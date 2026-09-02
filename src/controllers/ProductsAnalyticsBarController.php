<?php

namespace bymayo\commercewidgets\controllers;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\services\ProductsAnalyticsBar;

use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Product;
use craft\controllers\ElementIndexesController;

use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Serves the stats shown above the Commerce product index.
 *
 * As with the order index bar, extending Craft's element index controller means the stats run against
 * the same query the index used to draw the table, so its source, search and filters all apply.
 */
class ProductsAnalyticsBarController extends ElementIndexesController
{

    public function actionGetStats(): Response
    {
        $this->requirePermission(ProductsAnalyticsBar::PERMISSION);

        if (!is_a($this->elementType, Product::class, true)) {
            throw new BadRequestHttpException('Product index analytics are only available for products.');
        }

        $query = $this->getElementQuery();

        if (!$query instanceof ProductQuery) {
            throw new BadRequestHttpException('Product index analytics are only available for products.');
        }

        return $this->asJson([
            'stats' => CommerceWidgets::$plugin->productsAnalyticsBar->getStats($query),
        ]);
    }

}
