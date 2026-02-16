<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;
use craft\db\Query;
use yii\caching\TagDependency;

use Exception;

class SubscriptionPlans extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public $limit = 5;
    public $orderBy = 'dateCreated desc';

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::getInstance()->name . ' - ' . Craft::t('commerce-widgets', 'Subscription Plans');
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

    public function getSubscriptionPlans()
    {

      try {

         $query = (
            new Query()
            )
            ->select(
               [
                  'plans.*',
                  'COUNT(subscriptions.planId) as activeSubscriptions'
               ]
            )
            ->from(
               [
                  'plans' => '{{%commerce_plans}}'
               ]
            )
            ->join(
               'LEFT JOIN', '{{%commerce_subscriptions}} subscriptions', 'subscriptions.planId = plans.id'
            )
            ->where(
               [
                  'plans.isArchived' => 0
               ]
            )
            ->groupBy(['plans.id'])
            ->orderBy($this->orderBy)
            ->limit($this->limit);

         $command = $query->createCommand();
         $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
         $dependency = new TagDependency(['tags' => 'commerce-widgets']);
         $result = $command->cache($cacheDuration, $dependency)->queryAll();

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
      return 'Subscription Plans';
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules = array_merge(
            $rules,
            [
                ['limit', 'integer'],
                ['limit', 'default', 'value' => 5],
                ['orderBy', 'string'],
                ['orderBy', 'default', 'value' => 'dateCreated desc']
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
                'plans' => $this->getSubscriptionPlans()
            ]
        );
    }

}
