<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\records\DashboardWidget;
use bymayo\commercewidgets\widgets\CartAbandonment;
use bymayo\commercewidgets\widgets\ConversionRate;
use bymayo\commercewidgets\widgets\Goal;
use bymayo\commercewidgets\widgets\OrdersRecent;
use bymayo\commercewidgets\widgets\ProductsRecent;
use bymayo\commercewidgets\widgets\ProductsTop;
use bymayo\commercewidgets\widgets\SubscriptionPlans;
use bymayo\commercewidgets\widgets\TopCustomers;
use bymayo\commercewidgets\widgets\TotalRevenueOrders;

use craft\base\Component;

class DashboardWidgets extends Component
{

    public function getAvailableWidgetTypes(): array
    {
        return [
            ['class' => TotalRevenueOrders::class, 'name' => 'Total Revenue & Orders'],
            ['class' => TopCustomers::class, 'name' => 'Top Customers'],
            ['class' => OrdersRecent::class, 'name' => 'Recent Orders'],
            ['class' => Goal::class, 'name' => 'Goal'],
            ['class' => CartAbandonment::class, 'name' => 'Cart Abandonment'],
            ['class' => ConversionRate::class, 'name' => 'Conversion Rate'],
            ['class' => ProductsTop::class, 'name' => 'Top Products'],
            ['class' => ProductsRecent::class, 'name' => 'Recent Products'],
            ['class' => SubscriptionPlans::class, 'name' => 'Subscription Plans'],
        ];
    }

    public function getWidgetsForPage(int $pageId, int $userId): array
    {
        return DashboardWidget::find()
            ->where(['pageId' => $pageId, 'userId' => $userId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();
    }

    public function getWidgetsForUser(int $userId): array
    {
        return DashboardWidget::find()
            ->where(['userId' => $userId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();
    }

    public function addWidget(int $userId, string $type, int $colspan = 1, array $settings = [], ?int $pageId = null): DashboardWidget
    {
        $maxSort = DashboardWidget::find()
            ->where(['pageId' => $pageId, 'userId' => $userId])
            ->max('sortOrder');

        $record = new DashboardWidget();
        $record->userId = $userId;
        $record->pageId = $pageId;
        $record->type = $type;
        $record->colspan = $colspan;
        $record->sortOrder = ($maxSort ?? 0) + 1;
        $record->settings = !empty($settings) ? json_encode($settings) : null;
        $record->save();

        return $record;
    }

    public function removeWidget(int $widgetId, int $userId): bool
    {
        $record = DashboardWidget::find()
            ->where(['id' => $widgetId, 'userId' => $userId])
            ->one();

        if (!$record) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function reorderWidgets(array $widgetIds, int $userId): bool
    {
        foreach ($widgetIds as $order => $widgetId) {
            DashboardWidget::updateAll(
                ['sortOrder' => $order + 1],
                ['id' => $widgetId, 'userId' => $userId]
            );
        }

        return true;
    }

    public function resizeWidget(int $widgetId, int $userId, int $colspan): bool
    {
        $updated = DashboardWidget::updateAll(
            ['colspan' => $colspan],
            ['id' => $widgetId, 'userId' => $userId]
        );

        return $updated > 0;
    }

    public function saveWidgetSettings(int $widgetId, int $userId, int $colspan, array $settings): bool
    {
        $record = DashboardWidget::findOne([
            'id' => $widgetId,
            'userId' => $userId,
        ]);

        if (!$record) {
            return false;
        }

        $record->colspan = $colspan;
        $record->settings = !empty($settings) ? json_encode($settings) : null;

        return $record->save();
    }

    public function seedDefaultWidgets(int $userId, int $pageId): void
    {
        $this->addWidget($userId, TotalRevenueOrders::class, 2, [], $pageId);
        $this->addWidget($userId, OrdersRecent::class, 1, [], $pageId);
        $this->addWidget($userId, TopCustomers::class, 1, [], $pageId);
    }

}
