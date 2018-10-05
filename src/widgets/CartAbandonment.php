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

class CartAbandonment extends Widget
{

    // Public Properties
    // =========================================================================

    public $limit = 10;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::getInstance()->name . ' - ' . Craft::t('commerce-widgets', 'Cart Abandonment');
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

    public function getTotalCarts($isCompleted)
    {


      // SELECT
      //     DATE_FORMAT(orders.dateCreated,'%Y-%m') AS date,
      //     IFNULL(COUNT(orders.id), 0) AS count
      // FROM commerce_orders orders
      // WHERE orders.dateCreated BETWEEN '2018-9-01 00:00:00' AND CURRENT_DATE
      // AND isCompleted = 1
      // GROUP BY date
      // ORDER BY date

      try {

         $query = (
            new Query()
            )
            ->select(
               [
                  'DATE_FORMAT(orders.dateCreated, "%Y-%m") AS date',
                  'COALESCE(COUNT(orders.id), 0) AS count'
               ]
            )
            ->from(['orders' => 'commerce_orders'])
            ->where(
               [
                  ['orders.isCompleted' => '1'],
                  ['between', 'orders.dateCreated', '2018-08-01', 'CURRENT_DATE']
               ]
            )
            ->groupBy('date')
            ->orderBy('date');

         $command = $query->createCommand();
         $result = $command->queryAll();

         return $result;

      }
      catch (Exception $e) {
         $result = null;
      }

   }

   public function getCartTotalRevenue($isCompleted)
   {

      try {

         $query = (
            new Query()
            )
            ->select(
               [
                  'sum(orders.totalPrice) as total'
               ]
            )
            ->from(['orders' => 'commerce_orders'])
            ->where(
               [
                  'orders.isCompleted' => $isCompleted,
                  'MONTH(orders.dateCreated)' => date('n')
               ]
            );

         $result = $query->one();

         return $result;

      }
      catch (Exception $e) {
         $result = null;
      }

   }

    // Public Methods
    // =========================================================================

    public function getTitle(): string
    {
      return 'Cart Abandonment - ' . date('F Y');
    }

    public function getBodyHtml()
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
               'abandonedCartTotal' => $this->getTotalCarts(0),
               'completedCartTotal' => $this->getTotalCarts(0),
               'abandonedCartPrice' => $this->getCartTotalRevenue(0),
               'completedCartPrice' => $this->getCartTotalRevenue(1)
            ]
        );
    }

}
