<?php

namespace bymayo\commercewidgets\migrations;

use craft\db\Migration;
use craft\db\Query;

class m260217_000000_AddOverviewPages extends Migration
{

    public function safeUp()
    {
        // 1. Create the pages table
        $this->createTable('{{%commercewidgets_dashboard_pages}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'name' => $this->string()->notNull()->defaultValue('Overview'),
            'sortOrder' => $this->smallInteger()->notNull()->defaultValue(0),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%commercewidgets_dashboard_pages}}', ['userId']);
        $this->addForeignKey(null, '{{%commercewidgets_dashboard_pages}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');

        // 2. Add pageId column to widgets table (nullable initially for migration)
        $this->addColumn(
            '{{%commercewidgets_dashboard_widgets}}',
            'pageId',
            $this->integer()->null()->after('userId')
        );

        // 3. Migrate existing data: create a default page per user and reassign widgets
        $userIds = (new Query())
            ->select(['userId'])
            ->distinct()
            ->from('{{%commercewidgets_dashboard_widgets}}')
            ->column();

        $now = (new \DateTime())->format('Y-m-d H:i:s');

        foreach ($userIds as $userId) {
            $this->insert('{{%commercewidgets_dashboard_pages}}', [
                'userId' => $userId,
                'name' => 'Overview',
                'sortOrder' => 1,
                'dateCreated' => $now,
                'dateUpdated' => $now,
            ]);

            $pageId = (new Query())
                ->select(['id'])
                ->from('{{%commercewidgets_dashboard_pages}}')
                ->where(['userId' => $userId])
                ->orderBy(['id' => SORT_DESC])
                ->scalar();

            $this->update(
                '{{%commercewidgets_dashboard_widgets}}',
                ['pageId' => $pageId],
                ['userId' => $userId, 'pageId' => null]
            );
        }

        // 4. Make pageId NOT NULL and add foreign key
        $this->alterColumn(
            '{{%commercewidgets_dashboard_widgets}}',
            'pageId',
            $this->integer()->notNull()
        );

        $this->createIndex(null, '{{%commercewidgets_dashboard_widgets}}', ['pageId']);
        $this->addForeignKey(
            null,
            '{{%commercewidgets_dashboard_widgets}}',
            ['pageId'],
            '{{%commercewidgets_dashboard_pages}}',
            ['id'],
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // Drop FK and column from widgets
        $this->dropForeignKeyIfExists('{{%commercewidgets_dashboard_widgets}}', ['pageId']);
        $this->dropColumn('{{%commercewidgets_dashboard_widgets}}', 'pageId');

        // Drop pages table
        $this->dropTableIfExists('{{%commercewidgets_dashboard_pages}}');
    }

}
