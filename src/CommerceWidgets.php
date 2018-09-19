<?php
/**
 * Commerce Widgets plugin for Craft CMS 3.x
 *
 * Description
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

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

/**
 * Class CommerceWidgets
 *
 * @author    ByMayo
 * @package   CommerceWidgets
 * @since     2.0.0
 *
 * @property  CommerceWidgetsServiceService $commerceWidgetsService
 */
class CommerceWidgets extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var CommerceWidgets
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '2.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {

                // $finder = new Finder();

                // $namespace = 'bymayo\commercewidgets\Widgets';

                // $files = $finder
                //     ->name('*Widget.php')
                //     ->files()
                //     ->ignoreDotFiles(true)
                //     ->notName('Abstract*.php')
                //     ->in(__DIR__ . '/Widgets/');

                // foreach ($files as $file) {
                //     $className = str_replace('.' . $file->getExtension(), '', $file->getBasename());
                //     $className = $namespace . '\\' . $className;
                //     $event->types[] = $className;
                // }

                // $event->types[] = $namespace . '\\' . 'RecentProducts';

                $event->types[] = Widgets\RecentProducts::class;
                $event->types[] = Widgets\CartAbandonment::class;
                $event->types[] = Widgets\TotalRevenueOrders::class;
                $event->types[] = Widgets\TopCustomers::class;

            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                // $variable = $event->sender;
                // $variable->set('commerceWidgets', CommerceWidgetsRecentProductsWidget::class);
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

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'commerce-widgets/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
