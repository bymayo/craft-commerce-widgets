<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\base\Component;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\Plugin as Commerce;
use craft\db\Query;

use Exception;

class ProductsAnalyticsBar extends Component
{

    public const PERMISSION = 'commerceWidgets-viewProductsAnalyticsBar';

    public const MAX_STATS = 6;

    /**
     * The stats that can appear above the Commerce product index, in the order they're displayed.
     *
     * Unlike the order index, Commerce's product index has no date range picker, so there's no period
     * to compare against — these are all a reading of the catalogue as it stands right now.
     */
    public const STATS = [
        'products' => [
            'label' => 'Products',
            'format' => 'number',
        ],
        'outOfStock' => [
            'label' => 'Out of Stock',
            'format' => 'number',
        ],
        'lowStock' => [
            'label' => 'Low Stock',
            'format' => 'number',
        ],
        'averagePrice' => [
            'label' => 'Avg Price',
            'format' => 'currency',
        ],
        'stockValue' => [
            'label' => 'Stock Value',
            'format' => 'currency',
        ],
        'onPromotion' => [
            'label' => 'On Promotion',
            'format' => 'number',
        ],
        'unavailable' => [
            'label' => 'Unavailable',
            'format' => 'number',
        ],
        'variants' => [
            'label' => 'Variants',
            'format' => 'number',
        ],
    ];

    /**
     * Whether the current user is allowed to see the analytics bar.
     */
    public function canView(): bool
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return false;
        }

        return $user->admin || $user->can(self::PERMISSION);
    }

    /**
     * @return string[]
     */
    public function getEnabledStats(): array
    {
        $settings = CommerceWidgets::$plugin->getSettings();

        if (!$settings->enableProductsAnalyticsBar) {
            return [];
        }

        $enabled = is_array($settings->productsAnalyticsBarStats) ? $settings->productsAnalyticsBarStats : [];

        $handles = array_values(array_filter(
            array_keys(self::STATS),
            fn(string $handle) => in_array($handle, $enabled, true)
        ));

        return array_slice($handles, 0, self::MAX_STATS);
    }

    public function getEnabledStatsForJs(): array
    {
        return array_map(fn(string $handle) => [
            'handle' => $handle,
            'label' => self::STATS[$handle]['label'],
        ], $this->getEnabledStats());
    }

    /**
     * Label/value pairs for every stat, for the settings screen.
     */
    public function getStatOptions(): array
    {
        return array_map(fn(string $handle) => [
            'label' => self::STATS[$handle]['label'],
            'value' => $handle,
        ], array_keys(self::STATS));
    }

    /**
     * Builds every enabled stat for the given product index query.
     */
    public function getStats(ProductQuery $query): array
    {
        $values = $this->getValues($query, $this->getEnabledStats());
        $currencyCode = $this->getCurrencyCode();

        $stats = [];

        foreach ($this->getEnabledStats() as $handle) {
            $stats[] = [
                'handle' => $handle,
                'label' => self::STATS[$handle]['label'],
                'value' => $values[$handle],
                'formattedValue' => $this->format($values[$handle], self::STATS[$handle]['format'], $currencyCode),
                'changeIndicator' => null,
                'changeDirection' => 'neutral',
                'changeTooltip' => null,
            ];
        }

        return $stats;
    }

    /**
     * @param string[] $handles
     * @return array<string, float>
     */
    private function getValues(ProductQuery $query, array $handles): array
    {
        $base = (clone $query)
            ->limit(null)
            ->offset(null)
            ->orderBy([]);

        $values = [];

        foreach ($handles as $handle) {
            $values[$handle] = match ($handle) {
                'products' => $this->scalar(fn() => (clone $base)->count()),
                'averagePrice' => $this->scalar(fn() => (clone $base)->average('[[commerce_products.defaultPrice]]')),
                'outOfStock' => $this->countMatching($base, $this->stockRollup(0)),
                'lowStock' => $this->countMatching($base, $this->stockRollup($this->getLowStockThreshold())),
                'onPromotion' => $this->countMatching($base, $this->variantFlag(['not', ['ps.basePromotionalPrice' => null]])),
                'unavailable' => $this->countMatching($base, $this->variantFlag(['ps.availableForPurchase' => false])),
                'variants' => $this->scalar(fn() => $this->variantAggregate($base)->count('v.id')),
                'stockValue' => $this->scalar(fn() => $this->variantAggregate($base)->sum('ps.stock * ps.basePrice')),
                default => 0.0,
            };
        }

        return $values;
    }

    /**
     * Counts the products in view whose IDs appear in the given variant-level subquery.
     *
     * Matching on IDs rather than joining keeps one row per product — a join through variants would
     * multiply rows and inflate the count for anything with more than one variant.
     */
    private function countMatching(ProductQuery $base, Query $subQuery): float
    {
        return $this->scalar(fn() => (clone $base)
            ->andWhere(['commerce_products.id' => $subQuery])
            ->count());
    }

    /**
     * Products whose tracked stock across all variants is at or below the given level.
     */
    private function stockRollup(int $threshold): Query
    {
        return $this->variantQuery()
            ->andWhere(['ps.inventoryTracked' => true])
            ->groupBy(['v.primaryOwnerId'])
            ->having(['<=', 'SUM(ps.stock)', $threshold]);
    }

    /**
     * Products with at least one variant matching the given condition.
     */
    private function variantFlag(array $condition): Query
    {
        return $this->variantQuery()->andWhere($condition);
    }

    private function variantQuery(): Query
    {
        return (new Query())
            ->select(['v.primaryOwnerId'])
            ->from(['v' => '{{%commerce_variants}}'])
            ->innerJoin(['ps' => '{{%commerce_purchasables_stores}}'], '[[ps.purchasableId]] = [[v.id]]')
            ->where(['ps.storeId' => $this->getStoreId()]);
    }

    /**
     * A variant-level query scoped to the products the index is currently showing.
     *
     * The element query goes in as a subquery rather than a list of IDs, so a large catalogue doesn't
     * have to be pulled into PHP just to be counted.
     */
    private function variantAggregate(ProductQuery $base): Query
    {
        return $this->variantQuery()
            ->andWhere(['v.primaryOwnerId' => (clone $base)->select(['elements.id'])]);
    }

    public function getLowStockThreshold(): int
    {
        return max(0, (int) (CommerceWidgets::$plugin->getSettings()->lowStockThreshold ?? 5));
    }

    private function scalar(callable $query): float
    {
        try {
            return (float) $query();
        } catch (Exception $e) {
            return 0.0;
        }
    }

    private function getStoreId(): ?int
    {
        try {
            return Commerce::getInstance()->getStores()->getCurrentStore()->id;
        } catch (Exception $e) {
            return null;
        }
    }

    private function getCurrencyCode(): ?string
    {
        try {
            return Commerce::getInstance()->getStores()->getCurrentStore()->getCurrency()?->getCode();
        } catch (Exception $e) {
            return null;
        }
    }

    private function format(float $value, string $format, ?string $currencyCode): string
    {
        $formatter = Craft::$app->getFormatter();

        if ($format !== 'currency') {
            return $formatter->asDecimal($value, 0);
        }

        return $currencyCode !== null
            ? $formatter->asCurrency($value, $currencyCode)
            : $formatter->asDecimal($value, 2);
    }

}
