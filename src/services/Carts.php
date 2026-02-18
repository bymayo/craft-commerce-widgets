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
        $helpers = CommerceWidgets::$plugin->helpers;
        $now = $helpers->craftNow();
        $periods = [];

        switch ($targetDuration) {
            case 'daily':
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $day = (clone $now)->modify("-$i days");
                    $start = (clone $day)->setTime(0, 0, 0);
                    $end = (clone $day)->setTime(23, 59, 59);
                    $periods[] = [
                        'label' => $day->format('D j'),
                        'start' => $helpers->toUtc($start),
                        'end' => $helpers->toUtc($end),
                    ];
                }
                break;

            case 'weekly':
                $weekStart = $settings->weekStart ?? 'monday';
                $tomorrow = (clone $now)->modify('+1 day');
                $thisWeekStart = (clone $tomorrow)->modify("last $weekStart");

                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $start = (clone $thisWeekStart)->modify("-$i weeks")->setTime(0, 0, 0);
                    $end = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                    $periods[] = [
                        'label' => $start->format('j M'),
                        'start' => $helpers->toUtc($start),
                        'end' => $helpers->toUtc($end),
                    ];
                }
                break;

            case 'monthly':
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $month = (clone $now)->modify("-$i months");
                    $start = (clone $month)->modify('first day of this month')->setTime(0, 0, 0);
                    $end = (clone $month)->modify('last day of this month')->setTime(23, 59, 59);
                    $label = $month->format('M');
                    if ($month->format('Y') !== $now->format('Y')) {
                        $label .= " '" . $month->format('y');
                    }
                    $periods[] = [
                        'label' => $label,
                        'start' => $helpers->toUtc($start),
                        'end' => $helpers->toUtc($end),
                    ];
                }
                break;

            case 'yearly':
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $year = (int) $now->format('Y') - $i;
                    $start = $helpers->craftDate("first day of january $year")->setTime(0, 0, 0);
                    $end = $helpers->craftDate("last day of december $year")->setTime(23, 59, 59);
                    $periods[] = [
                        'label' => (string) $year,
                        'start' => $helpers->toUtc($start),
                        'end' => $helpers->toUtc($end),
                    ];
                }
                break;

            case 'fiscalYear':
                $fiscal = $helpers->getFiscalYearDates();

                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $sYear = $fiscal['startYear'] - $i;
                    $eYear = $sYear + 1;
                    $start = $helpers->craftDate("{$fiscal['startDay']} {$fiscal['startMonth']} $sYear")->setTime(0, 0, 0);
                    $end = $helpers->craftDate("{$fiscal['endDay']} {$fiscal['endMonth']} $eYear")->setTime(23, 59, 59);
                    $periods[] = [
                        'label' => 'FY ' . substr((string) $sYear, 2) . '/' . substr((string) $eYear, 2),
                        'start' => $helpers->toUtc($start),
                        'end' => $helpers->toUtc($end),
                    ];
                }
                break;

            case 'allTime':
                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $year = (int) $now->format('Y') - $i;
                    $start = $helpers->craftDate("first day of january $year")->setTime(0, 0, 0);
                    $end = $helpers->craftDate("last day of december $year")->setTime(23, 59, 59);
                    $periods[] = [
                        'label' => (string) $year,
                        'start' => $helpers->toUtc($start),
                        'end' => $helpers->toUtc($end),
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

            // Chart data: fetch individual rows for timezone-correct bucketing
            $chartQuery = (new Query())
                ->select([
                    'orders.dateCreated',
                    'orders.isCompleted',
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['between', 'orders.dateCreated', $startDate, $endDate])
                ->andWhere(['elements.dateDeleted' => null])
                ->andWhere([
                    'or',
                    ['orders.isCompleted' => 1],
                    [
                        'and',
                        ['orders.isCompleted' => 0],
                        ['<', 'elements.dateUpdated', $cutoff]
                    ]
                ]);

            $chartRows = $chartQuery->cache($cacheDuration, $dependency)->all();

            // Bucket into periods using full datetime comparison
            foreach ($chartRows as $row) {
                $orderDate = $row['dateCreated'];
                $isCompleted = (int) $row['isCompleted'];

                foreach ($periods as $i => $period) {
                    if ($orderDate >= $period['start'] && $orderDate <= $period['end']) {
                        if ($isCompleted === 1) {
                            $result['completedChart'][$i]++;
                        } else {
                            $result['abandonedChart'][$i]++;
                        }
                        break;
                    }
                }
            }

            // Totals for current period
            $totalsQuery = (new Query())
                ->select([
                    'orders.isCompleted',
                    'COALESCE(SUM(orders.totalPrice), 0) as totalPrice',
                    'COALESCE(COUNT(orders.id), 0) as count'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['between', 'orders.dateCreated', $currentPeriod['start'], $currentPeriod['end']])
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
