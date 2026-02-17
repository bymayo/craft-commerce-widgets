<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use craft\commerce\elements\Product;
use yii\caching\TagDependency;

use Exception;

class Products extends Component
{

    public function getRecentProducts(int $limit = 5, ?int $productTypeId = null): array
    {

        try {

            $query = Product::find()
                ->limit($limit)
                ->status(null);

            if ($productTypeId) {
                $query->typeId($productTypeId);
            }

            return $query->all();

        }
        catch (Exception $e) {
            return [];
        }

    }

    public function getTopProducts(string $orderBy = 'totalRevenue', int $limit = 5, ?int $orderStatusId = null, string $targetDuration = 'default'): array
    {

        try {

            $query = (new Query())
                ->select([
                    'variants.primaryOwnerId as id',
                    'purchasables.sku as sku',
                    'SUM(items.total) as totalRevenue',
                    'count(*) as totalOrdered',
                ])
                ->from(['items' => '{{%commerce_lineitems}}'])
                ->join('LEFT JOIN', '{{%commerce_purchasables}} purchasables', 'purchasables.id = items.purchasableId')
                ->join('LEFT JOIN', '{{%commerce_variants}} variants', 'variants.id = purchasables.id')
                ->join('LEFT JOIN', '{{%commerce_orders}} orders', 'orders.id = items.orderId')
                ->join('LEFT JOIN', '{{%elements}} elements', 'elements.id = variants.primaryOwnerId')
                ->where(['elements.dateDeleted' => null])
                ->andWhere(['orders.isCompleted' => 1])
                ->groupBy(['items.purchasableId'])
                ->orderBy($orderBy . ' desc')
                ->limit($limit);

            CommerceWidgets::$plugin->helpers->applyDateFilter($query, $targetDuration, 'orders.datePaid');

            if ($orderStatusId != null) {
                $query
                    ->andWhere(['orders.orderStatusId' => $orderStatusId])
                    ->andWhere(['not', ['variants.id' => null]]);
            }

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $query->cache($cacheDuration, $dependency)->all();

            return $result;

        }
        catch (Exception $e) {
            return [];
        }

    }

}
