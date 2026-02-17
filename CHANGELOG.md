# Commerce Widgets Changelog

## 6.0.0 - 2026-02-17

### Added
- Cancelled and Expired columns on Subscription Plans widget
- Country flag and location on Top Customers widget
- Locations widget statistic setting to show Orders or Customers
- Locations widget Show Map lightswitch to toggle the Mapbox globe
- Top 3 countries bar chart on Locations widget
- Locations widget (formerly Order Countries) with 3D Mapbox globe showing order locations by country with count pins
- Mapbox Access Token plugin setting with environment variable support
- Custom dashboard with drag-and-drop widget management
- Dashboard pages - create multiple dashboard pages per user
- Pages subnav in CP sidebar for quick navigation between pages
- "Enable Pages" plugin setting to toggle the pages feature
- User permissions for viewing pages, managing pages, and accessing widgets
- New vs Returning Customers widget with line chart tracking new and returning customers over time
- Conversion Rate widget with funnel visualisation (Added to Cart, Checkout, Completed)
- Conversion Rate diagonal gradient connectors between funnel stages
- Conversion Rate change indicators comparing current vs previous period per stage
- Conversion rate percentages relative to Added to Cart baseline with tooltip explanations
- Fiscal Year option to widget target duration settings
- Fiscal Year start/end day and month settings for granular fiscal year configuration
- All Time option to widget target duration settings
- Product Type filter on Recent Products widget with type name in title
- Order Status filter on Recent Orders widget with status name in title
- Plugin Name setting now applies to all widget display names, permissions, and cache labels
- Change indicator on Cart Abandonment widget comparing current vs previous period
- Hover tooltip on change indicators describing the comparison period
- `calculateChange()` helper method for reuse across widgets
- `getChangeTooltip()` helper method for duration-aware tooltip text
- `getDateRange()` helper method for current/previous period date conditions
- `statisticSecondaryTooltip` option on statistic component
- CP Settings page restored
- Tailwind CSS for modern styling
- Vite build system
- Improved line graph styling with detailed/minimal options

### Changed
- Complete visual redesign of all widgets
- Extracted widget query logic into domain-specific services (Orders, Products, Customers, Carts, Subscriptions)
- Shared date filtering across all services via `applyDateFilter()`
- Recent Orders and Recent Products data now fetched in PHP services instead of Twig templates
- Cart Abandonment widget now respects all target duration settings (daily, weekly, monthly, yearly, fiscal year, all time)
- Cart Abandonment consolidated into a single `getCartAnalytics()` service call with 2 DB queries instead of 5 separate calls
- Cart Abandonment only counts inactive carts (per Commerce's `activeCartDuration` setting) for abandoned stats
- Chart legend hidden in simple graph style
- Merged dashboard services into a single Pages service
- Renamed dashboard controller, templates, and routes to Pages
- Renamed database tables to `commerce_widgets_pages` and `commerce_widgets_pages_widgets`
- Renamed permission handle from `commerceWidgets-addCmsDashboardWidgets` to `commerceWidgets-accessWidgets`
- Order Status dropdown in Top Products and Recent Orders now uses a standard select field
- Updated config.php to match all current plugin settings
- Improved tooltip positioning
- Updated styling across Top Products and Top Customers widgets

### Improved
- Extracted fiscal year calculation into shared `getFiscalYearDates()` helper, removing duplication across 5 files
- Extracted order status select into shared settings component used by Recent Orders and Top Products
- Line chart JavaScript wrapped in IIFE to prevent global scope pollution with multiple widgets
- Removed unused bar chart template

### Fixed
- Subscription Plans widget only counting active subscriptions, now correctly counts all subscription states
- Responsive layout issues on widgets
- Widget refresh and resize behaviour
- Top Customers widget using `totalPrice` instead of `totalPaid` for revenue calculation
- Cart Abandonment widget using hardcoded chart ID causing conflicts with multiple instances
- Loose type comparisons in Customers and Products services
- Missing `only` keyword on Cart Abandonment statistic include
- Total Revenue & Orders widget filtering by cart creation date instead of payment date
- Total Revenue & Orders widget missing orders on end-of-period boundaries (week/month)
- Total Revenue & Orders widget using `totalPrice` instead of `totalPaid` for revenue
- Total Revenue & Orders widget counting trashed orders in totals
- Total Revenue & Orders widget ignoring the `weekStart` plugin setting
- Total Revenue & Orders widget missing change indicator tooltips
- Top Products widget thumbnail not displaying when a Thumbnail Source is set on the product type
- Top Products widget returning no results due to MySQL strict GROUP BY mode
- Cart Abandonment widget not respecting target duration settings
- Cart Abandonment chart showing hardcoded month labels instead of dynamic data
- Cart Abandonment total price not displaying due to type casting from database
- Duplicate customers in Top Customers widget caused by grouping on both email and customerId
- Widget render failures caused by type casting when passing settings to services
- Subscription Plans widget compatibility
- Icon paths in Top Products widget
- Widget subtitle/description display and icons
- OrderStatus color error when status is null
- Migration class naming issue

## 5.0.1 - 2024-05-30
### Changed
- Icon to a new shiny (literally) icon

## 5.0.0 - 2024-05-30
### Changed
- Craft 5 compatibility

## 3.0.0 - 2022-06-17
### Changed
- Now requires PHP ^8.0.0.
- Now requires Craft CMS ^4.0.0

## 2.0.21 - 2019-05-07

### Fixed
- GROUP_BY error in 5.7 on TopCustomers widget

## 2.0.20 - 2019-11-15

### Fixed
- Namespace on asset bundle which is now case sensitive (Thanks to @matt-adigital)

## 2.0.19 - 2019-09-10

### Changed
- Minor visual adjustment to make `TopCustomers` and `ProductTop` widgets the same styling

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
