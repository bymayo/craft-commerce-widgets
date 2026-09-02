<?php

namespace bymayo\commercewidgets\models;

use bymayo\commercewidgets\services\OrdersAnalyticsBar;
use bymayo\commercewidgets\services\ProductsAnalyticsBar;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $pluginName = 'Commerce Widgets';
    public $cacheDuration = 3600;
    public $defaultTargetDuration = 'yearly';
    public $fiscalYearStartDay = 1;
    public $fiscalYearStartMonth = 'april';
    public $fiscalYearEndDay = 31;
    public $fiscalYearEndMonth = 'march';
    public $weekStart = 'monday';
    public $excludeEmailAddresses = array();
    public $enablePages = false;
    public $defaultPageWidgets = [];
    public $comparisonMode = 'full';
    public $mapboxAccessToken = '';
    public $enableOrdersAnalyticsBar = true;
    public $ordersAnalyticsBarStats = ['orders', 'revenue', 'averageOrderValue', 'toFulfil', 'shipped', 'itemsOrdered'];
    public $orderStatusesToFulfil = array();
    public $orderStatusesFulfilled = array();
    public $orderStatusesShipped = array();
    public $orderStatusesReturned = array();
    public $enableProductsAnalyticsBar = true;
    public $productsAnalyticsBarStats = ['products', 'outOfStock', 'lowStock', 'averagePrice', 'stockValue', 'onPromotion'];
    public $lowStockThreshold = 5;

    // Public Methods
    // =========================================================================

    public function beforeValidate(): bool
    {
        if (is_string($this->excludeEmailAddresses)) {
            $this->excludeEmailAddresses = array_filter(
                array_map('trim', explode("\n", $this->excludeEmailAddresses))
            );
        }

        $this->cacheDuration = (int) $this->cacheDuration;

        foreach (['ordersAnalyticsBarStats', 'productsAnalyticsBarStats', 'orderStatusesToFulfil', 'orderStatusesFulfilled', 'orderStatusesShipped', 'orderStatusesReturned'] as $attribute) {
            if (!is_array($this->$attribute)) {
                $this->$attribute = $this->$attribute === null || $this->$attribute === '' ? [] : [$this->$attribute];
            }

            $this->$attribute = array_values(array_filter($this->$attribute, fn($value) => $value !== '' && $value !== null));
        }

        $this->ordersAnalyticsBarStats = array_slice($this->ordersAnalyticsBarStats, 0, OrdersAnalyticsBar::MAX_STATS);
        $this->productsAnalyticsBarStats = array_slice($this->productsAnalyticsBarStats, 0, ProductsAnalyticsBar::MAX_STATS);
        $this->lowStockThreshold = max(0, (int) $this->lowStockThreshold);

        return parent::beforeValidate();
    }

    public function rules(): array
    {
        return [
            [['cacheDuration'], 'integer'],
            [['defaultTargetDuration'], 'string'],
            [['fiscalYearStartDay', 'fiscalYearEndDay'], 'integer'],
            [['fiscalYearStartMonth', 'fiscalYearEndMonth'], 'string'],
            [['weekStart'], 'string'],
            [['excludeEmailAddresses'], 'safe'],
            [['enablePages'], 'boolean'],
            [['defaultPageWidgets'], 'safe'],
            [['comparisonMode'], 'string'],
            [['mapboxAccessToken'], 'string'],
            [['enableOrdersAnalyticsBar'], 'boolean'],
            [['ordersAnalyticsBarStats'], 'safe'],
            [['enableProductsAnalyticsBar'], 'boolean'],
            [['productsAnalyticsBarStats'], 'safe'],
            [['lowStockThreshold'], 'integer'],
            [['orderStatusesToFulfil', 'orderStatusesFulfilled', 'orderStatusesShipped', 'orderStatusesReturned'], 'safe']
        ];
    }
}
