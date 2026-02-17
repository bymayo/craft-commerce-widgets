<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;

class Helpers extends Component
{

    public function getPluginName(): string
    {
        return CommerceWidgets::$plugin->getSettings()->pluginName ?: 'Commerce Widgets';
    }

    public function getTargetDuration($targetDuration): string
    {
        if ($targetDuration === 'default' || $targetDuration === null) {
            return CommerceWidgets::$plugin->getSettings()->defaultTargetDuration;
        }

        return $targetDuration;
    }

    public function applyDateFilter(Query $query, string $targetDuration, string $dateColumn = 'orders.datePaid'): Query
    {
        $targetDuration = $this->getTargetDuration($targetDuration);
        $settings = CommerceWidgets::$plugin->getSettings();

        switch ($targetDuration) {
            case 'weekly':
                $query->andWhere([
                    'WEEK(' . $dateColumn . ', 1)' => date('W'),
                    'YEAR(' . $dateColumn . ')' => date('Y')
                ]);
                break;
            case 'monthly':
                $query->andWhere([
                    'MONTH(' . $dateColumn . ')' => date('n'),
                    'YEAR(' . $dateColumn . ')' => date('Y')
                ]);
                break;
            case 'yearly':
                $query->andWhere([
                    'YEAR(' . $dateColumn . ')' => date('Y')
                ]);
                break;
            case 'fiscalYear':
                $startDay = (int) $settings->fiscalYearStartDay;
                $startMonth = $settings->fiscalYearStartMonth;
                $endDay = (int) $settings->fiscalYearEndDay;
                $endMonth = $settings->fiscalYearEndMonth;
                $fiscalMonth = date('n', strtotime("1 $startMonth"));
                $currentMonth = (int) date('n');
                $currentYear = (int) date('Y');

                if ($currentMonth > $fiscalMonth || ($currentMonth == $fiscalMonth && (int) date('j') >= $startDay)) {
                    $startYear = $currentYear;
                } else {
                    $startYear = $currentYear - 1;
                }
                $endYear = $startYear + 1;

                $startDate = date('Y-m-d', strtotime("$startDay $startMonth $startYear"));
                $endDate = date('Y-m-d', strtotime("$endDay $endMonth $endYear"));

                $query->andWhere(['and',
                    ['>=', $dateColumn, $startDate],
                    ['<=', $dateColumn, $endDate . ' 23:59:59']
                ]);
                break;
            case 'allTime':
                // No date filter
                break;
        }

        return $query;
    }

    public function calculateChange(int $current, int $previous): array
    {
        if ($previous === 0) {
            if ($current === 0) {
                return ['percentage' => null, 'direction' => 'neutral'];
            }
            return ['percentage' => '100%', 'direction' => 'up'];
        }

        $change = (($current - $previous) / $previous) * 100;
        $percentage = round(abs($change)) . '%';

        if ($change > 0) {
            return ['percentage' => $percentage, 'direction' => 'up'];
        } elseif ($change < 0) {
            return ['percentage' => $percentage, 'direction' => 'down'];
        }

        return ['percentage' => null, 'direction' => 'neutral'];
    }

    public function getChangeTooltip(string $targetDuration): string
    {
        $targetDuration = $this->getTargetDuration($targetDuration);

        switch ($targetDuration) {
            case 'daily':
                return 'Compared to previous day';
            case 'weekly':
                return 'Compared to previous week';
            case 'monthly':
                return 'Compared to previous month';
            case 'yearly':
                return 'Compared to previous year';
            case 'fiscalYear':
                return 'Compared to previous fiscal year';
            case 'allTime':
                return 'Compared to previous year';
            default:
                return 'Compared to previous period';
        }
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
