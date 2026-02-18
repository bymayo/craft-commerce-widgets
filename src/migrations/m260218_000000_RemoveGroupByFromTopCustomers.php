<?php

namespace bymayo\commercewidgets\migrations;

use craft\db\Migration;
use craft\db\Query;

class m260218_000000_RemoveGroupByFromTopCustomers extends Migration
{

    public function safeUp()
    {
        $tables = [
            '{{%widgets}}',
            '{{%commerce_widgets_pages_widgets}}',
        ];

        foreach ($tables as $table) {
            $rows = (new Query())
                ->select(['id', 'settings'])
                ->from($table)
                ->where(['like', 'type', 'TopCustomers'])
                ->andWhere(['like', 'settings', 'groupBy'])
                ->all();

            foreach ($rows as $row) {
                $settings = json_decode($row['settings'], true);

                if (is_array($settings) && array_key_exists('groupBy', $settings)) {
                    unset($settings['groupBy']);
                    $this->update($table, [
                        'settings' => json_encode($settings),
                    ], ['id' => $row['id']]);
                }
            }
        }
    }

    public function safeDown()
    {
        return true;
    }

}
