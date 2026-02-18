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

    private const ALLOWED_ORDER_BY = [
        'totalRevenue' => 'totalRevenue desc',
        'totalOrdered' => 'totalOrdered desc',
    ];

    public function getRecentProducts(int $limit = 5, ?int $productTypeId = null): array
    {

        try {

            $query = Product::find()
                ->limit($limit)
                ->orderBy('postDate desc');

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

            $safeOrderBy = self::ALLOWED_ORDER_BY[$orderBy] ?? 'totalRevenue desc';
            $dateRange = CommerceWidgets::$plugin->helpers->getDateRange($targetDuration, 'orders.datePaid');
            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);

            // Current period
            $query = $this->buildTopProductsQuery($safeOrderBy, $limit, $orderStatusId);

            if ($dateRange['current'] !== null) {
                $query->andWhere($dateRange['current']);
            }

            $result = $query->cache($cacheDuration, $dependency)->all();

            if (empty($result)) {
                return [];
            }

            // Previous period
            $productIds = array_column($result, 'id');
            $previousQuery = $this->buildTopProductsQuery($safeOrderBy, null, $orderStatusId);
            $previousQuery->andWhere(['in', 'variants.primaryOwnerId', $productIds]);

            if ($dateRange['previous'] !== null) {
                $previousQuery->andWhere($dateRange['previous']);
            }

            $previousRows = $previousQuery->cache($cacheDuration, $dependency)->all();
            $previousById = [];
            foreach ($previousRows as $row) {
                $previousById[$row['id']] = $row;
            }

            // Merge change indicators
            $compareField = ($orderBy === 'totalOrdered') ? 'totalOrdered' : 'totalRevenue';
            foreach ($result as &$row) {
                $prev = $previousById[$row['id']] ?? null;
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

    private function buildTopProductsQuery(string $orderBy, ?int $limit, ?int $orderStatusId): Query
    {
        $query = (new Query())
            ->select([
                'variants.primaryOwnerId as id',
                'MIN(purchasables.sku) as sku',
                'SUM(items.total) as totalRevenue',
                'count(*) as totalOrdered',
            ])
            ->from(['items' => '{{%commerce_lineitems}}'])
            ->join('INNER JOIN', '{{%commerce_purchasables}} purchasables', 'purchasables.id = items.purchasableId')
            ->join('INNER JOIN', '{{%commerce_variants}} variants', 'variants.id = purchasables.id')
            ->join('INNER JOIN', '{{%commerce_orders}} orders', 'orders.id = items.orderId')
            ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = variants.primaryOwnerId')
            ->where(['elements.dateDeleted' => null])
            ->andWhere(['orders.isCompleted' => 1])
            ->andWhere(['not', ['variants.id' => null]])
            ->groupBy(['variants.primaryOwnerId'])
            ->orderBy($orderBy);

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($orderStatusId !== null) {
            $query->andWhere(['orders.orderStatusId' => $orderStatusId]);
        }

        return $query;
    }

}
