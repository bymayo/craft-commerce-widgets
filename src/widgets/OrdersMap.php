<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;

class OrdersMap extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public static $displayName = 'Order Countries';
    public $targetDuration = 'default';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . self::$displayName;
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

    public function getTitle(): ?string
    {
      return self::$displayName;
    }

    public function getSubtitle(): ?string
    {
        return CommerceWidgets::$plugin->helpers->getTargetDurationLabel($this->targetDuration);
    }

    public function getBodyHtml(): ?string
    {
        $settings = CommerceWidgets::$plugin->getSettings();

        $mapboxAccessToken = Craft::parseEnv($settings->mapboxAccessToken);

        if (empty($mapboxAccessToken)) {
            return '<div class="cw:text-gray-400 cw:text-sm cw:py-4">Add a Mapbox Access Token in the plugin settings to use this widget.</div>';
        }

        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
                'widgetId' => $this->id,
                'mapboxAccessToken' => $mapboxAccessToken,
                'ordersByCountry' => CommerceWidgets::$plugin->orders->getOrdersByCountry($this->targetDuration),
            ]
        );
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/settings',
            [
                'widget' => $this
            ]
        );
    }

}
