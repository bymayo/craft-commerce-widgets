<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use craft\commerce\elements\Order;
use yii\caching\TagDependency;

use Exception;

class Orders extends Component
{

    public function getRecentOrders(int $limit = 5, ?int $orderStatusId = null): array
    {

        try {

            $query = Order::find()
                ->limit($limit)
                ->status(null)
                ->orderBy('dateUpdated desc');

            if ($orderStatusId) {
                $query->orderStatusId($orderStatusId);
            }

            return $query->all();

        }
        catch (Exception $e) {
            return [];
        }

    }

    public function getOrderTotals(string $targetDuration): array
    {

        try {

            $query = (new Query())
                ->select([
                    'COALESCE(count(*), 0) as totalOrders',
                    'COALESCE(SUM(orders.totalPaid),0) as totalRevenue'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->where(['orders.isCompleted' => 1]);

            CommerceWidgets::$plugin->helpers->applyDateFilter($query, $targetDuration);

            $result = $query->cache(CommerceWidgets::$plugin->getSettings()->cacheDuration)->one();

        }
        catch (Exception $e) {
            $result = null;
        }

        return $result ?? ['totalOrders' => 0, 'totalRevenue' => 0];

    }

    public function getTimeFrames(): array
    {
        $settings = CommerceWidgets::$plugin->getSettings();
        $startDay = (int) $settings->fiscalYearStartDay;
        $startMonth = $settings->fiscalYearStartMonth;
        $endDay = (int) $settings->fiscalYearEndDay;
        $endMonth = $settings->fiscalYearEndMonth;
        $fiscalMonth = date('n', strtotime("1 $startMonth"));
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        if ($currentMonth > $fiscalMonth || ($currentMonth == $fiscalMonth && (int) date('j') >= $startDay)) {
            $startYear = $currentYear;
        } else {
            $startYear = $currentYear - 1;
        }
        $endYear = $startYear + 1;

        $fiscalStart = date('Y-m-d', strtotime("$startDay $startMonth $startYear"));
        $fiscalEnd = date('Y-m-d', strtotime("$endDay $endMonth $endYear"));
        $prevFiscalStart = date('Y-m-d', strtotime("$startDay $startMonth " . ($startYear - 1)));
        $prevFiscalEnd = date('Y-m-d', strtotime("$endDay $endMonth $startYear"));

        return [
            [
                'label' => 'Today',
                'date' => date('d M Y'),
                'current' => ['DATE_FORMAT(orders.dateCreated, "%Y-%m-%d")' => date('Y-m-d')],
                'previous' => ['DATE_FORMAT(orders.dateCreated, "%Y-%m-%d")' => date('Y-m-d', strtotime('-1 day'))],
            ],
            [
                'label' => 'Week',
                'date' => date('d M Y', strtotime('monday this week')) . ' - ' . date('d M Y', strtotime('sunday this week')),
                'current' => ['between', 'orders.dateCreated', date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))],
                'previous' => ['between', 'orders.dateCreated', date('Y-m-d', strtotime('monday last week')), date('Y-m-d', strtotime('sunday last week'))],
            ],
            [
                'label' => 'Month',
                'date' => date('M Y'),
                'current' => ['between', 'orders.dateCreated', date('Y-m-d', strtotime('first day of this month')), date('Y-m-d', strtotime('last day of this month'))],
                'previous' => ['between', 'orders.dateCreated', date('Y-m-d', strtotime('first day of last month')), date('Y-m-d', strtotime('last day of last month'))],
            ],
            [
                'label' => 'Year',
                'date' => date('Y'),
                'current' => ['YEAR(orders.dateCreated)' => date('Y')],
                'previous' => ['YEAR(orders.dateCreated)' => date('Y', strtotime('-1 year'))],
            ],
            [
                'label' => 'Fiscal Year',
                'date' => date('d M Y', strtotime($fiscalStart)) . ' - ' . date('d M Y', strtotime($fiscalEnd)),
                'current' => ['between', 'orders.dateCreated', $fiscalStart, $fiscalEnd . ' 23:59:59'],
                'previous' => ['between', 'orders.dateCreated', $prevFiscalStart, $prevFiscalEnd . ' 23:59:59'],
            ],
            [
                'label' => 'All Time',
                'date' => '∞',
                'current' => null,
                'previous' => null,
            ],
        ];
    }

    public function getRevenueOrdersRow(array $timeFrame): ?array
    {

        try {

            $query = (new Query())
                ->select([
                    'COALESCE(sum(orders.totalPrice), 0) as totalRevenue',
                    'COALESCE(count(orders.id), 0) as totalOrders'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['orders.isCompleted' => 1])
                ->andWhere(['elements.dateDeleted' => null]);

            if ($timeFrame['current'] !== null) {
                $query->andWhere($timeFrame['current']);
            }

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $query->cache($cacheDuration, $dependency)->one();

            return $result;

        }
        catch (Exception $e) {
            return null;
        }

    }

    public function getPreviousRevenueOrdersRow(array $timeFrame): ?array
    {

        if ($timeFrame['previous'] === null) {
            return null;
        }

        try {

            $query = (new Query())
                ->select([
                    'COALESCE(sum(orders.totalPrice), 0) as totalRevenue',
                    'COALESCE(count(orders.id), 0) as totalOrders'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['orders.isCompleted' => 1])
                ->andWhere(['elements.dateDeleted' => null]);

            $query->andWhere($timeFrame['previous']);

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $query->cache($cacheDuration, $dependency)->one();

            return $result;

        }
        catch (Exception $e) {
            return null;
        }

    }

    public function getChangeIndicator($current, $previous): array
    {

        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0) {
            return [
                'percentage' => null,
                'direction' => $current > 0 ? 'up' : 'neutral'
            ];
        }

        $change = (($current - $previous) / $previous) * 100;
        $percentage = round(abs($change), 1) . '%';

        if ($change > 0) {
            $direction = 'up';
        } elseif ($change < 0) {
            $direction = 'down';
        } else {
            $direction = 'neutral';
        }

        return [
            'percentage' => $percentage,
            'direction' => $direction
        ];

    }

    public function getRevenueOrders(): array
    {

        $data = [];

        foreach ($this->getTimeFrames() as $timeFrame) {
            $current = $this->getRevenueOrdersRow($timeFrame);
            $previous = $this->getPreviousRevenueOrdersRow($timeFrame);

            $row = $current ?? ['totalRevenue' => 0, 'totalOrders' => 0];

            if ($current && $previous) {
                $change = $this->getChangeIndicator($current['totalRevenue'], $previous['totalRevenue']);
                $row['changeIndicator'] = $change['percentage'];
                $row['changeDirection'] = $change['direction'];
            } else {
                $row['changeIndicator'] = null;
                $row['changeDirection'] = 'neutral';
            }

            $data[] = $row;
        }

        return $data;

    }

}
