<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use yii\caching\TagDependency;

use Exception;

class Customers extends Component
{

    private const ALLOWED_ORDER_BY = [
        'totalRevenue' => 'totalRevenue desc',
        'totalOrders' => 'totalOrders desc',
    ];

    public function getTopCustomers(string $orderBy = 'totalRevenue', int $limit = 5, bool $includeGuests = true, string $targetDuration = 'default', bool $excludeAdmins = false): array
    {

        try {

            $safeOrderBy = self::ALLOWED_ORDER_BY[$orderBy] ?? 'totalRevenue desc';
            $dateRange = CommerceWidgets::$plugin->helpers->getDateRange($targetDuration, 'orders.datePaid');
            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);

            // Current period
            $query = $this->buildCustomerQuery($safeOrderBy, $limit, $includeGuests, $excludeAdmins);

            if ($dateRange['current'] !== null) {
                $query->andWhere($dateRange['current']);
            }

            $result = $query->cache($cacheDuration, $dependency)->all();

            if (empty($result)) {
                return [];
            }

            // Previous period
            $emails = array_column($result, 'email');
            $previousQuery = $this->buildCustomerQuery($safeOrderBy, null, $includeGuests, $excludeAdmins);
            $previousQuery->andWhere(['in', 'orders.email', $emails]);

            if ($dateRange['previous'] !== null) {
                $previousQuery->andWhere($dateRange['previous']);
            }

            $previousRows = $previousQuery->cache($cacheDuration, $dependency)->all();
            $previousByEmail = [];
            foreach ($previousRows as $row) {
                $previousByEmail[$row['email']] = $row;
            }

            // Merge change indicators
            $compareField = ($orderBy === 'totalOrders') ? 'totalOrders' : 'totalRevenue';
            foreach ($result as &$row) {
                $prev = $previousByEmail[$row['email']] ?? null;
                $prevValue = $prev ? (float) $prev[$compareField] : 0;
                $change = CommerceWidgets::$plugin->helpers->calculateChange((float) $row[$compareField], $prevValue);
                $row['changeIndicator'] = $change['percentage'];
                $row['changeDirection'] = $change['direction'];
            }

            return $result;

        }
        catch (Exception $e) {
            return [];
        }

    }

    public function getNewVsReturningAnalytics(string $targetDuration, int $previousAmount): array
    {
        $periods = CommerceWidgets::$plugin->carts->getPeriods($targetDuration, $previousAmount);

        $result = [
            'labels' => array_column($periods, 'label'),
            'newChart' => array_fill(0, count($periods), 0),
            'returningChart' => array_fill(0, count($periods), 0),
            'newTotal' => ['totalRevenue' => 0.0, 'count' => 0],
            'returningTotal' => ['totalRevenue' => 0.0, 'count' => 0],
            'newChange' => ['percentage' => null, 'direction' => 'neutral'],
            'returningChange' => ['percentage' => null, 'direction' => 'neutral'],
            'changeTooltip' => CommerceWidgets::$plugin->helpers->getChangeTooltip($targetDuration),
        ];

        if (empty($periods)) {
            return $result;
        }

        $startDate = $periods[0]['start'];
        $endDate = end($periods)['end'];
        $currentPeriod = end($periods);
        $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
        $dependency = new TagDependency(['tags' => 'commerce-widgets']);
        $excludeEmails = CommerceWidgets::$plugin->getSettings()->excludeEmailAddresses ?? [];

        try {

            // Query 1: First order date for each customer (all time)
            $firstOrdersQuery = (new Query())
                ->select(['orders.email', 'MIN(DATE(orders.datePaid)) as firstOrderDate'])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['orders.isCompleted' => 1])
                ->andWhere(['elements.dateDeleted' => null])
                ->andWhere(['not', ['orders.datePaid' => null]])
                ->groupBy('orders.email');

            if (!empty($excludeEmails)) {
                $firstOrdersQuery->andWhere(['not in', 'orders.email', $excludeEmails]);
            }

            $firstOrderRows = $firstOrdersQuery->cache($cacheDuration, $dependency)->all();

            $firstOrderMap = [];
            foreach ($firstOrderRows as $row) {
                $firstOrderMap[$row['email']] = $row['firstOrderDate'];
            }

            // Query 2: Distinct customer emails per day in the full date range
            $chartQuery = (new Query())
                ->select(['DATE(orders.datePaid) AS orderDate', 'orders.email'])
                ->distinct()
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['orders.isCompleted' => 1])
                ->andWhere(['elements.dateDeleted' => null])
                ->andWhere(['between', 'orders.datePaid', $startDate, $endDate . ' 23:59:59']);

            if (!empty($excludeEmails)) {
                $chartQuery->andWhere(['not in', 'orders.email', $excludeEmails]);
            }

            $chartRows = $chartQuery->cache($cacheDuration, $dependency)->all();

            // Bucket into periods: new vs returning
            foreach ($periods as $i => $period) {
                $newEmails = [];
                $returningEmails = [];

                foreach ($chartRows as $row) {
                    if ($row['orderDate'] >= $period['start'] && $row['orderDate'] <= $period['end']) {
                        $firstDate = $firstOrderMap[$row['email']] ?? null;
                        if ($firstDate !== null && $firstDate >= $period['start'] && $firstDate <= $period['end']) {
                            $newEmails[$row['email']] = true;
                        } else {
                            $returningEmails[$row['email']] = true;
                        }
                    }
                }

                $result['newChart'][$i] = count($newEmails);
                $result['returningChart'][$i] = count($returningEmails);
            }

            // Query 3: Totals for current period (email + revenue)
            $totalsQuery = (new Query())
                ->select(['orders.email', 'SUM(orders.totalPaid) as totalRevenue'])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['orders.isCompleted' => 1])
                ->andWhere(['elements.dateDeleted' => null])
                ->andWhere(['between', 'orders.datePaid', $currentPeriod['start'], $currentPeriod['end'] . ' 23:59:59'])
                ->groupBy('orders.email');

            if (!empty($excludeEmails)) {
                $totalsQuery->andWhere(['not in', 'orders.email', $excludeEmails]);
            }

            $totalsRows = $totalsQuery->cache($cacheDuration, $dependency)->all();

            foreach ($totalsRows as $row) {
                $firstDate = $firstOrderMap[$row['email']] ?? null;
                if ($firstDate !== null && $firstDate >= $currentPeriod['start'] && $firstDate <= $currentPeriod['end']) {
                    $result['newTotal']['count']++;
                    $result['newTotal']['totalRevenue'] += (float) $row['totalRevenue'];
                } else {
                    $result['returningTotal']['count']++;
                    $result['returningTotal']['totalRevenue'] += (float) $row['totalRevenue'];
                }
            }

            // Calculate change vs previous period from chart data
            $periodCount = count($periods);
            if ($periodCount >= 2) {
                $currentIdx = $periodCount - 1;
                $previousIdx = $periodCount - 2;

                $result['newChange'] = CommerceWidgets::$plugin->helpers->calculateChange(
                    $result['newChart'][$currentIdx],
                    $result['newChart'][$previousIdx]
                );

                $result['returningChange'] = CommerceWidgets::$plugin->helpers->calculateChange(
                    $result['returningChart'][$currentIdx],
                    $result['returningChart'][$previousIdx]
                );
            }

        }
        catch (Exception $e) {
            // Return defaults on error
        }

        return $result;
    }

    private function buildCustomerQuery(string $orderBy, ?int $limit, bool $includeGuests, bool $excludeAdmins): Query
    {
        $query = (new Query())
            ->select([
                'count(*) as totalOrders',
                'SUM(orders.totalPaid) as totalRevenue',
                'orders.email',
                'MAX(orders.customerId) as customerId'
            ])
            ->from(['orders' => '{{%commerce_orders}}'])
            ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
            ->where(['orders.isCompleted' => 1])
            ->andWhere(['elements.dateDeleted' => null])
            ->orderBy($orderBy)
            ->groupBy(['orders.email']);

        if ($limit !== null) {
            $query->limit($limit);
        }

        if (!empty(CommerceWidgets::$plugin->getSettings()->excludeEmailAddresses)) {
            $query->andWhere(['not in', 'orders.email', CommerceWidgets::$plugin->getSettings()->excludeEmailAddresses]);
        }

        if ($includeGuests === false) {
            $query
                ->join('INNER JOIN', '{{%commerce_customers}} customers', 'orders.customerId = customers.id')
                ->andWhere(['not', ['customers.userId' => null]]);
        }

        if ($excludeAdmins) {
            $query
                ->join('LEFT JOIN', '{{%users}} users', 'orders.customerId = users.id')
                ->andWhere(['or', ['users.admin' => false], ['users.id' => null]]);
        }

        return $query;
    }

}
