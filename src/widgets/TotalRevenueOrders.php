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
use craft\helpers\DateTimeHelper;
use craft\i18n\Formatter;
use craft\i18n\Locale;
use craft\db\Query;
use craft\records\Session;

class TotalRevenueOrders extends Widget
{

    // Public Properties
    // =========================================================================

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
      return CommerceWidgets::getInstance()->name . ' - ' . Craft::t('commerce-widgets', 'Total Revenue & Orders');
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

    public function getTimeFrames()
    {

      return array(
         array(
            'label' => 'Today',
            'date' => date('d M Y', strtotime('monday this week'))
         ),
         array(
            'label' => 'Week',
            'date' => date('d M Y', strtotime('monday this week')) . ' - ' . date('d M Y', strtotime('sunday this week'))
         ),
         array(
            'label' => 'Month',
            'date' => date('M')
         ),
         array(
            'label' => 'Year',
            'date' => date('Y')
         ),
         array(
            'label' => 'All',
            'date' => '∞'
         )
      );

   }

   public function getRevenueOrdersRow($timeFrame)
   {

      try {

         $query = (
            new Query()
            )
            ->select(
               [
                  'COALESCE(sum(orders.totalPrice), 0) as totalRevenue',
                  'COALESCE(count(orders.id), 0) as totalOrders'
               ]
            )
            ->from(['orders' => 'commerce_orders'])
            ->where(
               [
                  'orders.isCompleted' => 1
               ]
            );

         switch ($timeFrame['label']) {
            case "Today":
               $query->andWhere(
                  [
                     'DATE_FORMAT(orders.dateCreated, "%Y-%m-%d")' => date('Y-m-d')
                  ]
               );
               break;
            case "Week":
               $query->andWhere(
                  [
                     'WEEK(orders.dateCreated)' => date('W'),
                     'YEAR(orders.dateCreated)' => date('Y')
                  ]
               );
               break;
            case "Month":
               $query->andWhere(
                  [
                     'MONTH(orders.dateCreated)' => date('M'),
                     'YEAR(orders.dateCreated)' => date('Y')
                  ]
               );
               break;
            case "Year":
               $query->andWhere(
                  [
                     'YEAR(orders.dateCreated)' => date('Y')
                  ]
               );
               break;
         }

         $result = $query->one();

         return $result;

      }
      catch (Exception $e) {
         $result = null;
      }

   }

   public function getRevenueOrders()
   {

      $data = array();

      foreach ($this->getTimeFrames() as $timeFrame) {
         array_push($data, $this->getRevenueOrdersRow($timeFrame));
      }

      return $data;

   }

    // Public Methods
    // =========================================================================

    public function getTitle(): string
    {
      return 'Total Revenue & Orders';
    }

    public function getBodyHtml()
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
                'revenueOrders' => $this->getRevenueOrders(),
                'timeFrames' => $this->getTimeFrames()
            ]
        );
    }

}
