<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\base\Widget;
use craft\helpers\StringHelper;
use craft\db\Query;

use Exception;

class ConversionRate extends Widget
{

    // Public Properties
    // =========================================================================

    public static $displayName = 'Conversion Rate';
    public $targetDuration = 'monthly';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::getInstance()->name . ' - ' . self::$displayName;
    }

    public static function iconPath()
    {
        return Craft::getAlias("@bymayo/commercewidgets/icon-mask.svg");
    }

    public static function maxColspan(): ?int
    {
        return null;
    }

    // Custom Public Methods
    // =========================================================================

    // Public Methods
    // =========================================================================

    public function getTitle(): ?string
    {
      return self::$displayName;
    }

    public function getSubtitle(): ?string
    {
        return date('F Y');
    }

    public function getBodyHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            []
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
