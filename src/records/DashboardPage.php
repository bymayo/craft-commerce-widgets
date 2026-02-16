<?php

namespace bymayo\commercewidgets\records;

use craft\db\ActiveRecord;

class DashboardPage extends ActiveRecord
{

    public static function tableName(): string
    {
        return '{{%commercewidgets_dashboard_pages}}';
    }

}
