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
		$event = $observer->getEvent(); /* @var $event Varien_Event */
		$controllerAction = $event->getControllerAction(); /* @var $controllerAction Mage_Core_Controller_Varien_Action */
		$fullActionName = $controllerAction->getFullActionName();

		$lifetime = $helper->isCacheableAction($fullActionName);

		if ($lifetime) {
			$response = $controllerAction->getResponse(); /* @var $response Mage_Core_Controller_Response_Http */
			$response->setHeader('X-Magento-Lifetime', $lifetime, true); // Only for debugging and information
			$response->setHeader('X-Magento-Action', $fullActionName, true); // Only for debugging and information
			$response->setHeader('Cache-Control', 'max-age='. $lifetime, true);
			$response->setHeader('aoestatic', 'cache', true);
		}

		return $this;
	}

	/**
	 * Add layout handle 'aoestatic_cacheable' or 'aoestatic_notcacheable'
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function beforeLoadLayout(Varien_Event_Observer $observer)
	{
		$helper = Mage::helper('aoestatic'); /* @var $helper Aoe_Static_Helper_Data */
		$event = $observer->getEvent(); /* @var $event Varien_Event */
		$controllerAction = $event->getAction(); /* @var $controllerAction Mage_Core_Controller_Varien_Action */
		$fullActionName = $controllerAction->getFullActionName();

		$lifetime = $helper->isCacheableAction($fullActionName);

		$handle = $lifetime ? 'aoestatic_cacheable' : 'aoestatic_notcacheable';

		$observer->getEvent()->getLayout()->getUpdate()->addHandle($handle);
	}
}
