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

class TopCustomers extends Widget
{

    // Public Properties
    // =========================================================================

    public $includeGuests;
    public $groupBy;
    public $orderBy;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::getInstance()->name . ' - ' . Craft::t('commerce-widgets', 'Top Customers');
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

    public function customers()
    {

      try {

         $query = (
            new Query()
            )
            ->select(
               [
                  'count(*) as totalOrders',
                  'SUM(orders.totalPrice) as totalRevenue',
                  'orders.email',
                  'orders.customerId'
               ]
            )
            ->from(['orders' => 'commerce_orders'])
            ->where(['orders.isCompleted' => 1])
            ->orderBy($this->orderBy . ' desc')
            ->groupBy(['orders.email'])
            ->limit(5);

         if($this->includeGuests == 'no')
         {
            $query
               ->join('INNER JOIN', 'commerce_customers customers', 'orders.customerId = customers.id')
               ->where(['not', ['customers.userId' => null]]);
         }

         $command = $query->createCommand();
         $result = $command->queryAll();

      }
      catch (Exception $e) {
         $result = [];
     }

      return $result;

   }

    // Public Methods
    // =========================================================================

    public function getTitle(): string
    {
      return 'Top Customers';
    }

    public function rules()
    {
        $rules = parent::rules();

        $rules = array_merge(
            $rules,
            [
                [['includeGuests', 'orderBy'], 'string'],
                ['includeGuests', 'default', 'value' => 'yes'],
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
                'widget' => $this,
                'includeGuests' => $this->includeGuests,
                'orderBy' => $this->orderBy
            ]
        );
    }

    public function getBodyHtml()
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/body',
            [
                'customers' => $this->customers()
            ]
        );
    }

}
