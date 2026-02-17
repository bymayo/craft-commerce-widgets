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
            case 'daily':
                $query->andWhere([
                    "DATE_FORMAT($dateColumn, \"%Y-%m-%d\")" => date('Y-m-d')
                ]);
                break;
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

    public function getDateRange(string $targetDuration, string $dateColumn = 'orders.dateCreated'): array
    {
        $targetDuration = $this->getTargetDuration($targetDuration);
        $settings = CommerceWidgets::$plugin->getSettings();

        switch ($targetDuration) {
            case 'daily':
                return [
                    'current' => ["DATE_FORMAT($dateColumn, \"%Y-%m-%d\")" => date('Y-m-d')],
                    'previous' => ["DATE_FORMAT($dateColumn, \"%Y-%m-%d\")" => date('Y-m-d', strtotime('-1 day'))],
                ];
            case 'weekly':
                return [
                    'current' => ['between', $dateColumn, date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week')) . ' 23:59:59'],
                    'previous' => ['between', $dateColumn, date('Y-m-d', strtotime('monday last week')), date('Y-m-d', strtotime('sunday last week')) . ' 23:59:59'],
                ];
            case 'monthly':
                return [
                    'current' => ['between', $dateColumn, date('Y-m-d', strtotime('first day of this month')), date('Y-m-d', strtotime('last day of this month')) . ' 23:59:59'],
                    'previous' => ['between', $dateColumn, date('Y-m-d', strtotime('first day of last month')), date('Y-m-d', strtotime('last day of last month')) . ' 23:59:59'],
                ];
            case 'yearly':
                return [
                    'current' => ["YEAR($dateColumn)" => date('Y')],
                    'previous' => ["YEAR($dateColumn)" => date('Y', strtotime('-1 year'))],
                ];
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

                $fiscalStart = date('Y-m-d', strtotime("$startDay $startMonth $startYear"));
                $fiscalEnd = date('Y-m-d', strtotime("$endDay $endMonth $endYear"));
                $prevFiscalStart = date('Y-m-d', strtotime("$startDay $startMonth " . ($startYear - 1)));
                $prevFiscalEnd = date('Y-m-d', strtotime("$endDay $endMonth $startYear"));

                return [
                    'current' => ['between', $dateColumn, $fiscalStart, $fiscalEnd . ' 23:59:59'],
                    'previous' => ['between', $dateColumn, $prevFiscalStart, $prevFiscalEnd . ' 23:59:59'],
                ];
            case 'allTime':
            default:
                return [
                    'current' => null,
                    'previous' => null,
                ];
        }
    }

    public function calculateChange(float $current, float $previous): array
    {
        if ($previous == 0) {
            if ($current == 0) {
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
