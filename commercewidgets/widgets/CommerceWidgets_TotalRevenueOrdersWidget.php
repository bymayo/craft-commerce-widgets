<?php
/**
 * Commerce Widgets
 *
 * @author    Jason Mayo
 * @twitter   @madebymayo
 * @package   Commerce Widgets
 *
 */
namespace Craft;
class CommerceWidgets_TotalRevenueOrdersWidget extends BaseWidget
{

    /**
     * @return string
     */	
    public function getTitle()
    {
		return Craft::t('Total Revenue & Orders');
        return parent::getTitle();
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return Craft::t('CW - ' . $this->getTitle());
    }
    
    /**
     * @return string
     */
    public function getIconPath()
    {
        return craft()->path->getPluginsPath() . 'commercewidgets/resources/icon-mask.svg';
    }
    
    /**
     * @return mixed
     */
    public function getBodyHtml()
    {
	    
        craft()->templates->includeCssResource('commercewidgets/css/style.css');
        craft()->templates->includeJsResource('commercewidgets/js/script.js');
        
        return craft()->templates->render(
        	'commercewidgets/totalrevenueorders/body',
        	array(
	        	'today' => $this->getTotals(strtotime('now'), strtotime('now')),
	        	'yesterday' => $this->getTotals(strtotime('-1 day'), strtotime('-1 day')),
	        	'week' => $this->getTotals(strtotime('last Monday'), strtotime('next Sunday')),
	        	'month' => $this->getTotals(strtotime('first day of ' . date('F Y')), strtotime('last day of ' . date('F Y'))),
	        	'year' => $this->getTotals(strtotime('first day of January this year'), strtotime('last day of December this year')),
        	)
        );
        
    }
    
    /**
     * @return mixed
     */	
    public function getSettingsHtml()
    {
	    
	    $orderStatuses = craft()->commerce_orderStatuses->getAllOrderStatuses();

        return craft()->templates->render(
        	'commercewidgets/totalrevenueorders/settings', 
        	array(
				'settings' => $this->getSettings(),
				'orderStatuses' => $orderStatuses
			)
		);
        
    }
    
    /**
     * @return array
     */	
	public function getTotals($dateFrom, $dateTo) 
	{
		
		$orderStatusId = $this->getSettings()->orderStatusId;
		
		if ($orderStatusId == 0) {
			$orderStatusIdCondition = 'orderStatusId!=:value2';
			$orderStatusIdArgument[':value2'] = $orderStatusId;
		}
		else {
			$orderStatusIdCondition = 'orderStatusId=:value2';
			$orderStatusIdArgument[':value2'] = $orderStatusId;			
		}
		
		$query = craft()->db->createCommand()
			->select('COUNT(*) as orderTotal, sum(totalPrice) as revenueTotal')
			->from('commerce_orders')
			->where('dateOrdered>=:dateFrom', array(':dateFrom' => date('Y-m-d', $dateFrom) . ' 00:00:00'))
			->andWhere('dateOrdered<=:dateTo', array(':dateTo' => date('Y-m-d', $dateTo) . ' 23:59:59'))
			->andWhere($orderStatusIdCondition, $orderStatusIdArgument)
			->queryAll();
				
		$result = new \stdClass();
		
		$result->orderTotal = $query[0]['orderTotal'];
		$result->revenueTotal = $query[0]['revenueTotal'];
		$result->dateFrom = $dateFrom;
		$result->dateTo = $dateTo;
				
		return $result;
	
	}
     
    /**
     * @return array
     */	
    protected function defineSettings()
    {
        return array(
            'orderStatusId' => array(AttributeType::Number)
        );
    }
    
}
