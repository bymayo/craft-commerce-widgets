<?php

namespace bymayo\commercewidgets;

use bymayo\commercewidgets\services\Helpers;
use bymayo\commercewidgets\services\Orders;
use bymayo\commercewidgets\services\Customers;
use bymayo\commercewidgets\services\Products;
use bymayo\commercewidgets\services\Carts;
use bymayo\commercewidgets\services\Subscriptions;
use bymayo\commercewidgets\services\Pages;
use bymayo\commercewidgets\services\OrdersAnalyticsBar;
use bymayo\commercewidgets\services\ProductsAnalyticsBar;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;
use bymayo\commercewidgets\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\services\UserPermissions;
use craft\events\RegisterUserPermissionsEvent;
use craft\utilities\ClearCaches;
use craft\events\RegisterCacheOptionsEvent;
use craft\web\View;
use craft\events\TemplateEvent;
use craft\helpers\Json;

use yii\base\Event;

class CommerceWidgets extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;

    // Public Properties
    // =========================================================================

    public string $schemaVersion = '5.1.1';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'helpers' => Helpers::class,
            'orders' => Orders::class,
            'customers' => Customers::class,
            'products' => Products::class,
            'carts' => Carts::class,
            'subscriptions' => Subscriptions::class,
            'pages' => Pages::class,
            'ordersAnalyticsBar' => OrdersAnalyticsBar::class,
            'productsAnalyticsBar' => ProductsAnalyticsBar::class,
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['commerce-widgets'] = 'commerce-widgets/pages/index';
                $event->rules['commerce-widgets/page/<pageId:\d+>'] = 'commerce-widgets/pages/index';
            }
        );

        // Commerce offers no template hook on either index, so the bars are hung off the template
        // render itself and the JS places them above the element index.
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {
                $bars = [
                    'commerce/orders/_index' => [
                        'setting' => 'enableOrdersAnalyticsBar',
                        'service' => $this->ordersAnalyticsBar,
                        'action' => 'commerce-widgets/orders-analytics-bar/get-stats',
                    ],
                    'commerce/products/_index' => [
                        'setting' => 'enableProductsAnalyticsBar',
                        'service' => $this->productsAnalyticsBar,
                        'action' => 'commerce-widgets/products-analytics-bar/get-stats',
                    ],
                ];

                $bar = $bars[$event->template] ?? null;

                if ($bar === null || !$this->getSettings()->{$bar['setting']} || !$bar['service']->canView()) {
                    return;
                }

                $stats = $bar['service']->getEnabledStatsForJs();

                if (empty($stats)) {
                    return;
                }

                $view = Craft::$app->getView();
                $view->registerAssetBundle(CommerceWidgetsAsset::class);
                $view->registerJs(
                    'window.CommerceWidgetsAnalyticsBar = ' . Json::encode([
                        'action' => $bar['action'],
                        'stats' => $stats,
                    ]) . ';',
                    View::POS_HEAD
                );
            }
        );

        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {

               $event->types[] = \bymayo\commercewidgets\widgets\ProductsRecent::class;
               $event->types[] = \bymayo\commercewidgets\widgets\ProductsTop::class;
               $event->types[] = \bymayo\commercewidgets\widgets\CartAbandonment::class;
               $event->types[] = \bymayo\commercewidgets\widgets\TotalRevenueOrders::class;
               $event->types[] = \bymayo\commercewidgets\widgets\TopCustomers::class;
               $event->types[] = \bymayo\commercewidgets\widgets\Goal::class;
               $event->types[] = \bymayo\commercewidgets\widgets\SubscriptionPlans::class;
               $event->types[] = \bymayo\commercewidgets\widgets\OrdersRecent::class;
            $event->types[] = \bymayo\commercewidgets\widgets\ConversionRate::class;
            $event->types[] = \bymayo\commercewidgets\widgets\NewVsReturningCustomers::class;
            $event->types[] = \bymayo\commercewidgets\widgets\OrdersMap::class;

            }
        );

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => $this->helpers->getPluginName(),
                    'permissions' => [
                        'commerceWidgets-viewPages' => [
                            'label' => 'View Pages',
                        ],
                        'commerceWidgets-managePages' => [
                            'label' => 'Create and manage pages',
                        ],
                        'commerceWidgets-accessWidgets' => [
                            'label' => 'Access Widgets',
                        ],
                        'commerceWidgets-viewOrdersAnalyticsBar' => [
                            'label' => 'View Orders Analytics Bar',
                        ],
                        'commerceWidgets-viewProductsAnalyticsBar' => [
                            'label' => 'View Products Analytics Bar',
                        ],
                    ],
                ];
            }
        );

        Event::on(
            ClearCaches::class,
            ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
            function (RegisterCacheOptionsEvent $event) {
                $event->options[] = [
                    'key' => 'commerce-widgets-data',
                    'label' => $this->helpers->getPluginName() . ' data',
                    'action' => function() {
                        \yii\caching\TagDependency::invalidate(Craft::$app->getCache(), 'commerce-widgets');
                    },
                ];
            }
        );

        Craft::info(
            Craft::t(
                'commerce-widgets',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getCpNavItem(): ?array
    {
        $settings = $this->getSettings();

        if (!$settings->enablePages) {
            return null;
        }

        $item = parent::getCpNavItem();
        $item['label'] = $this->helpers->getPluginName();

        $user = Craft::$app->getUser()->getIdentity();
        $canViewPages = $user && ($user->admin || $user->can('commerceWidgets-viewPages'));

        if ($canViewPages && Craft::$app->getRequest()->getIsCpRequest()) {
            $pages = $this->pages->getPagesForUser($user->id);

            if (!empty($pages)) {
                $item['subnav'] = [];
                foreach ($pages as $page) {
                    $item['subnav']['page-' . $page->id] = [
                        'label' => $page->name,
                        'url' => 'commerce-widgets/page/' . $page->id,
                    ];
                }
            }
        }

        return $item;
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->controller->renderTemplate(
            'commerce-widgets/_settings',
            [
                'settings' => $this->getSettings(),
                'availableWidgetTypes' => self::$plugin->pages->getAvailableWidgetTypes(),
                'ordersBarStatOptions' => self::$plugin->ordersAnalyticsBar->getStatOptions(),
                'orderStatusOptions' => self::$plugin->ordersAnalyticsBar->getOrderStatusOptions(),
                'ordersBarStatsMax' => OrdersAnalyticsBar::MAX_STATS,
                'ordersBarStatusSettings' => self::$plugin->ordersAnalyticsBar->getStatStatusSettings(),
                'productsBarStatOptions' => self::$plugin->productsAnalyticsBar->getStatOptions(),
                'productsBarStatsMax' => ProductsAnalyticsBar::MAX_STATS,
            ]
        );
    }

}
