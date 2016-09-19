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
class CommerceWidgets_TopCustomersWidget extends BaseWidget
{

    /**
     * @return string
     */	
    public function getTitle()
    {
		return Craft::t('Top Customers');
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
        	'commercewidgets/topcustomers/body',
        	array(
	        	'customers' => $this->getCustomers(),
	        	'settings' => $this->getSettings()
        	)
        );
        
    }
    
    public function getCustomers()
    {
	    
	    if ($this->getSettings()->includeGuestOrders === 'yes') {
	    
			$query = craft()->db->createCommand()
				->select('count(*) as totalOrders, SUM(totalPrice) as totalRevenue, email, customerId')
				->from('commerce_orders')
				->where('isCompleted = 1')
				->group($this->getSettings()->groupBy)
				->order($this->getSettings()->orderBy . ' desc')
				->limit($this->getSettings()->limit)
				->queryAll();
			
	    }
	    else {
		    
			$query = craft()->db->createCommand()
				->select('count(*) as totalOrders, SUM(orders.totalPrice) as totalRevenue, orders.email, orders.customerId')
				->from('commerce_orders orders')
				->join('commerce_customers customers', 'orders.customerId = customers.id')
				->where('orders.customerId = customers.id')
				->andWhere('customers.userId IS NOT NULL')
				->andWhere('orders.isCompleted = 1')
				->group($this->getSettings()->groupBy)
				->order($this->getSettings()->orderBy . ' desc')
				->limit($this->getSettings()->limit)
				->queryAll();
		   
	    }
			
		return $query;
	    
    }
    
    /**
     * @return mixed
     */	
    public function getSettingsHtml()
    {

        return craft()->templates->render(
        	'commercewidgets/topcustomers/settings', 
        	array(
				'settings' => $this->getSettings()
			)
		);
        
    }
     
    /**
     * @return array
     */	
    protected function defineSettings()
    {
        return array(
            'limit' => array(AttributeType::Number, 'default' => 5),
            'includeGuestOrders' => array(AttributeType::String, 'default' => 'yes'),
            'groupBy' => array(AttributeType::String, 'default' => 'email'),
            'orderBy' => array(AttributeType::String, 'default' => 'totalRevenue')
        );
    }
    
}