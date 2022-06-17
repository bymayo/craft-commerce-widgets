<?php

namespace bymayo\commercewidgets;

use bymayo\commercewidgets\services\CommerceWidgetsService as CommerceWidgetsServiceService;
use bymayo\commercewidgets\variables\CommerceWidgetsVariable;
use bymayo\commercewidgets\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

class CommerceWidgets extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;

    // Public Properties
    // =========================================================================

    public string $schemaVersion = '2.0.1';
    public bool $hasCpSettings = false;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

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

        Craft::info(
            Craft::t(
                'commerce-widgets',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

}
