<?php

namespace bymayo\commercewidgets\records;

use craft\db\ActiveRecord;

class DashboardWidget extends ActiveRecord
{

    public static function tableName(): string
    {
        return '{{%commercewidgets_dashboard_widgets}}';
    }

}
