<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;

class OrdersToFulfil extends BaseOrderStatusWidget
{

    // Public Properties
    // =========================================================================

    public static $displayName = 'Orders to Fulfil';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Orders to Fulfil');
    }

    public static function statusSetting(): string
    {
        return 'orderStatusesToFulfil';
    }

    // Public Methods
    // =========================================================================

    public function getTitle(): ?string
    {
        return 'Orders to Fulfil';
    }

}
