<img src="https://raw.githubusercontent.com/bymayo/commerce-widgets/craft-5/resources/icon.png" width="60">

# Commerce Widgets for Craft CMS 5

Commerce Widgets is a Craft CMS plugin that gives you insightful dashboard widgets for your Craft Commerce store. See a better overview of your store's performance, track abandoned carts, set revenue targets, identify top customers and products, and more.

<img src="https://raw.githubusercontent.com/bymayo/commerce-widgets/craft-5/resources/screenshot.jpg" width="850">

## Features

- **Total Revenue & Orders** - Revenue and order overview by day, week, month, year, fiscal year and all time with change indicators
- **Cart Abandonment** - Track abandoned vs completed carts with revenue impact and trend charts
- **Conversion Rate** - Funnel visualisation showing Added to Cart, Checkout and Completed stages with conversion percentages
- **Goal** - Set revenue or order targets and track progress by week, month, year or fiscal year
- **Top Products** - See your best-selling products ranked by revenue or order count with thumbnails
- **Top Customers** - See your highest-value customers ranked by revenue or order count with country flags
- **New vs Returning Customers** - Line chart tracking new and returning customers over time with revenue breakdowns
- **Recent Orders** - View your latest completed orders with optional order status filtering
- **Recent Products** - View products recently added to your store with optional product type filtering
- **Locations** - Interactive 3D Mapbox globe showing order/customer locations by country with top countries bar chart
- **Subscription Plans** - Overview of your subscription plans with active, cancelled and expired counts
- **Custom Pages** - Create multiple dashboard pages with drag-and-drop widget management per user
- **Permissions** - Control access to widgets and page management with granular user permissions
- **Caching** - All widget data is cached with configurable duration for performance on large stores

## Install

- Install with Composer via `composer require bymayo/commerce-widgets` from your project directory
- Enable / Install the plugin in the Craft Control Panel under `Settings > Plugins`

You can also install the plugin via the Plugin Store in the Craft Admin CP by searching for `Commerce Widgets`.

## Requirements

- Craft CMS 5.x
- Craft Commerce 5.x
- PHP 8.2+
- MySQL or PostgreSQL

## Product Thumbnails

The Top Products widget supports displaying product thumbnails. To enable this, configure a **Thumbnail Source** on your product type:

1. Go to **Commerce > System Settings > Product Types**
2. Edit your product type
3. In the field layout designer, find your image/asset field
4. Click the gear icon on the field and enable **Use as thumbnail**

Once configured, the Top Products widget will display the product image.

## Config File

You can override plugin settings by creating a `config/commerce-widgets.php` file in your Craft project. The contents of this file will get merged with the plugin defaults, so you only need to specify values for the settings you want to override.

```php
<?php

return [
    'pluginName' => 'Commerce Widgets',
    'cacheDuration' => 3600,
    'defaultTargetDuration' => 'yearly',
    'fiscalYearStartDay' => 1,
    'fiscalYearStartMonth' => 'april',
    'fiscalYearEndDay' => 31,
    'fiscalYearEndMonth' => 'march',
    'weekStart' => 'monday',
    'excludeEmailAddresses' => [],
    'comparisonMode' => 'full',
    'enablePages' => false,
    'defaultPageWidgets' => [],
    'mapboxAccessToken' => '',
];
```

| Setting | Default | Description |
|---|---|---|
| `pluginName` | `Commerce Widgets` | Custom name displayed across all widgets, permissions and cache labels |
| `cacheDuration` | `3600` | How long (in seconds) widget data is cached. Set to `0` to disable |
| `defaultTargetDuration` | `yearly` | Default time period for widgets when set to "Plugin Default". Options: `daily`, `weekly`, `monthly`, `yearly`, `fiscalYear`, `allTime` |
| `fiscalYearStartDay` | `1` | Day of the month your fiscal year starts |
| `fiscalYearStartMonth` | `april` | Month your fiscal year starts |
| `fiscalYearEndDay` | `31` | Day of the month your fiscal year ends |
| `fiscalYearEndMonth` | `march` | Month your fiscal year ends |
| `weekStart` | `monday` | First day of the week for weekly calculations |
| `excludeEmailAddresses` | `[]` | Email addresses to exclude across all widgets (one per line in CP, array in config). Useful for test or admin accounts |
| `comparisonMode` | `full` | How change indicators compare periods. `full` compares entire periods (e.g. all of 2025 vs all of 2024). `toDate` compares only the elapsed portion (e.g. Jan 1–Feb 18 2025 vs Jan 1–Feb 18 2024) for fairer mid-period comparisons |
| `enablePages` | `false` | Allow users to create multiple dashboard pages in the CP sidebar |
| `defaultPageWidgets` | `[]` | Widget types to add to the default Overview page for new users |
| `mapboxAccessToken` | `''` | Mapbox access token for the Locations widget map. Supports environment variables |

## Recommendations

### Purge Inactive Carts Duration

It's recommended to increase the [`purgeInactiveCartsDuration`](https://craftcms.com/docs/commerce/5.x/system/orders.html#purging-inactive-carts) setting for Craft Commerce from the default (3 months) to around 6 months (`P6M`). The Cart Abandonment widget chart spans 6 months and may show incorrect results if this setting isn't adjusted.

## Support

If you have any issues (Surely not!) then I'll aim to reply to these as soon as possible. If it's a site-breaking-oh-no-what-has-happened moment, then hit me up on the Craft CMS Discord - `@bymayo`
