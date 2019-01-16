**Looking for Craft CMS 2 Support?** [Commerce Widgets for Craft CMS 2](https://github.com/bymayo/craft-commerce-widgets/tree/craft-2)

<img src="https://raw.githubusercontent.com/bymayo/commerce-widgets/master/resources/icon.png" width="50">

# Commerce Widgets for Craft CMS 3.x

Commerce Widgets is a Craft CMS plugin that gives you insightful dashboard widgets for your Craft Commerce 2 store.

They help you see a better overview of your stores performance, by viewing abandoned cart statistics, setting yearly or monthly goals/targets and seeing who which customers are buying off you the most.

All these help you to optimise your store and in turn (hopefully) increase your revenue and orders.

<img src="https://raw.githubusercontent.com/bymayo/commerce-widgets/master/resources/screenshot.jpg" width="850">

## Install

- Install with Composer via `composer require bymayo/commerce-widgets` from your project directory
- Install the plugin in the Craft Control Panel under `Settings > Plugins`

You can also install the plugin via the Plugin Store in the Craft Admin CP by searching for `Commerce Widgets`.

## Requirements

- Craft CMS 3.x
- Craft Commerce 2.x

## Configuration

Most of the widgets come with settings. Make sure you check the settings on each widget to customise them for your shop.

## Widgets Included

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
         Limit
      </td>
	</tr>
   <tr>
		<td>Subscription Plans</td>
      <td>An overview of your subscription plans</td>
      <td>
         Limit<br>
         Order By
      </td>
	</tr>
   <tr>
		<td>Top Customers</td>
      <td>See who's your top customers, based on # revenue or # orders.</td>
      <td>
         Order By<br>
         Group By<br>
         Include Guests
      </td>
	</tr>
   <tr>
		<td>Total Revenue & Orders</td>
      <td>Better revenue and order overview, by current day, week, month, year and all time.</td>
      <td>-</td>
	</tr>
</table>

## Support

If you have any issues (Surely not!) then I'll aim to reply to these as soon as possible. If it's a site-breaking-oh-no-what-has-happened moment, then hit me up on the Craft CMS Slack - @bymayo

## Roadmap

- +15 more widgets (Aiming to add at least 1 or 2 a month)
- Google Analytics eCommerce Widgets
- Work with all currencies and locales
- Export options per widget
- Commerce Lite version (Free)
- 'Default' widget dashboard for all users
