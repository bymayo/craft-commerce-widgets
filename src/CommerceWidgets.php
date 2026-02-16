<?php

namespace bymayo\commercewidgets;

use bymayo\commercewidgets\services\Helpers;
use bymayo\commercewidgets\services\DashboardWidgets;
use bymayo\commercewidgets\services\DashboardPages;
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

    public string $schemaVersion = '4.1.0';
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
            'dashboardWidgets' => DashboardWidgets::class,
            'dashboardPages' => DashboardPages::class,
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['commerce-widgets'] = 'commerce-widgets/dashboard/index';
                $event->rules['commerce-widgets/page/<pageId:\d+>'] = 'commerce-widgets/dashboard/index';
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
                    'heading' => 'Commerce Widgets',
                    'permissions' => [
                        'commerceWidgets-viewPages' => [
                            'label' => 'View dashboard pages',
                        ],
                        'commerceWidgets-managePages' => [
                            'label' => 'Create and manage dashboard pages',
                        ],
                        'commerceWidgets-addCmsDashboardWidgets' => [
                            'label' => 'Add widgets to the CMS Dashboard',
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
                    'label' => Craft::t('commerce-widgets', 'Commerce Widgets data'),
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
        $item = parent::getCpNavItem();
        $settings = $this->getSettings();
        $item['label'] = $settings->pluginName ?: 'Commerce Widgets';

        if ($settings->enablePages) {
            $user = Craft::$app->getUser()->getIdentity();
            $canViewPages = $user && ($user->admin || $user->can('commerceWidgets-viewPages'));

            if ($canViewPages && Craft::$app->getRequest()->getIsCpRequest()) {
                $pages = $this->dashboardPages->getPagesForUser($user->id);

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
        }

        return $item;
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/_settings',
            ['settings' => $this->getSettings()]
        );
    }

}
