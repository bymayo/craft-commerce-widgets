<?php

namespace bymayo\commercewidgets\variables;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\db\Query;

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
