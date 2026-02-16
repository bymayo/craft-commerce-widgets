<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;
use craft\db\Query;
use yii\caching\TagDependency;

use Exception;

class Goal extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public $type = 'orders';
    public $targetValue = 15;
    public $targetDuration = 'default';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
      return CommerceWidgets::getInstance()->name . ' - ' . Craft::t('commerce-widgets', 'Goal');
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

    public function getTotals()
    {

      try {

         $query = (
            new Query()
            )
            ->select(
               [
                  'COALESCE(count(*), 0) as totalOrders',
                  'COALESCE(SUM(orders.totalPaid),0) as totalRevenue'
               ]
            )
            ->from(['orders' => '{{%commerce_orders}}'])
            ->where(
               [
                  'orders.isCompleted' => 1,
               ]
            );

         $targetDuration = CommerceWidgets::$plugin->helpers->getTargetDuration($this->targetDuration);
         switch ($targetDuration) {
            case "weekly":
               $query
                  ->where(
                     [
                        'WEEK(orders.datePaid, 1)' => date('W'),
                        'YEAR(orders.datePaid)' => date('Y')
                     ]
                  );
               break;
            case "monthly":
               $query
                  ->where(
                     [
                        'MONTH(orders.datePaid)' => date('n'),
                        'YEAR(orders.datePaid)' => date('Y')
                     ]
                  );
               break;
            case "yearly":
               $query
                  ->where(
                     [
                        'YEAR(orders.datePaid)' => date('Y')
                     ]
                  );
               break;
            case "fiscalYear":
               $settings = CommerceWidgets::$plugin->getSettings();
               $startDay = (int) $settings->fiscalYearStartDay;
               $startMonth = $settings->fiscalYearStartMonth;
               $endDay = (int) $settings->fiscalYearEndDay;
               $endMonth = $settings->fiscalYearEndMonth;
               $fiscalMonth = date('n', strtotime("1 $startMonth"));
               $currentMonth = (int) date('n');
               $currentYear = (int) date('Y');

               if ($currentMonth > $fiscalMonth || ($currentMonth == $fiscalMonth && (int) date('j') >= $startDay)) {
                  $startYear = $currentYear;
               } else {
                  $startYear = $currentYear - 1;
               }
               $endYear = $startYear + 1;

               $startDate = date('Y-m-d', strtotime("$startDay $startMonth $startYear"));
               $endDate = date('Y-m-d', strtotime("$endDay $endMonth $endYear"));

               $query
                  ->where(
                     ['and',
                        ['>=', 'orders.datePaid', $startDate],
                        ['<=', 'orders.datePaid', $endDate . ' 23:59:59']
                     ]
                  );
               break;
            case "allTime":
               // No date filter — query all completed orders
               break;
         }

         $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
         $dependency = new TagDependency(['tags' => 'commerce-widgets']);
         $result = $query->cache($cacheDuration, $dependency)->one();

      }
      catch (Exception $e) {
         $result = null;
      }

      return ($this->type === 'orders') ? $result['totalOrders'] : $result['totalRevenue'];

   }

    // Public Methods
    // =========================================================================

    public function getTitle(): ?string
    {
      $targetDuration = CommerceWidgets::$plugin->helpers->getTargetDuration($this->targetDuration);
      $durationLabel = ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', $targetDuration));
      return $durationLabel . ' ' . StringHelper::titleize($this->type) . ' Goal';
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
            $rules,
            [
                [['type', 'targetDuration'], 'string'],
                ['targetValue', 'integer'],
                ['type', 'default', 'value' => 'orders'],
                ['targetValue', 'default', 'value' => 15],
                ['targetDuration', 'default', 'value' => 'default']
            ]
        );

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/settings',
            [
                'widget' => $this
            ]
        );
    }

    public function getBodyHtml(): ?string
    {

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
                'widgetId' => $this->id,
                'type' => $this->type,
                'targetValue' => $this->targetValue,
                'total' => $this->getTotals()
            ]
        );
    }

}
