<?php
/**
 * Commerce Widgets for Craft CMS 3.x
 *
 * @author    ByMayo
 * @link      https://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 *
 */

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\base\Widget;
use craft\helpers\StringHelper;
use craft\db\Query;

class ProductsTop extends Widget
{

    // Public Properties
    // =========================================================================

    public $limit;
    public $orderBy;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::getInstance()->name . ' - ' . Craft::t('commerce-widgets', 'Top Products');
    }

    public static function iconPath()
    {
        return Craft::getAlias("@bymayo/commercewidgets/icon-mask.svg");
    }

    public static function maxColspan()
    {
        return null;
    }

    // Custom Public Methods
    // =========================================================================

    public function getProducts()
    {

      $query = (
         new Query()
         )
         ->select(
            [
               'variants.productId as id',
               'variants.sku as sku',
               'SUM(items.total) as totalRevenue',
               'count(*) as totalOrdered',
            ]
         )
         ->from(['items' => '{{%commerce_lineitems}}'])
         ->join(
            'LEFT JOIN', '{{%commerce_purchasables}} purchasables', 'purchasables.id = items.purchasableId'
         )
         ->join(
            'LEFT JOIN', '{{%commerce_variants}} variants', 'variants.id = purchasables.id'
         )
         ->groupBy('items.purchasableId')
         ->orderBy($this->orderBy . ' desc')
         ->limit($this->limit);

      $result = $query->cache(CommerceWidgets::$plugin->getSettings()->cacheDuration)->all();

      return $result;

   }

    // Public Methods
    // =========================================================================

    public function getTitle(): string
    {
      return 'Top Products';
    }

    public function rules()
    {
        $rules = parent::rules();

        $rules = array_merge(
            $rules,
            [
                ['orderBy', 'string'],
                ['limit', 'integer'],
                ['limit', 'default', 'value' => 5],
                ['orderBy', 'default', 'value' => 'totalRevenue']
            ]
        );

        return $rules;
    }

    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/settings',
            [
                'widget' => $this
            ]
        );
    }

    public function getBodyHtml()
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
               'widgetId' => $this->id,
               'products' => $this->getProducts()
            ]
        );
    }

}
