<?php
/**
 * Commerce Widgets plugin for Craft CMS 3.x
 *
 * Description
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\commercewidgets\assetbundles\CommerceWidgets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    ByMayo
 * @package   CommerceWidgets
 * @since     2.0.0
 */
class CommerceWidgetsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@bymayo/commercewidgets/assetbundles/commercewidgets/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
           'js/plugins/Chart.min.js',
            'js/CommerceWidgets.js',
        ];

        $this->css = [
            'css/CommerceWidgets.css',
        ];

        parent::init();
    }
}
