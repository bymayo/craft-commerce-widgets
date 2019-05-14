<?php

namespace bymayo\commercewidgets\models;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $cacheDuration = 3600;
    public $yearStart = 'april';

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['cacheDuration'], 'integer'],
            [['yearStart'], 'string']
        ];
    }
}
