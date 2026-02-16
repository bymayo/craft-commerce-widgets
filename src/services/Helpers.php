<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;

class Helpers extends Component
{

    public function getTargetDuration($targetDuration): string
    {
        if ($targetDuration === 'default' || $targetDuration === null) {
            return CommerceWidgets::$plugin->getSettings()->defaultTargetDuration;
        }

        return $targetDuration;
    }

    public function getTargetDurationLabel($targetDuration): string
    {
        $targetDuration = $this->getTargetDuration($targetDuration);
        $settings = CommerceWidgets::$plugin->getSettings();

        switch ($targetDuration) {
            case 'daily':
                return date('j F Y');
            case 'weekly':
                $start = strtotime("last {$settings->weekStart}", strtotime('tomorrow'));
                $end = strtotime('+6 days', $start);
                if (date('F', $start) === date('F', $end)) {
                    return date('j', $start) . ' - ' . date('j F Y', $end);
                }
                return date('j F', $start) . ' - ' . date('j F Y', $end);
            case 'fiscalYear':
                $startDay = (int) $settings->fiscalYearStartDay;
                $startMonth = ucfirst($settings->fiscalYearStartMonth);
                $endDay = (int) $settings->fiscalYearEndDay;
                $endMonth = ucfirst($settings->fiscalYearEndMonth);
                $fiscalMonthNum = date('n', strtotime("1 {$startMonth}"));
                $currentYear = (int) date('Y');
                $startYear = ((int) date('n') >= $fiscalMonthNum || ((int) date('n') == $fiscalMonthNum && (int) date('j') >= $startDay)) ? $currentYear : $currentYear - 1;
                $endYear = $startYear + 1;
                return "{$startDay} {$startMonth} {$startYear} - {$endDay} {$endMonth} {$endYear}";
            case 'yearly':
                return date('Y');
            case 'allTime':
                return 'All Time';
            default:
                return date('F Y');
        }
    }

}
