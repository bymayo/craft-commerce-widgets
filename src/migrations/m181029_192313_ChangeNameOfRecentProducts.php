<?php

namespace bymayo\commercewidgets\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m181029_192313_ChangeNameOfRecentProducts extends Migration
{

    public function safeUp()
    {

      $this->update(
                 '{{%widgets}}',
                 ['type' => 'bymayo\commercewidgets\widgets\ProductsRecent'],
                 ['type' => 'bymayo\commercewidgets\widgets\RecentProducts']
            );

    }

    public function safeDown()
    {
        echo "m181029_192313_ChangeNameOfRecentProducts cannot be reverted.\n";
        return false;
    }
}
