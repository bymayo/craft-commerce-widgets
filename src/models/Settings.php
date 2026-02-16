<?php

namespace bymayo\commercewidgets\models;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $pluginName = 'Commerce Widgets';
    public $cacheDuration = 3600;
    public $defaultTargetDuration = 'monthly';
    public $fiscalYearStart = 'april';
    public $weekStart = 'monday';
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
            [['defaultTargetDuration'], 'string'],
            [['fiscalYearStart'], 'string'],
            [['weekStart'], 'string'],
            [['excludeEmailAddresses'], 'safe']
        ];
    }
}
