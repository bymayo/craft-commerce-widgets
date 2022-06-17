<?php

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
    public $orderBy;
    public $groupBy; // Remove
    public $limit;

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

    public static function maxColspan(): ?int
    {
        return null;
    }

    // Custom Public Methods
    // =========================================================================

    public function getCustomers()
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
            ->from(['orders' => '{{%commerce_orders}}'])
            ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
            ->where(['orders.isCompleted' => 1])
            ->andWhere(['elements.dateDeleted' => null])
            ->orderBy($this->orderBy . ' desc')
            ->groupBy(['orders.email', 'orders.customerId'])
            ->limit($this->limit);

        if(!empty(CommerceWidgets::$plugin->getSettings()->excludeEmailAddresses)) 
        {
            $query->andWhere(['not in', 'orders.email', CommerceWidgets::$plugin->getSettings()->excludeEmailAddresses]);
        }

         if($this->includeGuests == 'no')
         {
            $query
               ->join('INNER JOIN', '{{%commerce_customers}} customers', 'orders.customerId = customers.id')
               ->andWhere(['not', ['customers.userId' => null]]);
         }

         $command = $query->createCommand();
         $result = $command->cache(CommerceWidgets::$plugin->getSettings()->cacheDuration)->queryAll();

         return $result;

      }
      catch (Exception $e) {
         $result = [];
     }

   }

    // Public Methods
    // =========================================================================

    public function getTitle(): ?string
    {
      return 'Top Customers';
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules = array_merge(
            $rules,
            [
                [['includeGuests', 'orderBy'], 'string'],
                [['limit'], 'integer'],
                ['includeGuests', 'default', 'value' => 'yes'],
                ['orderBy', 'default', 'value' => 'totalRevenue'],
                ['limit', 'default', 'value' => 5]
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
                'customers' => $this->getCustomers()
            ]
        );
    }

}
