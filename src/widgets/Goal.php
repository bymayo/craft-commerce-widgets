<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\base\Widget;
use craft\helpers\StringHelper;
use craft\db\Query;

class Goal extends Widget
{

    // Public Properties
    // =========================================================================

    public $type;
    public $targetValue;
    public $targetDuration;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
      return CommerceWidgets::getInstance()->name . ' - ' . Craft::t('commerce-widgets', 'Goal');
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

         switch ($this->targetDuration) {
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
         }

         $result = $query->cache(CommerceWidgets::$plugin->getSettings()->cacheDuration)->one();

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
      return StringHelper::titleize($this->type) . ' Goal';
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
                ['targetDuration', 'default', 'value' => 'monthly']
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
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
                'widgetId' => $this->id,
                'type' => $this->type,
                'targetValue' => $this->targetValue,
                'targetDuration' => $this->targetDuration,
                'total' => $this->getTotals()
            ]
        );
    }

}
