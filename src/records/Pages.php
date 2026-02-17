<?php

namespace bymayo\commercewidgets\records;

use craft\db\ActiveRecord;

class Pages extends ActiveRecord
{

    public static function tableName(): string
    {
        return '{{%commerce_widgets_pages}}';
    }

}
