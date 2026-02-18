<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;

class CartAbandonment extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public static $displayName = 'Cart Abandonment';
    public $targetDuration = 'default';
    public $previousAmount = 4;
    public $graphStep = 50;
    public $graphStyle = 'detailed';

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
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            array_merge(
               [
                   'widgetId' => $this->id,
                   'graphStep' => $this->graphStep,
                   'graphStyle' => $this->graphStyle,
               ],
               CommerceWidgets::$plugin->carts->getCartAnalytics($this->targetDuration, (int) $this->previousAmount)
            )
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
