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
class CommerceWidgets_CartAbandonmentWidget extends BaseWidget
{

    /**
     * @return string
     */	
    public function getTitle()
    {
		return Craft::t('Cart Abandonment');
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
        
        craft()->templates->includeJsResource('commercewidgets/js/plugins/chart.js');
        craft()->templates->includeJsResource('commercewidgets/js/script.js');
        
        return craft()->templates->render(
        	'commercewidgets/cartabandonment/body',
        	array(
	        	'cartCompleted' => $this->getOrdersByCompletedStatus('1'),
	        	'cartAbandoned' => $this->getOrdersByCompletedStatus('not 1'),
	        	'settings' => $this->getSettings()
        	)
        );
        
    }
    
    /**
     * @return mixed
     */	
    public function getSettingsHtml()
    {

        return craft()->templates->render(
        	'commercewidgets/cartabandonment/settings', 
        	array(
				'settings' => $this->getSettings()
			)
		);
        
    }
    
    /**
     * @return array
     */	
    public function getDateRange($start, $from, $to) 
    {
	    
		$dateStart = strtotime($start);
		
		$dateFrom = strtotime($from, $dateStart);
		$dateTo = strtotime($to, $dateStart);
		
		$dateRange = array('and', '> ' . date("Y-m-d", $dateFrom) . ' 00:00:00', '< ' . date("Y-m-d", $dateTo) . ' 23:59:59');
	    
	    return $dateRange;
	    
    }
    
    /**
     * @return array
     */	
	public function getOrdersByCompletedStatus($status) 
	{

		$dateRange = $this->getSettings()->cartAbandonmentDateRange;
		
		switch($dateRange) {
		    case 'day':
		        $dateCreated = $this->getDateRange('today', 'today', 'today');
		        break;
		    case 'week':
		        $dateCreated = $this->getDateRange('today', 'Monday this week', 'Sunday this week');
		        break;
		    case 'month':
		        $dateCreated = $this->getDateRange('this month', 'first day of this month', 'last day of this month');
		        break;
		    case 'year':
		        $dateCreated = $this->getDateRange('today', 'first day of January', 'last day of December');
		        break;
		}
	
		$criteria = craft()->elements->getCriteria('Commerce_Order');
		$criteria->isCompleted = $status;
		$criteria->dateCreated = $dateCreated;
		$criteria->limit = null;
		$criteria->find();
		
		return count($criteria);
	
	}
     
    /**
     * @return array
     */	
    protected function defineSettings()
    {
        return array(
            'cartAbandonmentDateRange' => array(AttributeType::String)
        );
    }
    
}