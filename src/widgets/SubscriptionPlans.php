<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;

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
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Subscription Plans');
    }

    public static function icon(): ?string
    {
        return Craft::getAlias("@bymayo/commercewidgets/icon-mask.svg");
    }

    public static function maxColspan(): ?int
    {
        return null;
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
                'plans' => CommerceWidgets::$plugin->subscriptions->getSubscriptionPlans($this->orderBy, (int) $this->limit)
            ]
        );
    }

}
