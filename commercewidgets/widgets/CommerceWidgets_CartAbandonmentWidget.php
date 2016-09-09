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
	        	'cartAbandoned' => $this->getOrdersByCompletedStatus('0'),
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
	public function getOrdersByCompletedStatus($status) 
	{

		$dateRange = $this->getSettings()->cartAbandonmentDateRange;

		if ($dateRange == 'day') {
			$dateCreated = array('and', '>= ' . date('Y-m-d', strtotime('now')), '<= ' . date('Y-m-d', strtotime('now')));
		}
		elseif ($dateRange = 'week') {
			$dateCreated = array('and', '>= ' . date('Y-m-d', strtotime('last Monday')), '<= ' . date('Y-m-d', strtotime('next Sunday')));
		}
		elseif ($dateRange = 'month') {
			$dateCreated = array('and', '>= ' . date('Y-m-d', strtotime('first day of ' . date('F Y'))), '<= ' . date('Y-m-d', strtotime('last day of ' . date('F Y'))));
		}
		elseif ($dateRange = 'year') {
			$dateCreated = array('and', '>= ' . date('Y-m-d H:i:s', strtotime('first day of ' . date('Y'))), '<= ' . date('Y-m-d H:i:s', strtotime('last day of ' . date( 'Y'))));
		}
	
		$criteria = craft()->elements->getCriteria('Commerce_Order');
		$criteria->isCompleted = $status;
		$criteria->dateCreated = $dateCreated;
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