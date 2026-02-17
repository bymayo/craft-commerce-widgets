<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use yii\caching\TagDependency;

use Exception;

class Subscriptions extends Component
{

    private const ALLOWED_ORDER_BY = [
        'dateCreated desc' => 'dateCreated desc',
        'activeSubscriptions desc' => 'activeSubscriptions desc',
        'name asc' => 'name asc',
        'enabled desc' => 'enabled desc',
    ];

    public function getSubscriptionPlans(string $orderBy = 'dateCreated desc', int $limit = 5): array
    {

        try {

            $safeOrderBy = self::ALLOWED_ORDER_BY[$orderBy] ?? 'dateCreated desc';

            $query = (new Query())
                ->select([
                    'plans.*',
                    'COUNT(subscriptions.planId) as activeSubscriptions'
                ])
                ->from(['plans' => '{{%commerce_plans}}'])
                ->join('LEFT JOIN', '{{%commerce_subscriptions}} subscriptions', [
                    'and',
                    'subscriptions.planId = plans.id',
                    ['subscriptions.dateCanceled' => null],
                    ['subscriptions.dateExpired' => null],
                ])
                ->where(['plans.isArchived' => 0])
                ->groupBy(['plans.id'])
                ->orderBy($safeOrderBy)
                ->limit($limit);

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $query->cache($cacheDuration, $dependency)->all();

            return $result;

        }
        catch (Exception $e) {
            return [];
        }

    }

}
