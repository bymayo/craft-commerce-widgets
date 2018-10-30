<?php

namespace bymayo\commercewidgets\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

/**
 * m181029_192313_ChangeNameOfRecentProducts migration.
 */
class m181029_192313_ChangeNameOfRecentProducts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

      $this->update(
                 '{{%widgets}}',
                 ['type' => 'bymayo\commercewidgets\widgets\ProductsRecent'],
                 ['type' => 'bymayo\commercewidgets\widgets\RecentProducts']
            );

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181029_192313_ChangeNameOfRecentProducts cannot be reverted.\n";
        return false;
    }
}
