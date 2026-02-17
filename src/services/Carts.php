<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use yii\caching\TagDependency;

use Exception;

class Carts extends Component
{

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
                $startDay = (int) $settings->fiscalYearStartDay;
                $startMonth = $settings->fiscalYearStartMonth;
                $endDay = (int) $settings->fiscalYearEndDay;
                $endMonth = $settings->fiscalYearEndMonth;
                $fiscalMonth = date('n', strtotime("1 $startMonth"));
                $currentMonth = (int) date('n');
                $currentYear = (int) date('Y');

                if ($currentMonth > $fiscalMonth || ($currentMonth == $fiscalMonth && (int) date('j') >= $startDay)) {
                    $currentStartYear = $currentYear;
                } else {
                    $currentStartYear = $currentYear - 1;
                }

                for ($i = $previousAmount - 1; $i >= 0; $i--) {
                    $sYear = $currentStartYear - $i;
                    $eYear = $sYear + 1;
                    $start = date('Y-m-d', strtotime("$startDay $startMonth $sYear"));
                    $end = date('Y-m-d', strtotime("$endDay $endMonth $eYear"));
                    $periods[] = [
                        'label' => 'FY ' . substr($sYear, 2) . '/' . substr($eYear, 2),
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

    public function getTotalCarts(int $isCompleted, string $targetDuration, int $previousAmount): array
    {
        $periods = $this->getPeriods($targetDuration, $previousAmount);
        if (empty($periods)) {
            return [];
        }

        $startDate = $periods[0]['start'];
        $endDate = end($periods)['end'];

        try {

            $query = (new Query())
                ->select([
                    'DATE(orders.dateCreated) AS orderDate',
                    'COALESCE(COUNT(orders.id), 0) AS count'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['between', 'orders.dateCreated', $startDate, $endDate . ' 23:59:59'])
                ->andWhere(['orders.isCompleted' => $isCompleted])
                ->andWhere(['elements.dateDeleted' => null])
                ->groupBy('orderDate');

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $results = $query->cache($cacheDuration, $dependency)->all();

            $dateMap = [];
            foreach ($results as $row) {
                $dateMap[$row['orderDate']] = (int) $row['count'];
            }

            $data = [];
            foreach ($periods as $period) {
                $count = 0;
                foreach ($dateMap as $date => $c) {
                    if ($date >= $period['start'] && $date <= $period['end']) {
                        $count += $c;
                    }
                }
                $data[] = $count;
            }

            return $data;

        }
        catch (Exception $e) {
            return [];
        }

    }

    public function getCartTotalRevenue(int $isCompleted, string $targetDuration): ?array
    {
        $periods = $this->getPeriods($targetDuration, 1);
        if (empty($periods)) {
            return null;
        }

        $period = end($periods);

        try {

            $query = (new Query())
                ->select([
                    'COALESCE(sum(orders.totalPrice), 0) as totalPrice',
                    'COALESCE(count(orders.id), 0) as count'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['between', 'orders.dateCreated', $period['start'], $period['end'] . ' 23:59:59'])
                ->andWhere(['orders.isCompleted' => $isCompleted])
                ->andWhere(['elements.dateDeleted' => null]);

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $query->cache($cacheDuration, $dependency)->one();

            return $result;

        }
        catch (Exception $e) {
            return null;
        }

    }

}
