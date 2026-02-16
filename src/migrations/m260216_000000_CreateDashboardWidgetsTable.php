<?php

namespace bymayo\commercewidgets\migrations;

use craft\db\Migration;

class m260216_000000_CreateDashboardWidgetsTable extends Migration
{

    public function safeUp()
    {
        $this->createTable('{{%commercewidgets_dashboard_widgets}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->notNull()->defaultValue(0),
            'colspan' => $this->tinyInteger()->notNull()->defaultValue(1),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%commercewidgets_dashboard_widgets}}', ['userId']);
        $this->addForeignKey(null, '{{%commercewidgets_dashboard_widgets}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%commercewidgets_dashboard_widgets}}');
    }

}
