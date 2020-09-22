<img src="https://raw.githubusercontent.com/bymayo/commerce-widgets/master/resources/icon.png" width="50">

# Commerce Widgets for Craft CMS 3.x

Commerce Widgets is a Craft CMS plugin that gives you insightful dashboard widgets for your Craft Commerce store.

They help you see a better overview of your stores performance, by viewing abandoned cart statistics, setting yearly or monthly goals/targets and seeing who which customers are buying off you the most.

All these help you to optimise your store and in turn (hopefully) increase your revenue and orders.

<img src="https://raw.githubusercontent.com/bymayo/commerce-widgets/master/resources/screenshot.jpg" width="850">

## Install

- Install with Composer via `composer require bymayo/commerce-widgets` from your project directory
- Install the plugin in the Craft Control Panel under `Settings > Plugins`

You can also install the plugin via the Plugin Store in the Craft Admin CP by searching for `Commerce Widgets`.

## Requirements

- Craft CMS 3.2.x
- Craft Commerce 2.x
- MySQL (PostgreSQL support is limited)

## Configuration

The plugin comes with a `config.php` file that defines some default settings that apply to mostly all widgets.

If you want to set your own config options, create a `commerce-widgets.php` file in your Craft config directory. The contents of this file will get merged with the plugin defaults, so you only need to specify values for the settings you want to override.

### Cache Duration 
`cacheDuration` allows you to set how long the widget data is cached in seconds. This is great for stores with large amounts of orders/customers, to only cache stats every X amount of hours rather than everytime the dashboard loads. Default: `3600` (60 minutes)

### Year Start 
`yearStart` allows you to set when the financial/tax year starts. Default: `april`

### Exclude Email Addresses
`excludeEmailAddresses` allows you to exclude certain email addresses/customers across all widgets. This is particularly useful if you use a specific user to debug orders, or if orders are created via the CP. Default: `null`

### Config File Example

```
<?php
return array(
    '*' => array(
        'cacheDuration' => 3600,
        'yearStart' => 'april',
        'excludeEmailAddresses' => array(
           'admin@website.com',
           'customer@website.com'
        )
    )
);
```

## Widgets

<table>
	<tr>
		<td><strong>Widget</strong></td>
		<td><strong>Description</strong></td>
		<td><strong>Settings</strong></td>
	</tr>
	<tr>
		<td>Cart Abanadonment</td>
      <td>Compare and keep track of how many carts have been abandoned and completed, and how much revenue you missed out on.</td>
      <td>-</td>
	</tr>
   <tr>
		<td>Goal</td>
      <td>Create revenue and orders targets for your store and see how your progressing each week, month or year.</td>
      <td>
         Target Value<br>
         Target Duration (E.g. Weekly)<br>
         Target Type (E.g. Orders)
      </td>
	</tr>
   <tr>
		<td>RecentProducts</td>
      <td>See what products were recently added to your store.</td>
      <td>
         Limit
      </td>
	</tr>
	<tr>
		<td>Top Products</td>
      <td>See what products have been ordered the most based on # revenue or # orders.</td>
      <td>
			Order By<br>
         Order Status<br>
         Limit<br>
      </td>
	</tr>
   <tr>
		<td>Subscription Plans</td>
      <td>An overview of your subscription plans</td>
      <td>
         Order By<br>
         Limit
      </td>
	</tr>
   <tr>
		<td>Top Customers</td>
      <td>See who's your top customers, based on # revenue or # orders.</td>
      <td>
         Order By<br>
         Include Guests<br>
         Limit<br>
      </td>
	</tr>
   <tr>
		<td>Total Revenue & Orders</td>
      <td>Better revenue and order overview, by current day, week, month, year and all time.</td>
      <td>-</td>
	</tr>
</table>

## Recommendations 

### Purge Inactive Carts Duration 
It's recommended to increase the `purgeInactiveCartsDuration` setting (https://docs.craftcms.com/commerce/v2/configuration.html#purgeinactivecartsduration) for Craft Commerce from the default (3 months) to around 6 months (`P6M`). This is because the graph on the `Cart Abandonment` widget spans across 6 months and might show incorrect results if this setting isn't adjusted. 

## Support

If you have any issues (Surely not!) then I'll aim to reply to these as soon as possible. If it's a site-breaking-oh-no-what-has-happened moment, then hit me up on the Craft CMS Discord - `@bymayo`

## Roadmap

- +15 more widgets planned (*shhh, secret*)
- Google Analytics eCommerce Widgets
- Work with all currencies and locales
- Export options per widget
- Commerce Lite version (Free)
- Default widget dashboard for all users
