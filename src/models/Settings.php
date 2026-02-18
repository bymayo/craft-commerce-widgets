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
    public $defaultTargetDuration = 'yearly';
    public $fiscalYearStartDay = 1;
    public $fiscalYearStartMonth = 'april';
    public $fiscalYearEndDay = 31;
    public $fiscalYearEndMonth = 'march';
    public $weekStart = 'monday';
    public $excludeEmailAddresses = array();
    public $enablePages = false;
    public $defaultPageWidgets = [];
    public $comparisonMode = 'full';
    public $mapboxAccessToken = '';

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
            [['fiscalYearStartDay', 'fiscalYearEndDay'], 'integer'],
            [['fiscalYearStartMonth', 'fiscalYearEndMonth'], 'string'],
            [['weekStart'], 'string'],
            [['excludeEmailAddresses'], 'safe'],
            [['enablePages'], 'boolean'],
            [['defaultPageWidgets'], 'safe'],
            [['comparisonMode'], 'string'],
            [['mapboxAccessToken'], 'string']
        ];
    }
}
