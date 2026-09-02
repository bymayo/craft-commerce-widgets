<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;

/**
 * Shared behaviour for the widgets that count orders sitting in a mapped status bucket.
 *
 * They read the same status mappings as the orders analytics bar, so a store sets them up once under
 * the plugin's Statuses settings and both features follow.
 */
abstract class BaseOrderStatusWidget extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public $targetDuration = 'default';

    // Static Methods
    // =========================================================================

    /**
     * The plugin setting holding the order status handles this widget counts.
     */
    abstract public static function statusSetting(): string;

    /**
     * Whether a rise in this number is bad news, so the change indicator can be coloured accordingly.
     */
    public static function invertSentiment(): bool
    {
        return false;
    }

    public static function icon(): ?string
    {
        return Craft::getAlias("@bymayo/commercewidgets/icon-mask.svg");
    }

    public static function maxColspan(): ?int
    {
        return null;
    }

    // Public Methods
    // =========================================================================

    public function getSubtitle(): ?string
    {
        return CommerceWidgets::$plugin->helpers->getTargetDurationLabel($this->targetDuration);
    }

    public function getBodyHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/OrderStatusCount/body',
            array_merge(
                [
                    'widgetId' => $this->id,
                    'invertSentiment' => static::invertSentiment(),
                ],
                CommerceWidgets::$plugin->ordersAnalyticsBar->getStatusCountData(
                    static::statusSetting(),
                    $this->targetDuration
                )
            )
        );
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/OrderStatusCount/settings',
            [
                'widget' => $this
            ]
        );
    }

}
