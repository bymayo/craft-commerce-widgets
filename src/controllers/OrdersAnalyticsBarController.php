<?php

namespace bymayo\commercewidgets\controllers;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\services\OrdersAnalyticsBar;

use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\controllers\ElementIndexesController;

use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Serves the stats shown above the Commerce order index.
 *
 * Extending Craft's own element index controller means the stats run against the exact same query the
 * index used to draw the table — source, order status, search, custom filters and Commerce's date range
 * are all applied for us by ElementIndexesController::elementQuery().
 */
class OrdersAnalyticsBarController extends ElementIndexesController
{

    /**
     * Order query attributes Commerce's index date range picker is allowed to filter on. Anything else
     * would be writing an arbitrary property onto the query, so we fall back to Commerce's own default.
     */
    private const ALLOWED_DATE_ATTRS = [
        'dateUpdated',
        'dateCreated',
        'dateOrdered',
        'datePaid',
        'dateAuthorized',
        'dateFirstPaid',
    ];

    /**
     * Commerce's date range picker treats the selected end date as inclusive, and encodes that as
     * `< endDate + 1 day`. We mirror that when working out the length of the selected window.
     */
    private const DAY_IN_SECONDS = 86400;

    public function actionGetStats(): Response
    {
        $this->requirePermission(OrdersAnalyticsBar::PERMISSION);

        if (!is_a($this->elementType, Order::class, true)) {
            throw new BadRequestHttpException('Order index stats are only available for orders.');
        }

        $query = $this->getElementQuery();

        if (!$query instanceof OrderQuery) {
            throw new BadRequestHttpException('Order index stats are only available for orders.');
        }

        $dateAttr = (string) ($this->request->getBodyParam('statsDateAttr') ?: 'dateUpdated');

        if (!in_array($dateAttr, self::ALLOWED_DATE_ATTRS, true)) {
            $dateAttr = 'dateUpdated';
        }

        [$previousDateParam, $changeTooltip] = $this->previousWindow();

        return $this->asJson([
            'stats' => CommerceWidgets::$plugin->ordersAnalyticsBar->getStats(
                $query,
                $previousDateParam,
                $dateAttr,
                $changeTooltip
            ),
        ]);
    }

    /**
     * Works out the window immediately before the one the index is filtered to, so each stat can be
     * compared against a period of equal length.
     *
     * Returns `[null, null]` unless both ends of the range are known — an open-ended range ("All", or a
     * custom From with no To) has no comparable preceding period.
     *
     * @return array{0: array|null, 1: string|null}
     */
    private function previousWindow(): array
    {
        $startDate = $this->request->getBodyParam('statsStartDate');
        $endDate = $this->request->getBodyParam('statsEndDate');

        if (!is_numeric($startDate) || !is_numeric($endDate)) {
            return [null, null];
        }

        $start = (int) floor($startDate / 1000);
        $end = (int) floor($endDate / 1000) + self::DAY_IN_SECONDS;
        $length = $end - $start;

        if ($length <= 0) {
            return [null, null];
        }

        $days = (int) round($length / self::DAY_IN_SECONDS);

        return [
            ['and', '>=' . ($start - $length), '<' . $start],
            $days === 1
                ? 'Compared to the previous day'
                : 'Compared to the previous ' . $days . ' days',
        ];
    }

}
