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
                $fiscalMonth = date('n', strtotime("1 {$settings->fiscalYearStart}"));
                $currentYear = (int) date('Y');
                $startYear = ((int) date('n') >= $fiscalMonth) ? $currentYear : $currentYear - 1;
                $endMonth = date('j F', strtotime('-1 month', strtotime("1 {$settings->fiscalYearStart}")));
                return date('j F', strtotime("1 {$settings->fiscalYearStart}")) . ' ' . $startYear . ' - ' . $endMonth . ' ' . ($startYear + 1);
            case 'yearly':
                return date('Y');
            default:
                return date('F Y');
        }
    }

}
