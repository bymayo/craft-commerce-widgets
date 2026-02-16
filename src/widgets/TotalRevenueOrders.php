<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\DateTimeHelper;
use craft\i18n\Formatter;
use craft\i18n\Locale;
use craft\db\Query;
use craft\records\Session;
use yii\caching\TagDependency;

use Exception;

class TotalRevenueOrders extends BaseWidget
{

    // Public Properties
    // =========================================================================

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
      return CommerceWidgets::getInstance()->name . ' - ' . Craft::t('commerce-widgets', 'Total Revenue & Orders');
    }

    public static function icon(): ?string
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
            'label' => 'Fiscal Year',
            'date' => date('d M Y', strtotime('first day of April last year')) . ' - ' . date('d M Y', strtotime('last day of April this year'))
         ),
         array(
            'label' => 'All Time',
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
            case "Fiscal Year":
               $query->andWhere(
                  [
                     'between', 'orders.dateCreated', date('Y-m-d', strtotime('first day of April last year')), date('Y-m-d', strtotime('last day of April this year'))
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

         $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
         $dependency = new TagDependency(['tags' => 'commerce-widgets']);
         $result = $query->cache($cacheDuration, $dependency)->one();

         return $result;

      }
      catch (Exception $e) {
         $result = null;
      }

   }

   public function getPreviousRevenueOrdersRow($timeFrame)
   {

      if ($timeFrame['label'] === 'All Time') {
         return null;
      }

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
                     'DATE_FORMAT(orders.dateCreated, "%Y-%m-%d")' => date('Y-m-d', strtotime('-1 day'))
                  ]
               );
               break;
            case "Week":
               $query->andWhere(
                  [
                     'between', 'orders.dateCreated', date('Y-m-d', strtotime('monday last week')), date('Y-m-d', strtotime('sunday last week'))
                  ]
               );
               break;
            case "Month":
               $query->andWhere(
                  [
                     'between', 'orders.dateCreated', date('Y-m-d', strtotime('first day of last month')), date('Y-m-d', strtotime('last day of last month'))
                  ]
               );
               break;
            case "Fiscal Year":
               $query->andWhere(
                  [
                     'between', 'orders.dateCreated', date('Y-m-d', strtotime('-1 year', strtotime('first day of April last year'))), date('Y-m-d', strtotime('-1 year', strtotime('last day of April this year')))
                  ]
               );
               break;
            case "Year":
               $query->andWhere(
                  [
                     'YEAR(orders.dateCreated)' => date('Y', strtotime('-1 year'))
                  ]
               );
               break;
         }

         $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
         $dependency = new TagDependency(['tags' => 'commerce-widgets']);
         $result = $query->cache($cacheDuration, $dependency)->one();

         return $result;

      }
      catch (Exception $e) {
         return null;
      }

   }

   public function getChangeIndicator($current, $previous)
   {

      $current = (float) $current;
      $previous = (float) $previous;

      if ($previous == 0) {
         return [
            'percentage' => null,
            'direction' => $current > 0 ? 'up' : 'neutral'
         ];
      }

      $change = (($current - $previous) / $previous) * 100;
      $percentage = round(abs($change), 1) . '%';

      if ($change > 0) {
         $direction = 'up';
      } elseif ($change < 0) {
         $direction = 'down';
      } else {
         $direction = 'neutral';
      }

      return [
         'percentage' => $percentage,
         'direction' => $direction
      ];

   }

   public function getRevenueOrders()
   {

      $data = array();

      foreach ($this->getTimeFrames() as $timeFrame) {
         $current = $this->getRevenueOrdersRow($timeFrame);
         $previous = $this->getPreviousRevenueOrdersRow($timeFrame);

         $row = $current ?? ['totalRevenue' => 0, 'totalOrders' => 0];

         if ($current && $previous) {
            $change = $this->getChangeIndicator($current['totalRevenue'], $previous['totalRevenue']);
            $row['changeIndicator'] = $change['percentage'];
            $row['changeDirection'] = $change['direction'];
         } else {
            $row['changeIndicator'] = null;
            $row['changeDirection'] = 'neutral';
         }

         array_push($data, $row);
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
