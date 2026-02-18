<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;

class TopCustomers extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public $includeGuests = 1;
    public $orderBy = 'totalRevenue';
    public $limit = 5;
    public $targetDuration = 'default';
    public $excludeAdmins = false;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Top Customers');
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
      return 'Top Customers';
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
                [['orderBy'], 'string'],
                [['includeGuests'], 'boolean'],
                [['limit'], 'integer'],
                ['includeGuests', 'default', 'value' => 1],
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
                'customers' => CommerceWidgets::$plugin->customers->getTopCustomers($this->orderBy, (int) $this->limit, (bool) $this->includeGuests, $this->targetDuration, (bool) $this->excludeAdmins),
                'changeTooltip' => CommerceWidgets::$plugin->helpers->getChangeTooltip($this->targetDuration),
            ]
        );
    }

}
