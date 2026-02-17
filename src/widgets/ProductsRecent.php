<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;
use craft\commerce\Plugin as CommercePlugin;

use Exception;

class ProductsRecent extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public $productTypeId;
    public $limit = 5;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Recent Products');
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
        if ($this->productTypeId) {
            $productType = CommercePlugin::getInstance()->getProductTypes()->getProductTypeById((int) $this->productTypeId);
            if ($productType) {
                return 'Recent Products - ' . $productType->name;
            }
        }

        return 'Recent Products';
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules = array_merge(
            $rules,
            [
                [['limit', 'productTypeId'], 'integer'],
                ['limit', 'default', 'value' => 5],
                ['productTypeId', 'default', 'value' => null]
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
                'productTypes' => CommercePlugin::getInstance()->getProductTypes()->getAllProductTypes()
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
                'products' => CommerceWidgets::$plugin->products->getRecentProducts((int) $this->limit, $this->productTypeId ? (int) $this->productTypeId : null)
            ]
        );
    }

}
