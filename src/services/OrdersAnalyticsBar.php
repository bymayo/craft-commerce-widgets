<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\base\Component;
use craft\commerce\elements\db\OrderQuery;
use craft\commerce\Plugin as Commerce;

use Exception;

class OrdersAnalyticsBar extends Component
{

    public const PERMISSION = 'commerceWidgets-viewOrdersAnalyticsBar';

    /**
     * The bar reads as a summary rather than a report, and more than this starts to crowd the row on
     * anything but a very wide screen. Enforced in the settings screen and again on save.
     */
    public const MAX_STATS = 6;

    /**
     * The stats that can appear above the Commerce order index, in the order they're displayed.
     *
     * `statusSetting` names the plugin setting holding the order status handles that stat counts.
     * A stat with a `statusSetting` is skipped until at least one status has been chosen for it.
     */
    public const STATS = [
        'orders' => [
            'label' => 'Orders',
            'format' => 'number',
            'statusSetting' => null,
        ],
        'revenue' => [
            'label' => 'Revenue',
            'format' => 'currency',
            'statusSetting' => null,
        ],
        'averageOrderValue' => [
            'label' => 'Avg Order Value',
            'format' => 'currency',
            'statusSetting' => null,
        ],
        'toFulfil' => [
            'label' => 'Orders to Fulfil',
            'format' => 'number',
            'statusSetting' => 'orderStatusesToFulfil',
        ],
        'awaitingPayment' => [
            'label' => 'Awaiting Payment',
            'format' => 'number',
            'statusSetting' => null,
        ],
        'shipped' => [
            'label' => 'Orders Shipped',
            'format' => 'number',
            'statusSetting' => 'orderStatusesShipped',
        ],
        'returned' => [
            'label' => 'Orders Returned',
            'format' => 'number',
            'statusSetting' => 'orderStatusesReturned',
        ],
        'itemsOrdered' => [
            'label' => 'Items Ordered',
            'format' => 'number',
            'statusSetting' => null,
        ],
        'customers' => [
            'label' => 'Customers',
            'format' => 'number',
            'statusSetting' => null,
        ],
    ];

    /**
     * Revenue follows the same column the dashboard widgets treat as revenue, so the two agree.
     */
    private const REVENUE_COLUMN = '[[commerce_orders.totalPaid]]';

    /**
     * Commerce derives `paidStatus` itself, so this stat needs no status mapping.
     */
    private const AWAITING_PAYMENT_STATUSES = ['unpaid', 'partial'];

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
     * Stat handles that are switched on and, where a stat counts order statuses, actually configured.
     *
     * @return string[]
     */
    public function getEnabledStats(): array
    {
        $settings = CommerceWidgets::$plugin->getSettings();

        if (!$settings->enableOrdersAnalyticsBar) {
            return [];
        }

        $enabled = is_array($settings->ordersAnalyticsBarStats) ? $settings->ordersAnalyticsBarStats : [];

        $handles = array_values(array_filter(
            array_keys(self::STATS),
            function(string $handle) use ($enabled, $settings) {
                if (!in_array($handle, $enabled, true)) {
                    return false;
                }

                $statusSetting = self::STATS[$handle]['statusSetting'];

                return $statusSetting === null || !empty($settings->$statusSetting);
            }
        ));

        // The settings screen caps selection, but a config file override or a value saved before the
        // cap existed could still ask for more, so hold the line here too.
        return array_slice($handles, 0, self::MAX_STATS);
    }

    /**
     * Handle/label pairs for the enabled stats, used to render the bar before any data has loaded.
     */
    public function getEnabledStatsForJs(): array
    {
        return array_map(fn(string $handle) => [
            'handle' => $handle,
            'label' => self::STATS[$handle]['label'],
        ], $this->getEnabledStats());
    }

    /**
     * Options for the settings screen's stat picker.
     *
     * A status-backed stat can't be shown until its order statuses are mapped, so it's offered as
     * disabled rather than selectable-but-silently-absent from the bar.
     */
    public function getStatOptions(): array
    {
        $settings = CommerceWidgets::$plugin->getSettings();

        return array_map(function(string $handle) use ($settings) {
            $statusSetting = self::STATS[$handle]['statusSetting'];

            return [
                'label' => self::STATS[$handle]['label'],
                'value' => $handle,
                'disabled' => $statusSetting !== null && empty($settings->$statusSetting),
            ];
        }, array_keys(self::STATS));
    }

    /**
     * Stat handle => the setting holding the order statuses it depends on, for stats that have one.
     *
     * Lets the settings screen enable and disable stat options as statuses are mapped, without a save.
     *
     * @return array<string, string>
     */
    public function getStatStatusSettings(): array
    {
        $map = [];

        foreach (self::STATS as $handle => $stat) {
            if ($stat['statusSetting'] !== null) {
                $map[$handle] = $stat['statusSetting'];
            }
        }

        return $map;
    }

    /**
     * Every order status across every store, deduped by handle, for the settings screen.
     *
     * Statuses are stored per store in Commerce, so the same handle can exist more than once. We key
     * on handle so a selection keeps working when a status is recreated or a second store is added.
     */
    public function getOrderStatusOptions(): array
    {
        $options = [];

        try {
            $stores = Commerce::getInstance()->getStores()->getAllStores();

            foreach ($stores as $store) {
                foreach (Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses($store->id) as $status) {
                    if (isset($options[$status->handle])) {
                        continue;
                    }

                    $options[$status->handle] = [
                        'label' => $status->name,
                        'value' => $status->handle,
                    ];
                }
            }
        } catch (Exception $e) {
            return [];
        }

        return array_values($options);
    }

    /**
     * Resolves order status handles to IDs across every store.
     *
     * @param string[] $handles
     * @return int[]
     */
    public function getOrderStatusIds(array $handles): array
    {
        if (empty($handles)) {
            return [];
        }

        $ids = [];

        try {
            $stores = Commerce::getInstance()->getStores()->getAllStores();

            foreach ($stores as $store) {
                foreach (Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses($store->id) as $status) {
                    if (in_array($status->handle, $handles, true)) {
                        $ids[] = (int) $status->id;
                    }
                }
            }
        } catch (Exception $e) {
            return [];
        }

        return array_values(array_unique($ids));
    }

    /**
     * Builds every enabled stat for the given order index query.
     *
     * @param OrderQuery $query The query behind the current index view, filters and all.
     * @param array|null $previousDateParam A date query param covering the window immediately before
     *                                      the filtered one, or null when there's nothing to compare to.
     * @param string $dateAttr The order query attribute $previousDateParam applies to.
     * @param string|null $changeTooltip Explains what the change is measured against.
     */
    public function getStats(
        OrderQuery $query,
        ?array $previousDateParam = null,
        string $dateAttr = 'dateUpdated',
        ?string $changeTooltip = null
    ): array {
        $handles = $this->getEnabledStats();
        $current = $this->getValues($query, $handles);
        $previous = null;

        if ($previousDateParam !== null) {
            $previousQuery = (clone $query);
            $previousQuery->$dateAttr = $previousDateParam;
            $previous = $this->getValues($previousQuery, $handles);
        }

        $currencyCode = $this->getCurrencyCode($query);
        $stats = [];

        foreach ($handles as $handle) {
            $stat = [
                'handle' => $handle,
                'label' => self::STATS[$handle]['label'],
                'value' => $current[$handle],
                'formattedValue' => $this->format($current[$handle], self::STATS[$handle]['format'], $currencyCode),
                'changeIndicator' => null,
                'changeDirection' => 'neutral',
                'changeTooltip' => $changeTooltip,
            ];

            if ($previous !== null) {
                $change = CommerceWidgets::$plugin->helpers->calculateChange(
                    $current[$handle],
                    $previous[$handle]
                );

                $stat['changeIndicator'] = $change['percentage'];
                $stat['changeDirection'] = $change['direction'];
            }

            $stats[] = $stat;
        }

        return $stats;
    }

    /**
     * Runs every enabled stat against one period of the index query.
     *
     * Order count and revenue are each needed by Average Order Value as well, so they're fetched once
     * and shared — with all three enabled that's two queries rather than four.
     *
     * @param string[] $handles
     * @return array<string, float>
     */
    private function getValues(OrderQuery $query, array $handles): array
    {
        $base = (clone $query)
            ->limit(null)
            ->offset(null)
            ->orderBy([]);

        $orders = null;
        $revenue = null;

        if (array_intersect($handles, ['orders', 'averageOrderValue'])) {
            $orders = $this->scalar(fn() => (clone $base)->count());
        }

        if (array_intersect($handles, ['revenue', 'averageOrderValue'])) {
            $revenue = $this->scalar(fn() => (clone $base)->sum(self::REVENUE_COLUMN));
        }

        $values = [];

        foreach ($handles as $handle) {
            $values[$handle] = match ($handle) {
                'orders' => $orders,
                'revenue' => $revenue,
                'averageOrderValue' => $orders > 0 ? $revenue / $orders : 0.0,
                'itemsOrdered' => $this->scalar(fn() => (clone $base)->sum('[[commerce_orders.totalQty]]')),
                'customers' => $this->scalar(fn() => (clone $base)->count('DISTINCT [[commerce_orders.customerId]]')),
                'awaitingPayment' => $this->scalar(fn() => (clone $base)
                    ->andWhere(['commerce_orders.paidStatus' => self::AWAITING_PAYMENT_STATUSES])
                    ->count()),
                default => $this->getStatusValue($handle, $base),
            };
        }

        return $values;
    }

    /**
     * Counts the orders sitting in the statuses mapped to a status-backed stat.
     */
    private function getStatusValue(string $handle, OrderQuery $base): float
    {
        $statusSetting = self::STATS[$handle]['statusSetting'] ?? null;

        if ($statusSetting === null) {
            return 0.0;
        }

        $statusIds = $this->getOrderStatusIds(
            CommerceWidgets::$plugin->getSettings()->$statusSetting ?: []
        );

        if (empty($statusIds)) {
            return 0.0;
        }

        return $this->scalar(fn() => (clone $base)->orderStatusId($statusIds)->count());
    }

    /**
     * A stat is never worth breaking the whole bar over, so a failed aggregate reads as zero.
     */
    private function scalar(callable $query): float
    {
        try {
            return (float) $query();
        } catch (Exception $e) {
            return 0.0;
        }
    }

    /**
     * The currency of the store the index is showing, so money stats are labelled correctly in a
     * multi-store setup. Null when it can't be determined, in which case values stay unadorned.
     */
    private function getCurrencyCode(OrderQuery $query): ?string
    {
        try {
            $stores = Commerce::getInstance()->getStores();
            $store = isset($query->storeId) ? $stores->getStoreById((int) $query->storeId) : null;

            return ($store ?? $stores->getCurrentStore())?->getCurrency()?->getCode();
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
