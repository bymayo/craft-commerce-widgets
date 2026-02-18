<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\base\Component;
use craft\db\Query;

use DateTime;
use DateTimeZone;

class Helpers extends Component
{

    public function getPluginName(): string
    {
        return CommerceWidgets::$plugin->getSettings()->pluginName ?: 'Commerce Widgets';
    }

    public function craftNow(): DateTime
    {
        return new DateTime('now', new DateTimeZone(Craft::$app->getTimeZone()));
    }

    public function craftDate(string $dateString): DateTime
    {
        return new DateTime($dateString, new DateTimeZone(Craft::$app->getTimeZone()));
    }

    public function toUtc(DateTime $dt): string
    {
        $utc = clone $dt;
        $utc->setTimezone(new DateTimeZone('UTC'));
        return $utc->format('Y-m-d H:i:s');
    }

    public function getTargetDuration($targetDuration): string
    {
        if ($targetDuration === 'default' || $targetDuration === null) {
            return CommerceWidgets::$plugin->getSettings()->defaultTargetDuration;
        }

        return $targetDuration;
    }

    public function getFiscalYearDates(): array
    {
        $settings = CommerceWidgets::$plugin->getSettings();
        $startDay = (int) $settings->fiscalYearStartDay;
        $startMonth = $settings->fiscalYearStartMonth;
        $endDay = (int) $settings->fiscalYearEndDay;
        $endMonth = $settings->fiscalYearEndMonth;

        $now = $this->craftNow();
        $fiscalMonth = (int) $this->craftDate("1 $startMonth")->format('n');
        $currentMonth = (int) $now->format('n');
        $currentDay = (int) $now->format('j');
        $currentYear = (int) $now->format('Y');

        if ($currentMonth > $fiscalMonth || ($currentMonth == $fiscalMonth && $currentDay >= $startDay)) {
            $startYear = $currentYear;
        } else {
            $startYear = $currentYear - 1;
        }
        $endYear = $startYear + 1;

        $start = $this->craftDate("$startDay $startMonth $startYear");
        $start->setTime(0, 0, 0);
        $end = $this->craftDate("$endDay $endMonth $endYear");
        $end->setTime(23, 59, 59);
        $prevStart = $this->craftDate("$startDay $startMonth " . ($startYear - 1));
        $prevStart->setTime(0, 0, 0);
        $prevEnd = $this->craftDate("$endDay $endMonth $startYear");
        $prevEnd->setTime(23, 59, 59);

        return [
            'start' => $this->toUtc($start),
            'end' => $this->toUtc($end),
            'prevStart' => $this->toUtc($prevStart),
            'prevEnd' => $this->toUtc($prevEnd),
            'startYear' => $startYear,
            'endYear' => $endYear,
            'startDay' => $startDay,
            'startMonth' => $startMonth,
            'endDay' => $endDay,
            'endMonth' => $endMonth,
        ];
    }

    public function applyDateFilter(Query $query, string $targetDuration, string $dateColumn = 'orders.datePaid'): Query
    {
        $targetDuration = $this->getTargetDuration($targetDuration);
        $now = $this->craftNow();

        switch ($targetDuration) {
            case 'daily':
                $start = (clone $now)->setTime(0, 0, 0);
                $end = (clone $now)->setTime(23, 59, 59);
                $query->andWhere(['between', $dateColumn, $this->toUtc($start), $this->toUtc($end)]);
                break;
            case 'weekly':
                $start = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
                $end = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                $query->andWhere(['between', $dateColumn, $this->toUtc($start), $this->toUtc($end)]);
                break;
            case 'monthly':
                $start = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
                $end = (clone $now)->modify('last day of this month')->setTime(23, 59, 59);
                $query->andWhere(['between', $dateColumn, $this->toUtc($start), $this->toUtc($end)]);
                break;
            case 'yearly':
                $start = (clone $now)->modify('first day of january this year')->setTime(0, 0, 0);
                $end = (clone $now)->modify('last day of december this year')->setTime(23, 59, 59);
                $query->andWhere(['between', $dateColumn, $this->toUtc($start), $this->toUtc($end)]);
                break;
            case 'fiscalYear':
                $fiscal = $this->getFiscalYearDates();
                $query->andWhere(['between', $dateColumn, $fiscal['start'], $fiscal['end']]);
                break;
            case 'allTime':
                break;
        }

        return $query;
    }

    public function getDateRange(string $targetDuration, string $dateColumn = 'orders.dateCreated'): array
    {
        $targetDuration = $this->getTargetDuration($targetDuration);
        $now = $this->craftNow();
        $toDate = (CommerceWidgets::$plugin->getSettings()->comparisonMode ?? 'full') === 'toDate';

        switch ($targetDuration) {
            case 'daily':
                $todayStart = (clone $now)->setTime(0, 0, 0);
                $todayEnd = (clone $now)->setTime(23, 59, 59);
                $yesterdayStart = (clone $todayStart)->modify('-1 day');
                $yesterdayEnd = (clone $todayEnd)->modify('-1 day');
                return [
                    'current' => ['between', $dateColumn, $this->toUtc($todayStart), $this->toUtc($todayEnd)],
                    'previous' => ['between', $dateColumn, $this->toUtc($yesterdayStart), $this->toUtc($yesterdayEnd)],
                ];
            case 'weekly':
                $thisWeekStart = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
                $thisWeekEnd = (clone $thisWeekStart)->modify('+6 days')->setTime(23, 59, 59);
                $lastWeekStart = (clone $thisWeekStart)->modify('-7 days');
                if ($toDate) {
                    $thisWeekEnd = clone $now;
                    $elapsed = $thisWeekStart->diff($now);
                    $lastWeekEnd = (clone $lastWeekStart)->add($elapsed);
                } else {
                    $lastWeekEnd = (clone $thisWeekEnd)->modify('-7 days');
                }
                return [
                    'current' => ['between', $dateColumn, $this->toUtc($thisWeekStart), $this->toUtc($thisWeekEnd)],
                    'previous' => ['between', $dateColumn, $this->toUtc($lastWeekStart), $this->toUtc($lastWeekEnd)],
                ];
            case 'monthly':
                $thisMonthStart = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
                $thisMonthEnd = (clone $now)->modify('last day of this month')->setTime(23, 59, 59);
                $lastMonthStart = (clone $now)->modify('first day of last month')->setTime(0, 0, 0);
                if ($toDate) {
                    $thisMonthEnd = clone $now;
                    $elapsed = $thisMonthStart->diff($now);
                    $lastMonthEnd = (clone $lastMonthStart)->add($elapsed);
                } else {
                    $lastMonthEnd = (clone $now)->modify('last day of last month')->setTime(23, 59, 59);
                }
                return [
                    'current' => ['between', $dateColumn, $this->toUtc($thisMonthStart), $this->toUtc($thisMonthEnd)],
                    'previous' => ['between', $dateColumn, $this->toUtc($lastMonthStart), $this->toUtc($lastMonthEnd)],
                ];
            case 'yearly':
                $thisYearStart = (clone $now)->modify('first day of january this year')->setTime(0, 0, 0);
                $lastYearStart = (clone $thisYearStart)->modify('-1 year');
                if ($toDate) {
                    $thisYearEnd = clone $now;
                    $elapsed = $thisYearStart->diff($now);
                    $lastYearEnd = (clone $lastYearStart)->add($elapsed);
                } else {
                    $thisYearEnd = (clone $now)->modify('last day of december this year')->setTime(23, 59, 59);
                    $lastYearEnd = (clone $thisYearEnd)->modify('-1 year');
                }
                return [
                    'current' => ['between', $dateColumn, $this->toUtc($thisYearStart), $this->toUtc($thisYearEnd)],
                    'previous' => ['between', $dateColumn, $this->toUtc($lastYearStart), $this->toUtc($lastYearEnd)],
                ];
            case 'fiscalYear':
                $fiscal = $this->getFiscalYearDates();
                if ($toDate) {
                    $currentEnd = $this->toUtc($now);
                    $fiscalStart = $this->craftDate($fiscal['startDay'] . ' ' . $fiscal['startMonth'] . ' ' . $fiscal['startYear']);
                    $fiscalStart->setTime(0, 0, 0);
                    $elapsed = $fiscalStart->diff($now);
                    $prevFiscalStart = $this->craftDate($fiscal['startDay'] . ' ' . $fiscal['startMonth'] . ' ' . ($fiscal['startYear'] - 1));
                    $prevFiscalStart->setTime(0, 0, 0);
                    $prevEnd = (clone $prevFiscalStart)->add($elapsed);
                    return [
                        'current' => ['between', $dateColumn, $fiscal['start'], $currentEnd],
                        'previous' => ['between', $dateColumn, $fiscal['prevStart'], $this->toUtc($prevEnd)],
                    ];
                }
                return [
                    'current' => ['between', $dateColumn, $fiscal['start'], $fiscal['end']],
                    'previous' => ['between', $dateColumn, $fiscal['prevStart'], $fiscal['prevEnd']],
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
        $toDate = (CommerceWidgets::$plugin->getSettings()->comparisonMode ?? 'full') === 'toDate';
        $suffix = $toDate ? ' (to date)' : '';

        switch ($targetDuration) {
            case 'daily':
                return 'Compared to previous day';
            case 'weekly':
                return 'Compared to previous week' . $suffix;
            case 'monthly':
                return 'Compared to previous month' . $suffix;
            case 'yearly':
                return 'Compared to previous year' . $suffix;
            case 'fiscalYear':
                return 'Compared to previous fiscal year' . $suffix;
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
        $now = $this->craftNow();

        switch ($targetDuration) {
            case 'daily':
                return $now->format('j F Y');
            case 'weekly':
                $weekStart = $settings->weekStart ?? 'monday';
                $tomorrow = (clone $now)->modify('+1 day');
                $start = (clone $tomorrow)->modify("last $weekStart");
                $end = (clone $start)->modify('+6 days');
                if ($start->format('F') === $end->format('F')) {
                    return $start->format('j') . ' - ' . $end->format('j F Y');
                }
                return $start->format('j F') . ' - ' . $end->format('j F Y');
            case 'fiscalYear':
                $fiscal = $this->getFiscalYearDates();
                $startMonth = ucfirst($fiscal['startMonth']);
                $endMonth = ucfirst($fiscal['endMonth']);
                return "{$fiscal['startDay']} {$startMonth} {$fiscal['startYear']} - {$fiscal['endDay']} {$endMonth} {$fiscal['endYear']}";
            case 'yearly':
                return $now->format('Y');
            case 'allTime':
                return 'All Time';
            default:
                return $now->format('F Y');
        }
    }

}
