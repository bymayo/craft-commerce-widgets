<?php
/**
 * Commerce Widgets plugin for Craft CMS 3.x
 *
 * Helpful widgets to use with Craft Commerce
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\commercewidgets;

use bymayo\commercewidgets\services\CommerceWidgetsService as CommerceWidgetsServiceService;
use bymayo\commercewidgets\widgets\CommerceWidgetsWidget as CommerceWidgetsWidgetWidget;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
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
                $event->types[] = CommerceWidgetsWidgetWidget::class;
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

}
