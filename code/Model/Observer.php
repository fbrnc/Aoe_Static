<?php
/**
 * Observer model
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author		Fabrizio Branca <mail@fabrizio-branca.de>
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 */
class Aoe_Static_Model_Observer
{
    /**
     * Check when varnish caching should be enabled.
     *
     * @param Varien_Event_Observer $observer
     * @return Aoe_Static_Model_Observer
     */
    public function processPreDispatch(Varien_Event_Observer $observer)
    {
        
        $helper = Mage::helper('aoestatic'); /* @var $helper Aoe_Static_Helper_Data */
        $controllerAction = $observer->getEvent()->getControllerAction();
        $response = $controllerAction->getResponse(); /* @var $response Mage_Core_Controller_Response_Http */
        $fullActionName = $controllerAction->getFullActionName();
        
        $lifetime = $helper->isCacheableAction($fullActionName);
        
        if ($lifetime) {
	        $response->setHeader('X-Magento-Lifetime', $lifetime, true); // Only for debugging and information
	        $response->setHeader('X-Magento-Action', $fullActionName, true); // Only for debugging and information
	        $response->setHeader('Cache-Control', 'max-age='. $lifetime, true);
	        $response->setHeader('aoestatic', 'cache', true);
        }
		
        return $this;
    }
}
