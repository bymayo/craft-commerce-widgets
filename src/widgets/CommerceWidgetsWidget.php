<?php
/**
 * Commerce Widgets plugin for Craft CMS 3.x
 *
 * Helpful widgets to use with Craft Commerce
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\commercewidgets\widgets;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgetswidgetwidget\CommerceWidgetsWidgetWidgetAsset;

use Craft;
use craft\base\Widget;

/**
 * Commerce Widgets Widget
 *
 * @author    ByMayo
 * @package   CommerceWidgets
 * @since     2.0.0
 */
class CommerceWidgetsWidget extends Widget
{

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $message = 'Hello, world.';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce-widgets', 'CommerceWidgetsWidget');
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias("@bymayo/commercewidgets/assetbundles/commercewidgetswidgetwidget/dist/img/CommerceWidgetsWidget-icon.svg");
    }

    /**
     * @inheritdoc
     */
    public static function maxColspan()
    {
        return null;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules = array_merge(
            $rules,
            [
                ['message', 'string'],
                ['message', 'default', 'value' => 'Hello, world.'],
            ]
        );
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/_components/widgets/CommerceWidgetsWidget_settings',
            [
                'widget' => $this
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsWidgetWidgetAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-widgets/_components/widgets/CommerceWidgetsWidget_body',
            [
                'message' => $this->message
            ]
        );
    }
}
