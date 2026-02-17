<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\App;
use craft\helpers\StringHelper;

class OrdersMap extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public static $displayName = 'Locations';
    public $targetDuration = 'default';
    public $showMap = true;
    public $statType = 'orderCount';
    public $limit = 3;
    public $showBarChart = true;

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

    private const STAT_TYPE_LABELS = [
        'orderCount' => 'Orders',
        'customerCount' => 'Customers',
    ];

    public function getTitle(): ?string
    {
      return (self::STAT_TYPE_LABELS[$this->statType] ?? 'Orders') . ' Per Location';
    }

    public function getSubtitle(): ?string
    {
        return CommerceWidgets::$plugin->helpers->getTargetDurationLabel($this->targetDuration);
    }

    public function getBodyHtml(): ?string
    {
        $showMap = (bool) $this->showMap;
        $settings = CommerceWidgets::$plugin->getSettings();
        $mapboxAccessToken = $showMap ? App::parseEnv($settings->mapboxAccessToken) : null;

        if ($showMap && empty($mapboxAccessToken)) {
            return '<div class="cw:text-gray-400 cw:text-sm cw:py-4">Add a Mapbox Access Token in the plugin settings to use this widget.</div>';
        }

        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
                'widgetId' => $this->id,
                'showMap' => $showMap,
                'statType' => $this->statType,
                'mapboxAccessToken' => $mapboxAccessToken,
                'limit' => (int) $this->limit,
                'showBarChart' => (bool) $this->showBarChart,
                'ordersByCountry' => CommerceWidgets::$plugin->orders->getOrdersByCountry($this->targetDuration, $this->statType),
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
