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
                ->isCompleted(true)
                ->orderBy('dateOrdered desc');

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

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);

            $query = (new Query())
                ->select([
                    'COALESCE(count(*), 0) as totalOrders',
                    'COALESCE(SUM(orders.totalPaid), 0) as totalRevenue'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['orders.isCompleted' => 1])
                ->andWhere(['elements.dateDeleted' => null]);

            CommerceWidgets::$plugin->helpers->applyDateFilter($query, $targetDuration);

            $result = $query->cache($cacheDuration, $dependency)->one();

        }
        catch (Exception $e) {
            $result = null;
        }

        return $result ?? ['totalOrders' => 0, 'totalRevenue' => 0];

    }

    public function getTimeFrames(): array
    {
        $settings = CommerceWidgets::$plugin->getSettings();
        $weekStart = $settings->weekStart ?? 'monday';

        $weekStartDate = strtotime("last $weekStart", strtotime('tomorrow'));
        $weekEndDate = strtotime('+6 days', $weekStartDate);
        $prevWeekStartDate = strtotime('-7 days', $weekStartDate);
        $prevWeekEndDate = strtotime('-1 day', $weekStartDate);

        $fiscal = CommerceWidgets::$plugin->helpers->getFiscalYearDates();

        return [
            [
                'label' => 'Today',
                'changeTooltip' => 'Compared to yesterday',
                'current' => ['DATE_FORMAT(orders.datePaid, "%Y-%m-%d")' => date('Y-m-d')],
                'previous' => ['DATE_FORMAT(orders.datePaid, "%Y-%m-%d")' => date('Y-m-d', strtotime('-1 day'))],
            ],
            [
                'label' => 'Week',
                'changeTooltip' => 'Compared to previous week',
                'current' => ['between', 'orders.datePaid', date('Y-m-d', $weekStartDate), date('Y-m-d', $weekEndDate) . ' 23:59:59'],
                'previous' => ['between', 'orders.datePaid', date('Y-m-d', $prevWeekStartDate), date('Y-m-d', $prevWeekEndDate) . ' 23:59:59'],
            ],
            [
                'label' => 'Month',
                'changeTooltip' => 'Compared to previous month',
                'current' => ['between', 'orders.datePaid', date('Y-m-d', strtotime('first day of this month')), date('Y-m-d', strtotime('last day of this month')) . ' 23:59:59'],
                'previous' => ['between', 'orders.datePaid', date('Y-m-d', strtotime('first day of last month')), date('Y-m-d', strtotime('last day of last month')) . ' 23:59:59'],
            ],
            [
                'label' => 'Year',
                'changeTooltip' => 'Compared to previous year',
                'current' => ['YEAR(orders.datePaid)' => date('Y')],
                'previous' => ['YEAR(orders.datePaid)' => date('Y', strtotime('-1 year'))],
            ],
            [
                'label' => 'Fiscal Year',
                'changeTooltip' => 'Compared to previous fiscal year',
                'current' => ['between', 'orders.datePaid', $fiscal['start'], $fiscal['end'] . ' 23:59:59'],
                'previous' => ['between', 'orders.datePaid', $fiscal['prevStart'], $fiscal['prevEnd'] . ' 23:59:59'],
            ],
            [
                'label' => 'All Time',
                'changeTooltip' => null,
                'current' => null,
                'previous' => null,
            ],
        ];
    }

    private function buildRevenueQuery(?array $condition): Query
    {
        $query = (new Query())
            ->select([
                'COALESCE(SUM(orders.totalPaid), 0) as totalRevenue',
                'COALESCE(COUNT(orders.id), 0) as totalOrders',
            ])
            ->from(['orders' => '{{%commerce_orders}}'])
            ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
            ->where(['orders.isCompleted' => 1])
            ->andWhere(['elements.dateDeleted' => null]);

        if ($condition !== null) {
            $query->andWhere($condition);
        }

        return $query;
    }

    public function getConversionData(string $targetDuration): array
    {
        $dateRange = CommerceWidgets::$plugin->helpers->getDateRange($targetDuration);
        $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
        $dependency = new TagDependency(['tags' => 'commerce-widgets']);

        $result = [
            'addToCart' => ['current' => 0, 'previous' => 0],
            'checkout' => ['current' => 0, 'previous' => 0],
            'completed' => ['current' => 0, 'previous' => 0],
            'addToCartChange' => ['percentage' => null, 'direction' => 'neutral'],
            'checkoutChange' => ['percentage' => null, 'direction' => 'neutral'],
            'completedChange' => ['percentage' => null, 'direction' => 'neutral'],
            'changeTooltip' => CommerceWidgets::$plugin->helpers->getChangeTooltip($targetDuration),
        ];

        try {

            foreach (['current', 'previous'] as $period) {
                $query = (new Query())
                    ->select([
                        'COALESCE(COUNT(orders.id), 0) as addToCart',
                        'COALESCE(SUM(CASE WHEN orders.billingAddressId IS NOT NULL OR orders.shippingAddressId IS NOT NULL THEN 1 ELSE 0 END), 0) as checkout',
                        'COALESCE(SUM(CASE WHEN orders.isCompleted = 1 THEN 1 ELSE 0 END), 0) as completed',
                    ])
                    ->from(['orders' => '{{%commerce_orders}}'])
                    ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                    ->andWhere(['elements.dateDeleted' => null]);

                if ($dateRange[$period] !== null) {
                    $query->andWhere($dateRange[$period]);
                }

                $row = $query->cache($cacheDuration, $dependency)->one();

                if ($row) {
                    $result['addToCart'][$period] = (int) $row['addToCart'];
                    $result['checkout'][$period] = (int) $row['checkout'];
                    $result['completed'][$period] = (int) $row['completed'];
                }
            }

            $result['addToCartChange'] = CommerceWidgets::$plugin->helpers->calculateChange(
                $result['addToCart']['current'],
                $result['addToCart']['previous']
            );
            $result['checkoutChange'] = CommerceWidgets::$plugin->helpers->calculateChange(
                $result['checkout']['current'],
                $result['checkout']['previous']
            );
            $result['completedChange'] = CommerceWidgets::$plugin->helpers->calculateChange(
                $result['completed']['current'],
                $result['completed']['previous']
            );

        }
        catch (Exception $e) {
            // Return defaults on error
        }

        return $result;
    }

    public function getOrdersByCountry(string $targetDuration): array
    {
        try {

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);

            $query = (new Query())
                ->select([
                    'addresses.countryCode',
                    'COUNT(orders.id) as orderCount'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->join('LEFT JOIN', '{{%addresses}} addresses', 'addresses.id = orders.billingAddressId')
                ->where(['orders.isCompleted' => 1])
                ->andWhere(['elements.dateDeleted' => null])
                ->andWhere(['not', ['addresses.countryCode' => null]])
                ->groupBy('addresses.countryCode')
                ->orderBy('orderCount desc');

            CommerceWidgets::$plugin->helpers->applyDateFilter($query, $targetDuration);

            return $query->cache($cacheDuration, $dependency)->all();

        }
        catch (Exception $e) {
            return [];
        }
    }

    public function getRevenueOrders(): array
    {
        $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
        $dependency = new TagDependency(['tags' => 'commerce-widgets']);
        $data = [];

        foreach ($this->getTimeFrames() as $timeFrame) {
            try {
                $current = $this->buildRevenueQuery($timeFrame['current'])
                    ->cache($cacheDuration, $dependency)
                    ->one();
            } catch (Exception $e) {
                $current = null;
            }

            $row = $current ?? ['totalRevenue' => 0, 'totalOrders' => 0];
            $row['changeIndicator'] = null;
            $row['changeDirection'] = 'neutral';
            $row['changeTooltip'] = $timeFrame['changeTooltip'];

            if ($current && $timeFrame['previous'] !== null) {
                try {
                    $previous = $this->buildRevenueQuery($timeFrame['previous'])
                        ->cache($cacheDuration, $dependency)
                        ->one();
                } catch (Exception $e) {
                    $previous = null;
                }

                if ($previous) {
                    $change = CommerceWidgets::$plugin->helpers->calculateChange(
                        (float) $current['totalRevenue'],
                        (float) $previous['totalRevenue']
                    );
                    $row['changeIndicator'] = $change['percentage'];
                    $row['changeDirection'] = $change['direction'];
                }
            }

            $data[] = $row;
        }

        return $data;
    }

}
