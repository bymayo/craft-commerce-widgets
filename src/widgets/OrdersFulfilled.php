<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;

class OrdersFulfilled extends BaseOrderStatusWidget
{

    // Public Properties
    // =========================================================================

    public static $displayName = 'Orders Fulfilled';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Orders Fulfilled');
    }

    public static function statusSetting(): string
    {
        return 'orderStatusesFulfilled';
    }

    // Public Methods
    // =========================================================================

    public function getTitle(): ?string
    {
        return 'Orders Fulfilled';
    }

}
