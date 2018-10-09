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

    public function getMonthDateRange()
    {

         $currentMonth  = strtotime('next month');
         $monthArray = array();

         $totalMonths = 6;

         for ($i = $totalMonths; $i >= 1; $i--) {
            array_push(
               $monthArray,
               date('M', strtotime("-$i month", $currentMonth)
            )
         );

      }

      return $monthArray;

   }

    public function getTotalCarts($isCompleted)
    {

      $data = array();

      try {

         $query = (
            new Query()
            )
            ->select(
               [
                  'DATE_FORMAT(orders.dateCreated, "%b") AS month',
                  'COALESCE(COUNT(orders.id), 0) AS count'
               ]
            )
            ->from(['orders' => 'commerce_orders'])
            ->where(
               [
                  'between', 'orders.dateCreated', date('Y-m-d', strtotime('-5 months')), date('Y-m-d', strtotime('+1 day'))
               ]
            )
            ->andWhere(
               [
                  'orders.isCompleted' => $isCompleted
               ]
            )
            ->groupBy('month')
            ->orderBy('month');

         $command = $query->createCommand();
         $result = $command->queryAll();

         foreach ($this->getMonthDateRange() as $month) {
            $key = array_search($month, array_column($result, 'month'));
            if ($key !== '' and $key !== false) {
               array_push($data, ($result[$key]['month'] == $month ? $result[$key]['count'] : 0 ));
            }
            else {
               array_push($data, 0);
            }
         }

         return $data;

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
                  'COALESCE(sum(orders.totalPrice), 0) as totalPrice',
                  'COALESCE(count(orders.id), 0) as count'
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
               'dateRangeChart' => $this->getMonthDateRange(),
               'abandonedCartChart' => $this->getTotalCarts(0),
               'completedCartChart' => $this->getTotalCarts(1),
               'abandonedCartData' => $this->getCartTotalRevenue(0),
               'completedCartData' => $this->getCartTotalRevenue(1)
            ]
        );
    }

}
