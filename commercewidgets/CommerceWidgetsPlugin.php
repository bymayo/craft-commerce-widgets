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
        return 'https://github.com/madebyshape/commerce-widgets/blob/master/README.md';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/madebyshape/commerce-widgets/master/releases.json';
    }

    public function getVersion()
    {
        return '1.0.5';
    }

    public function getSchemaVersion()
    {
        return '1.0.5';
    }

    public function getDeveloper()
    {
        return 'Jason Mayo';
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