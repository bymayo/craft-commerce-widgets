<?php

namespace bymayo\commercewidgets\records;

use craft\db\ActiveRecord;

class Widget extends ActiveRecord
{

    public static function tableName(): string
    {
        return '{{%commerce_widgets_pages_widgets}}';
    }

}
