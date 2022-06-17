<?php

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

    public static function maxColspan(): ?int
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
            'date' => date('d M Y')
         ),
         array(
            'label' => 'Week',
            'date' => date('d M Y', strtotime('monday this week')) . ' - ' . date('d M Y', strtotime('sunday this week'))
         ),
         array(
            'label' => 'Month',
            'date' => date('M Y')
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
            ->from(['orders' => '{{%commerce_orders}}'])
            ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
            ->where(['orders.isCompleted' => 1])
            ->andWhere(['elements.dateDeleted' => null]);

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
                     'between', 'orders.dateCreated', date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))
                  ]
               );
               break;
            case "Month":
               $query->andWhere(
                  [
                     'between', 'orders.dateCreated', date('Y-m-d', strtotime('first day of this month')), date('Y-m-d', strtotime('last day of this month'))
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

         $result = $query->cache(CommerceWidgets::$plugin->getSettings()->cacheDuration)->one();

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
                'revenueOrders' => $this->getRevenueOrders(),
                'timeFrames' => $this->getTimeFrames()
            ]
        );
    }

}
