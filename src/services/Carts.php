<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;

use craft\base\Component;
use craft\db\Query;
use yii\caching\TagDependency;

use Exception;

class Carts extends Component
{

    public function getMonthDateRange(int $previousAmount = 4): array
    {

        $currentMonth = strtotime('next month');
        $monthArray = [];

        for ($i = $previousAmount; $i >= 1; $i--) {
            $monthArray[] = date('M', strtotime("-$i month", $currentMonth));
        }

        return $monthArray;

    }

    public function getTotalCarts(int $isCompleted, int $previousAmount = 4): array
    {

        $data = [];

        try {

            $query = (new Query())
                ->select([
                    'DATE_FORMAT(orders.dateCreated, "%b") AS month',
                    'COALESCE(COUNT(orders.id), 0) AS count'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where([
                    'between', 'orders.dateCreated', date('Y-m-d', strtotime('-5 months')), date('Y-m-d', strtotime('+1 day'))
                ])
                ->andWhere(['orders.isCompleted' => $isCompleted])
                ->andWhere(['elements.dateDeleted' => null])
                ->groupBy('month')
                ->orderBy('month');

            $command = $query->createCommand();
            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $command->cache($cacheDuration, $dependency)->queryAll();

            foreach ($this->getMonthDateRange($previousAmount) as $month) {
                $key = array_search($month, array_column($result, 'month'));
                if ($key !== '' and $key !== false) {
                    $data[] = ($result[$key]['month'] == $month ? $result[$key]['count'] : 0);
                }
                else {
                    $data[] = 0;
                }
            }

            return $data;

        }
        catch (Exception $e) {
            return [];
        }

    }

    public function getCartTotalRevenue(int $isCompleted): ?array
    {

        try {

            $query = (new Query())
                ->select([
                    'COALESCE(sum(orders.totalPrice), 0) as totalPrice',
                    'COALESCE(count(orders.id), 0) as count'
                ])
                ->from(['orders' => '{{%commerce_orders}}'])
                ->join('INNER JOIN', '{{%elements}} elements', 'elements.id = orders.id')
                ->where([
                    'orders.isCompleted' => $isCompleted,
                    'DATE_FORMAT(orders.dateCreated, "%c-%Y")' => date('n-Y'),
                ])
                ->andWhere(['elements.dateDeleted' => null]);

            $cacheDuration = CommerceWidgets::$plugin->getSettings()->cacheDuration ?? 3600;
            $dependency = new TagDependency(['tags' => 'commerce-widgets']);
            $result = $query->cache($cacheDuration, $dependency)->one();

            return $result;

        }
        catch (Exception $e) {
            return null;
        }

    }

}
