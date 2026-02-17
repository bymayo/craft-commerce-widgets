<?php

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use Craft;
use craft\helpers\StringHelper;
use craft\commerce\Plugin as CommercePlugin;

use Exception;

class OrdersRecent extends BaseWidget
{

    // Public Properties
    // =========================================================================

    public $orderStatusId;
    public $limit = 5;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return CommerceWidgets::$plugin->helpers->getPluginName() . ' - ' . Craft::t('commerce-widgets', 'Recent Orders');
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
        if ($this->orderStatusId) {
            $status = CommercePlugin::getInstance()->getOrderStatuses()->getOrderStatusById((int) $this->orderStatusId);
            if ($status) {
                return 'Recent Orders - ' . $status->name;
            }
        }

        return 'Recent Orders';
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules = array_merge(
            $rules,
            [
                [['limit', 'orderStatusId'], 'integer'],
                ['limit', 'default', 'value' => 5],
                ['orderStatusId', 'default', 'value' => null]
            ]
        );

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/widgets/' . StringHelper::basename(get_class($this)) . '/settings',
            [
                'widget' => $this,
                'orderStatuses' => CommercePlugin::getInstance()->getOrderStatuses()->getAllOrderStatuses()
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
                'orders' => CommerceWidgets::$plugin->orders->getRecentOrders((int) $this->limit, $this->orderStatusId ? (int) $this->orderStatusId : null)
            ]
        );
    }

}
