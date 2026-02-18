<?php

namespace bymayo\commercewidgets;

use bymayo\commercewidgets\services\Helpers;
use bymayo\commercewidgets\services\Orders;
use bymayo\commercewidgets\services\Customers;
use bymayo\commercewidgets\services\Products;
use bymayo\commercewidgets\services\Carts;
use bymayo\commercewidgets\services\Subscriptions;
use bymayo\commercewidgets\services\Pages;
use bymayo\commercewidgets\variables\CommerceWidgetsVariable;
use bymayo\commercewidgets\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\services\UserPermissions;
use craft\events\RegisterUserPermissionsEvent;
use craft\utilities\ClearCaches;
use craft\events\RegisterCacheOptionsEvent;

use yii\base\Event;

class CommerceWidgets extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;

    // Public Properties
    // =========================================================================

    public string $schemaVersion = '4.2.0';
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
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['commerce-widgets'] = 'commerce-widgets/pages/index';
                $event->rules['commerce-widgets/page/<pageId:\d+>'] = 'commerce-widgets/pages/index';
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
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('commercewidgets', CommerceWidgetsVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
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
            ]
        );
    }

}
