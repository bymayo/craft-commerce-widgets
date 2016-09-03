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
class CommerceWidgets_ProductsRecentWidget extends BaseWidget
{

    /**
     * @return string
     */	
    public function getTitle()
    {
		return Craft::t('Recent Products');
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
        	'commercewidgets/productsrecent/body',
        	array(
	        	'products' => $this->getProducts()
        	)
        );
        
    }
    
    /**
     * @return mixed
     */	
    public function getSettingsHtml()
    {

        return craft()->templates->render(
        	'commercewidgets/productsrecent/settings', 
        	array(
				'settings' => $this->getSettings()
			)
		);
        
    }
    
    /**
     * @return array
     */	
	public function getProducts() 
	{
	
		$criteria = craft()->elements->getCriteria('Commerce_Product');
		$criteria->limit = $this->getSettings()->limit;
		return $criteria->find();
	
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