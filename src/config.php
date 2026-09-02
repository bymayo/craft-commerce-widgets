<?php
return array(
    '*' => array(
        'pluginName' => 'Commerce Widgets',
        'cacheDuration' => 3600,
        'defaultTargetDuration' => 'yearly',
        'fiscalYearStartDay' => 1,
        'fiscalYearStartMonth' => 'april',
        'fiscalYearEndDay' => 31,
        'fiscalYearEndMonth' => 'march',
        'weekStart' => 'monday',
        'excludeEmailAddresses' => array(),
        'comparisonMode' => 'full',
        'enablePages' => false,
        'defaultPageWidgets' => array(),
        'mapboxAccessToken' => '',
        'enableOrdersAnalyticsBar' => true,
        'ordersAnalyticsBarStats' => array('orders', 'revenue', 'averageOrderValue', 'toFulfil', 'shipped', 'itemsOrdered'),
        'orderStatusesToFulfil' => array(),
        'orderStatusesShipped' => array(),
        'orderStatusesReturned' => array(),
        'enableProductsAnalyticsBar' => true,
        'productsAnalyticsBarStats' => array('products', 'outOfStock', 'lowStock', 'averagePrice', 'stockValue', 'onPromotion'),
        'lowStockThreshold' => 5
    )
);
