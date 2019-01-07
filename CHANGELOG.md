# Commerce Widgets Changelog

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
