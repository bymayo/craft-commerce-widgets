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
