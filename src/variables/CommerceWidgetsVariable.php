<?php
/**
 * Commerce Widgets plugin for Craft CMS 3.x
 *
 * Description
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\commercewidgets\variables;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\db\Query;

/**
 * @author    ByMayo
 * @package   CommerceWidgets
 * @since     2.0.0
 */
class CommerceWidgetsVariable
{
    // Public Methods
    // =========================================================================

    public function userIdByCustomerId($customerId)
    {

      $query = (
            new Query()
         )
         ->select(
            [
               'userId'
            ]
         )
         ->from(['{{%commerce_customers}}'])
         ->where(['id' => $customerId])
         ->all();

      return $query[0];

    }
}
