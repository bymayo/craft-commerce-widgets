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

    private function buildCustomerQuery(string $orderBy, ?int $limit, bool $includeGuests, bool $excludeAdmins): Query
    {
        $query = (new Query())
            ->select([
                'count(*) as totalOrders',
                'SUM(orders.totalPrice) as totalRevenue',
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

        if ($includeGuests == false) {
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
