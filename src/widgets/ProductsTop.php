<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;
use craft\commerce\Plugin as CommercePlugin;

class ProductsTop extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public $orderStatusId;
    public $orderBy = 'totalRevenue';
    public $limit = 5;
    public $targetDuration = 'default';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Top Products');
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
      return 'Top Products';
    }

   public function getSubtitle(): ?string
   {
      return CommerceWidgets::$plugin->helpers->getTargetDurationLabel($this->targetDuration);
   }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules = array_merge(
            $rules,
            [
                ['orderBy', 'string'],
                [['limit', 'orderStatusId'], 'integer'],
                ['limit', 'default', 'value' => 5],
                ['orderBy', 'default', 'value' => 'totalRevenue'],
                ['orderStatusId', 'default', 'value' => null]
            ]
        );

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {

      return Craft::$app->getView()->renderTemplate(
         'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/settings',
         [
            'widget' => $this,
            'orderStatuses' => CommercePlugin::getInstance()->getOrderStatuses()->getAllOrderStatuses()
         ]
      );
    }

    public function getBodyHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
               'widgetId' => $this->id,
               'products' => CommerceWidgets::$plugin->products->getTopProducts($this->orderBy, (int) $this->limit, $this->orderStatusId ? (int) $this->orderStatusId : null, $this->targetDuration),
               'changeTooltip' => CommerceWidgets::$plugin->helpers->getChangeTooltip($this->targetDuration),
            ]
        );
    }

}
