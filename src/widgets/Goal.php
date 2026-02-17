<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;

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
      return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Goal');
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
      $result = CommerceWidgets::$plugin->orders->getOrderTotals($this->targetDuration);
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
            [
                [['type', 'targetDuration'], 'string'],
                ['targetValue', 'integer', 'min' => 1],
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
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

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
