# Commerce Widgets Changelog

## 2.0.18 - 2019-08-09

### Fixed
- Added a default limit on `TopCustomers` widget to fix issue where it pulls all customers in if the widget already existed on the dashboard

## 2.0.17 - 2019-08-09

> {warning} This plugin now requires Craft CMS 3.2.x or higher due to the way it now deals with trashed orders. It's also recommended to now use a `commerce-widgets.php` config file for the plugin settings since this version doesn't include the ability to modify settings via the CP.

> {tip} Due to a change on the Cart Abandonment widget, it's recommended to increase the `purgeInactiveCartsDuration` setting in `commerce.php` file in you config folder.

### Changed
- Removed settings from CP and made it only available via a `commerce-widgets.php` config file.

### Added
- Added `excludeEmailAddresses` config setting to allow excluding of orders/customers via email across all widgets ([#28](https://github.com/bymayo/craft-commerce-widgets/issues/28))
- Added limit option to `TopCustomers` widget

### Fixed
- Data totals were being pulled through even when orders were trashed ([#27](https://github.com/bymayo/craft-commerce-widgets/issues/27))
- Order totals and revenue totals were being pulled through even if the order wasn't complete
- Customers are now grouped by email address only on the `TopCustomers` widget, fixing an issue where duplicates appeared

## 2.0.16 - 2019-07-10
### Fixed
- Completed carts value on `CartAbandonment` widget not grouped by month and year, not just month
- The 'Today' value now matches the current day, not the first day of the week on `TotalRevenueOrders` widget

### Changed
- Product titles now trim if they are above 40 characters

## 2.0.15 - 2019-06-03
### Fixed
- Fixed step count issue by removing step value on `CartAbandonment` widget

## 2.0.14 - 2019-05-14
### Fixed
- Issue with MySQL 5.7 on `CartAbandonment` widget

### Added
- Added year to the 'Month' date on the `TotalRevenueOrders` widget

## Changed
- Removed the SKU from the `ProductTop` table for smaller resolutions


## 2.0.13 - 2019-05-14
### Fixed
- Plural spelling on `CartAbandonment` widget

### Changed
- Step size to work better with large quantity of orders on `CartAbandonment` widget

## 2.0.12 - 2019-05-14
### Fixed
- Total data now outputs week and month correctly on `TotalRevenueOrders` widget
- Issue with  Verbb Gift Voucher on `ProductsTop` ([#20](https://github.com/bymayo/craft-commerce-widgets/issues/20))
- Graph now outputs the correct values on `CartAbandonment` widget
- Removed deprecated `round()` function ([#22](https://github.com/bymayo/craft-commerce-widgets/pull/22))
- `userIdByCustomerId` not prefixed on customers table ([#23](https://github.com/bymayo/craft-commerce-widgets/pull/23))

### Changed
- Column title on `ProductsTop` widget now Orders not Ordered ([#17](https://github.com/bymayo/craft-commerce-widgets/pull/17))

### Added
- Subscription Plan URL added to the plans on `SubscriptionPlans` widget

## 2.0.11 - 2019-01-07
### Added
- Added `Order Status` setting to the Top Products Widget.

## 2.0.10 - 2019-01-04
### Fixed
- `groupBy` issue with MySQL 5.7 on some widgets
- Week was showing incorrectly in the Goal widget
- Installing the plugin now populates the cache setting by default

### Changed
- When no elements exist on a widget they now show a message

## 2.0.9 - 2018-10-30
### Added
- *New Widget* Top Products Widget - See your top products ordered by Revenue or # Ordered
- Cache to queries to speed up the CMS
- `cacheDuration` setting in plugin settings.
- Added FontAwesome to some widget templates

### Changed
- Changed the class of the `RecentProducts` to `ProductsRecent` to keep widgets organised going forward.

### Fixed
- Number formatting across all widgets now formats when the value is more than 2 numbers

## 2.0.8 - 2018-10-15
### Changed
- Charts are now flexible to larger browsers
- Altered the stepSize on the Cart Abandonment widget for stores that have large amounts of carts.

## 2.0.7 - 2018-10-15
### Fixed
- Fix prefixed tables on join queries [#9](https://github.com/bymayo/craft-commerce-widgets/issues/9)

## 2.0.6 - 2018-10-14
### Fixed
- Fix prefixed tables [#9](https://github.com/bymayo/craft-commerce-widgets/issues/9)

## 2.0.5 - 2018-10-11
### Fixed
- Fix for classes not loading on case sensitive environments

## 2.0.4 - 2018-10-10
### Changed
- License from MIT to Craft

## 2.0.3 - 2018-10-09
### Added
- Subscriptions widget

### Fixed
- Show all products regardless of status

### Changed
- Removed redundant code
- Removed settings from customers widget

## 2.0.2 - 2018-10-08
### Changed
- Tidying up and updating the versions

## 2.0.1 - 2018-10-08
### Fixed
- Minor bugs for stores that are empty (Initial installs)

## 2.0.0 - 2018-10-08
### Added
- Initial release
