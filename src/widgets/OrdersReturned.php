<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;

class OrdersReturned extends BaseOrderStatusWidget
{

    // Public Properties
    // =========================================================================

    public static $displayName = 'Orders Returned';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Orders Returned');
    }

    public static function statusSetting(): string
    {
        return 'orderStatusesReturned';
    }

    public static function invertSentiment(): bool
    {
        return true;
    }

    // Public Methods
    // =========================================================================

    public function getTitle(): ?string
    {
        return 'Orders Returned';
    }

}
