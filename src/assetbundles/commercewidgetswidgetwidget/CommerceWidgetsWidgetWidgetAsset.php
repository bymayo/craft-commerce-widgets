<?php
/**
 * Commerce Widgets plugin for Craft CMS 3.x
 *
 * Helpful widgets to use with Craft Commerce
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\commercewidgets\assetbundles\commercewidgetswidgetwidget;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    ByMayo
 * @package   CommerceWidgets
 * @since     2.0.0
 */
class CommerceWidgetsWidgetWidgetAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@bymayo/commercewidgets/assetbundles/commercewidgetswidgetwidget/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/CommerceWidgetsWidget.js',
        ];

        $this->css = [
            'css/CommerceWidgetsWidget.css',
        ];

        parent::init();
    }
}
