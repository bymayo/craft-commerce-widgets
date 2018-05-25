<?php
/**
 * Commerce Widgets
 *
 * @author    Jason Mayo
 * @twitter   @madebymayo
 * @package   Commerce Widgets
 *
 */

namespace Craft;

class CommerceWidgetsPlugin extends BasePlugin
{

    public function init()
    {
    }

    public function getName()
    {
         return Craft::t('Commerce Widgets');
    }

    public function getDescription()
    {
        return Craft::t('Helpful widgets to use with Craft Commerce');
    }

    public function getDocumentationUrl()
    {
        return 'https://github.com/bymayo/commerce-widgets/blob/craft-2/README.md';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/bymayo/commerce-widgets/craft-2/releases.json';
    }

    public function getVersion()
    {
        return '1.0.7';
    }

    public function getSchemaVersion()
    {
        return '1.0.7';
    }

    public function getDeveloper()
    {
        return 'ByMayo';
    }

    public function getDeveloperUrl()
    {
        return 'bymayo.co.uk';
    }

    public function hasCpSection()
    {
        return false;
    }
}
