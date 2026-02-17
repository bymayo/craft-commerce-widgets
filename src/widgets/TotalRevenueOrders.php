<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;

class TotalRevenueOrders extends BaseWidget
{

    // Public Properties
    // =========================================================================

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
      return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Total Revenue & Orders');
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
      return 'Total Revenue & Orders';
    }

    public function getBodyHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
                'widgetId' => $this->id,
                'revenueOrders' => CommerceWidgets::$plugin->orders->getRevenueOrders(),
                'timeFrames' => CommerceWidgets::$plugin->orders->getTimeFrames()
            ]
        );
    }

}
