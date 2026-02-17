<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use yii\caching\TagDependency;

use Exception;

class Customers extends Component
{

    public function getTopCustomers(string $orderBy = 'totalRevenue', int $limit = 5, bool $includeGuests = true, string $targetDuration = 'default'): array
    {

        try {

            $query = (new Query())
                ->select([
                    'count(*) as totalOrders',
                    'SUM(orders.totalPrice) as totalRevenue',
                    'orders.email',
                    'orders.customerId'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where(['orders.isCompleted' => 1])
                ->andWhere(['elements.dateDeleted' => null])
                ->orderBy($orderBy . ' desc')
                ->groupBy(['orders.email'])
                ->limit($limit);

            CommerceWidgets::$plugin->helpers->applyDateFilter($query, $targetDuration, 'orders.datePaid');

            if (!empty(CommerceWidgets::$plugin->getSettings()->excludeEmailAddresses)) {
                $query->andWhere(['not in', 'orders.email', CommerceWidgets::$plugin->getSettings()->excludeEmailAddresses]);
            }

            if ($includeGuests == false) {
                $query
                    ->join('INNER JOIN', '{{%commerce_customers}} customers', 'orders.customerId = customers.id')
                    ->andWhere(['not', ['customers.userId' => null]]);
            }

            $command = $query->createCommand();
            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $command->cache($cacheDuration, $dependency)->queryAll();

            return $result;

        }
        catch (Exception $e) {
            return [];
        }

    }

}
