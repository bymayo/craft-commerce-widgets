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
    public $fiscalYearStart = 'april';
    public $excludeEmailAddresses = array();

    // Public Methods
    // =========================================================================

    public function beforeValidate(): bool
    {
        if (is_string($this->excludeEmailAddresses)) {
            $this->excludeEmailAddresses = array_filter(
                array_map('trim', explode("\n", $this->excludeEmailAddresses))
            );
        }

        $this->cacheDuration = (int) $this->cacheDuration;

        return parent::beforeValidate();
    }

    public function rules(): array
    {
        return [
            [['cacheDuration'], 'integer'],
            [['fiscalYearStart'], 'string'],
            [['excludeEmailAddresses'], 'array']
        ];
    }
}
