<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\base\Widget;
use craft\helpers\StringHelper;
use craft\db\Query;
use craft\commerce\Plugin as CommercePlugin;

class ProductsTop extends Widget
{

    // Public Properties
    // =========================================================================

    public $orderStatusId;
    public $orderBy;
    public $limit = 5;

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

    public static function maxColspan(): ?int
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
         ->join(
            'LEFT JOIN', '{{%commerce_orders}} orders', 'orders.id = items.orderId'
         )
         ->join(
            'LEFT JOIN', '{{%elements}} elements', 'elements.id = variants.productId'
         )
         ->where(['elements.dateDeleted' => null])
         ->andWhere(['orders.isCompleted' => 1])
         ->groupBy(['items.purchasableId'])
         ->orderBy($this->orderBy . ' desc')
         ->limit($this->limit);

      if($this->orderStatusId != null)
      {
         $query
         ->andWhere(['orders.orderStatusId' => $this->orderStatusId])
         ->andWhere(['not', ['variants.productId' => null]]);
      }

      $result = $query->cache(CommerceWidgets::$plugin->getSettings()->cacheDuration)->all();

      return $result;

   }

    // Public Methods
    // =========================================================================

    public function getTitle(): ?string
    {
      return 'Top Products';
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

      // Credit - craft/vendor/craftcms/commerce/src/widgets/Orders.php
      $id = StringHelper::basename(get_class($this)) . '-' . StringHelper::randomString();
      $namespaceId = Craft::$app->getView()->namespaceInputId($id);

      Craft::$app->getView()->registerJs("new CommerceWidgets.OrderStatuses('" . $namespaceId . "');");

      return Craft::$app->getView()->renderTemplate(
         'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/settings',
         [
            'id' => $id,
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
               'products' => $this->getProducts()
            ]
        );
    }

}
