<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\ConfigHelper;
use craft\helpers\DateTimeHelper;
use yii\caching\TagDependency;

use DateTime;
use Exception;

class Carts extends Component
{

    private function getInactiveCartCutoff(): string
    {
        $edge = new DateTime();
        $seconds = ConfigHelper::durationInSeconds(CommercePlugin::getInstance()->getSettings()->activeCartDuration);
        $interval = DateTimeHelper::secondsToInterval($seconds);
        $edge->sub($interval);
        return $edge->format('Y-m-d H:i:s');
    }

    public function getPeriods(string $targetDuration, int $previousAmount): array
    {
        $targetDuration = CommerceWidgets::$plugin->helpers->getTargetDuration($targetDuration);
        $settings = CommerceWidgets::$plugin->getSettings();
        $periods = [];

        switch ($targetDuration) {
            case 'daily':
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $date = strtotime("-$i days");
                    $periods[] = [
                        'label' => date('D j', $date),
                        'start' => date('Y-m-d', $date),
                        'end' => date('Y-m-d', $date),
                    ];
                }
                break;

            case 'weekly':
                $weekStart = $settings->weekStart ?? 'monday';
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $start = strtotime("last $weekStart -" . ($i - 1) . " weeks");
                    if ($i === 0) {
                        $start = strtotime("last $weekStart", strtotime('tomorrow'));
                    }
                    $end = strtotime('+6 days', $start);
                    $periods[] = [
                        'label' => date('j M', $start),
                        'start' => date('Y-m-d', $start),
                        'end' => date('Y-m-d', $end),
                    ];
                }
                break;

            case 'monthly':
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $date = strtotime("-$i months");
                    $label = date('M', $date);
                    if (date('Y', $date) !== date('Y')) {
                        $label .= " '" . date('y', $date);
                    }
                    $periods[] = [
                        'label' => $label,
                        'start' => date('Y-m-01', $date),
                        'end' => date('Y-m-t', $date),
                    ];
                }
                break;

            case 'yearly':
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $year = (int) date('Y') - $i;
                    $periods[] = [
                        'label' => (string) $year,
                        'start' => "$year-01-01",
                        'end' => "$year-12-31",
                    ];
                }
                break;

            case 'fiscalYear':
                $fiscal = CommerceWidgets::$plugin->helpers->getFiscalYearDates();

                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $sYear = $fiscal['startYear'] - $i;
                    $eYear = $sYear + 1;
                    $start = date('Y-m-d', strtotime("{$fiscal['startDay']} {$fiscal['startMonth']} $sYear"));
                    $end = date('Y-m-d', strtotime("{$fiscal['endDay']} {$fiscal['endMonth']} $eYear"));
                    $periods[] = [
                        'label' => 'FY ' . substr((string) $sYear, 2) . '/' . substr((string) $eYear, 2),
                        'start' => $start,
                        'end' => $end,
                    ];
                }
                break;

            case 'allTime':
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $year = (int) date('Y') - $i;
                    $periods[] = [
                        'label' => (string) $year,
                        'start' => "$year-01-01",
                        'end' => "$year-12-31",
                    ];
                }
                break;
        }

        return $periods;
    }

    public function getCartAnalytics(string $targetDuration, int $previousAmount): array
    {
        $periods = $this->getPeriods($targetDuration, $previousAmount);

        $result = [
            'labels' => array_column($periods, 'label'),
            'completedChart' => array_fill(0, count($periods), 0),
            'abandonedChart' => array_fill(0, count($periods), 0),
            'completedTotal' => ['totalPrice' => 0.0, 'count' => 0],
            'abandonedTotal' => ['totalPrice' => 0.0, 'count' => 0],
            'completedChange' => ['percentage' => null, 'direction' => 'neutral'],
            'abandonedChange' => ['percentage' => null, 'direction' => 'neutral'],
            'changeTooltip' => CommerceWidgets::$plugin->helpers->getChangeTooltip($targetDuration),
        ];

        if (empty($periods)) {
            return $result;
        }

        $startDate = $periods[0]['start'];
        $endDate = end($periods)['end'];
        $cutoff = $this->getInactiveCartCutoff();
        $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
        $dependency = new TagDependency(['tags' => 'commerce-widgets']);
        $currentPeriod = end($periods);

        try {

            // Chart data: completed + abandoned in one query
            $chartQuery = (new Query())
                ->select([
                    'DATE(orders.dateCreated) AS orderDate',
                    'orders.isCompleted',
                    'COUNT(orders.id) AS count'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['between', 'orders.dateCreated', $startDate, $endDate . ' 23:59:59'])
                ->andWhere(['elements.dateDeleted' => null])
                ->andWhere([
                    'or',
                    ['orders.isCompleted' => 1],
                    [
                        'and',
                        ['orders.isCompleted' => 0],
                        ['<', 'elements.dateUpdated', $cutoff]
                    ]
                ])
                ->groupBy(['orderDate', 'orders.isCompleted']);

            $chartRows = $chartQuery->cache($cacheDuration, $dependency)->all();

            // Build date maps keyed by isCompleted
            $completedMap = [];
            $abandonedMap = [];
            foreach ($chartRows as $row) {
                if ((int) $row['isCompleted'] === 1) {
                    $completedMap[$row['orderDate']] = (int) $row['count'];
                } else {
                    $abandonedMap[$row['orderDate']] = (int) $row['count'];
                }
            }

            // Bucket into periods
            foreach ($periods as $i => $period) {
                foreach ($completedMap as $date => $count) {
                    if ($date >= $period['start'] && $date <= $period['end']) {
                        $result['completedChart'][$i] += $count;
                    }
                }
                foreach ($abandonedMap as $date => $count) {
                    if ($date >= $period['start'] && $date <= $period['end']) {
                        $result['abandonedChart'][$i] += $count;
                    }
                }
            }

            // Totals for current period: completed + abandoned in one query
            $totalsQuery = (new Query())
                ->select([
                    'orders.isCompleted',
                    'COALESCE(SUM(orders.totalPrice), 0) as totalPrice',
                    'COALESCE(COUNT(orders.id), 0) as count'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['between', 'orders.dateCreated', $currentPeriod['start'], $currentPeriod['end'] . ' 23:59:59'])
                ->andWhere(['elements.dateDeleted' => null])
                ->andWhere([
                    'or',
                    ['orders.isCompleted' => 1],
                    [
                        'and',
                        ['orders.isCompleted' => 0],
                        ['<', 'elements.dateUpdated', $cutoff]
                    ]
                ])
                ->groupBy('orders.isCompleted');

            $totalsRows = $totalsQuery->cache($cacheDuration, $dependency)->all();

            foreach ($totalsRows as $row) {
                $total = [
                    'totalPrice' => (float) $row['totalPrice'],
                    'count' => (int) $row['count'],
                ];
                if ((int) $row['isCompleted'] === 1) {
                    $result['completedTotal'] = $total;
                } else {
                    $result['abandonedTotal'] = $total;
                }
            }

            // Calculate change vs previous period from chart data
            $periodCount = count($periods);
            if ($periodCount >= 2) {
                $currentIdx = $periodCount - 1;
                $previousIdx = $periodCount - 2;

                $result['completedChange'] = CommerceWidgets::$plugin->helpers->calculateChange(
                    $result['completedChart'][$currentIdx],
                    $result['completedChart'][$previousIdx]
                );

                $result['abandonedChange'] = CommerceWidgets::$plugin->helpers->calculateChange(
                    $result['abandonedChart'][$currentIdx],
                    $result['abandonedChart'][$previousIdx]
                );
            }

        }
        catch (Exception $e) {
            // Return defaults on error
        }

        return $result;
    }

}
