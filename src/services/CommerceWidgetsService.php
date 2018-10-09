<?php
/**
 * Commerce Widgets plugin for Craft CMS 3.x
 *
 * Description
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\base\Component;

/**
 * @author    ByMayo
 * @package   CommerceWidgets
 * @since     2.0.0
 */
class CommerceWidgetsService extends Component
{
    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function exampleService()
    {
        $result = 'something';
        // Check our Plugin's settings for `someAttribute`
        if (CommerceWidgets::$plugin->getSettings()->someAttribute) {
        }

        return $result;
    }
}
