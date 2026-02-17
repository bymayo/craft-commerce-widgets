<?php

namespace bymayo\commercewidgets\migrations;

use craft\db\Migration;

class m260217_100000_RenameTableNames extends Migration
{

    public function safeUp()
    {
        // Drop foreign keys before renaming
        $this->dropForeignKeyIfExists('{{%commercewidgets_dashboard_widgets}}', ['pageId']);
        $this->dropForeignKeyIfExists('{{%commercewidgets_dashboard_widgets}}', ['userId']);
        $this->dropForeignKeyIfExists('{{%commercewidgets_dashboard_pages}}', ['userId']);

        // Rename tables
        $this->renameTable('{{%commercewidgets_dashboard_pages}}', '{{%commerce_widgets_pages}}');
        $this->renameTable('{{%commercewidgets_dashboard_widgets}}', '{{%commerce_widgets_pages_widgets}}');

        // Re-add foreign keys with new table names
        $this->addForeignKey(null, '{{%commerce_widgets_pages}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%commerce_widgets_pages_widgets}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%commerce_widgets_pages_widgets}}', ['pageId'], '{{%commerce_widgets_pages}}', ['id'], 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKeyIfExists('{{%commerce_widgets_pages_widgets}}', ['pageId']);
        $this->dropForeignKeyIfExists('{{%commerce_widgets_pages_widgets}}', ['userId']);
        $this->dropForeignKeyIfExists('{{%commerce_widgets_pages}}', ['userId']);

        $this->renameTable('{{%commerce_widgets_pages}}', '{{%commercewidgets_dashboard_pages}}');
        $this->renameTable('{{%commerce_widgets_pages_widgets}}', '{{%commercewidgets_dashboard_widgets}}');

        $this->addForeignKey(null, '{{%commercewidgets_dashboard_pages}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%commercewidgets_dashboard_widgets}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%commercewidgets_dashboard_widgets}}', ['pageId'], '{{%commercewidgets_dashboard_pages}}', ['id'], 'CASCADE');
    }

}
