<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use yii\caching\TagDependency;

use Exception;

class Subscriptions extends Component
{

    public function getSubscriptionPlans(string $orderBy = 'dateCreated desc', int $limit = 5): array
    {

        try {

            $query = (new Query())
                ->select([
                    'plans.*',
                    'COUNT(subscriptions.planId) as activeSubscriptions'
                ])
                ->from(['plans' => '{{%commerce_plans}}'])
                ->join('LEFT JOIN', '{{%commerce_subscriptions}} subscriptions', 'subscriptions.planId = plans.id')
                ->where(['plans.isArchived' => 0])
                ->groupBy(['plans.id'])
                ->orderBy($orderBy)
                ->limit($limit);

            $command = $query->createCommand();
            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $command->cache($cacheDuration, $dependency)->queryAll();

            return $result;

        }
        catch (Exception $e) {
            return [];
        }

    }

}
