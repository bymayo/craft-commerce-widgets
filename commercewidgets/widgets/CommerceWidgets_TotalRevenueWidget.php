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
class CommerceWidgets_TotalRevenueWidget extends BaseWidget
{

    /**
     * @return string
     */	
    public function getTitle()
    {
		return Craft::t('Total Revenue');
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
        	'commercewidgets/totalrevenue/body',
        	array(
	        	'today' => $this->getTotals(date('Y-m-d') . ' 00:00:00', date('Y-m-d'))
        	)
        );
        
    }
    
    /**
     * @return mixed
     */	
    public function getSettingsHtml()
    {

        return craft()->templates->render(
        	'commercewidgets/totalrevenue/settings', 
        	array(
				'settings' => $this->getSettings()
			)
		);
        
    }
    
    /**
     * @return array
     */	
	public function getTotals($dateFrom, $dateTo) 
	{
		
		$query = craft()->db->createCommand()
			->select('*')
			->from('commerce_orders')
			->where('dateOrdered >= 2016-09-08 00:00:00')
			->queryAll();
				
		
/*
		$date = new DateTime();
		$timezone = new \DateTimeZone(DateTime::UTC);
		$date = $date->format(DateTime::MYSQL_DATETIME, $timezone);
	
		$criteria = craft()->elements->getCriteria('Commerce_Order');
		$criteria->after = $dateFrom;
*/
		
// 		[£12.00, 12]
				
		return $query;
	
	}
     
    /**
     * @return array
     */	
    protected function defineSettings()
    {
        return array(
            'limit' => array(AttributeType::Number, 'default' => 10)
        );
    }
    
}