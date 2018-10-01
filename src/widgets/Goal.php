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

    public static function maxColspan()
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
                  'count(*) as totalOrders',
                  'COALESCE(SUM(orders.totalPrice),0) as totalRevenue'
               ]
            )
            ->from(['orders' => 'commerce_orders'])
            ->where(
               [
                  'isCompleted' => 1,
                  'month(dateOrdered)' => 'month(current_date())',
                  'year(dateOrdered)' => 'year(current_date())'
               ]
            );

         $command = $query->createCommand();
         $result = $command->queryAll();

      }
      catch (Exception $e) {
         $result = null;
      }

      return ($this->type === 'orders') ? $result[0]['totalOrders'] : $result[0]['totalRevenue'];

   }

    // Public Methods
    // =========================================================================

    public function getTitle(): string
    {
      return 'Goal - ' . StringHelper::titleize($this->type);
    }

    public function rules()
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
                'type' => $this->type,
                'targetValue' => $this->targetValue,
                'targetDuration' => $this->targetDuration,
                'total' => $this->getTotals()
            ]
        );
    }

}
