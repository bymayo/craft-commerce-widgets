<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;

class OrdersShipped extends BaseOrderStatusWidget
{

    // Public Properties
    // =========================================================================

    public static $displayName = 'Orders Shipped';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Orders Shipped');
    }

    public static function statusSetting(): string
    {
        return 'orderStatusesShipped';
    }

    // Public Methods
    // =========================================================================

    public function getTitle(): ?string
    {
        return 'Orders Shipped';
    }

}
