<?php

namespace bymayo\commercewidgets\assetbundles\commercewidgets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CommerceWidgetsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@bymayo/commercewidgets/resources/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
           'js/plugins/Chart.min.js',
           'js/commerce-widgets.js',
        ];

        $this->css = [
            'css/commerce-widgets.css',
        ];

        parent::init();
    }
}
