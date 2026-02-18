<?php

namespace bymayo\commercewidgets\migrations;

use craft\db\Migration;

class Install extends Migration
{

    public function safeUp()
    {
        // Pages table
        if (!$this->db->tableExists('{{%commerce_widgets_pages}}')) {
            $this->createTable('{{%commerce_widgets_pages}}', [
                'id' => $this->primaryKey(),
                'userId' => $this->integer()->notNull(),
                'name' => $this->string()->notNull()->defaultValue('Overview'),
                'sortOrder' => $this->smallInteger()->notNull()->defaultValue(0),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%commerce_widgets_pages}}', ['userId']);
            $this->addForeignKey(null, '{{%commerce_widgets_pages}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');
        }

        // Page widgets table
        if (!$this->db->tableExists('{{%commerce_widgets_pages_widgets}}')) {
            $this->createTable('{{%commerce_widgets_pages_widgets}}', [
                'id' => $this->primaryKey(),
                'userId' => $this->integer()->notNull(),
                'pageId' => $this->integer()->notNull(),
                'type' => $this->string()->notNull(),
                'sortOrder' => $this->smallInteger()->notNull()->defaultValue(0),
                'colspan' => $this->tinyInteger()->notNull()->defaultValue(1),
                'settings' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%commerce_widgets_pages_widgets}}', ['userId']);
            $this->createIndex(null, '{{%commerce_widgets_pages_widgets}}', ['pageId']);
            $this->addForeignKey(null, '{{%commerce_widgets_pages_widgets}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');
            $this->addForeignKey(null, '{{%commerce_widgets_pages_widgets}}', ['pageId'], '{{%commerce_widgets_pages}}', ['id'], 'CASCADE');
        }
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%commerce_widgets_pages_widgets}}');
        $this->dropTableIfExists('{{%commerce_widgets_pages}}');
    }

}
