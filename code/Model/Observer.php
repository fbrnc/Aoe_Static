<?php
/**
 * Observer model
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Fabrizio Branca <mail@fabrizio-branca.de>
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 */
class Aoe_Static_Model_Observer
{
    /**
     * @var Aoe_Static_Model_Config
     */
    protected $_config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_config = Mage::getSingleton('aoestatic/config');
    }

    /**
     * Check when varnish caching should be enabled.
     *
     * @param Varien_Event_Observer $observer
     * @return Aoe_Static_Model_Observer
     */
    public function processPreDispatch(Varien_Event_Observer $observer)
    {
        /* @var $event Varien_Event */
        $event = $observer->getEvent();
        /* @var $controllerAction Mage_Core_Controller_Varien_Action */
        $controllerAction = $event->getControllerAction();
        $fullActionName = $controllerAction->getFullActionName();

        /* @var $response Mage_Core_Controller_Response_Http */
        $response = $controllerAction->getResponse();

        // gather information for replace array
        $customerName = '';
        $loggedIn     = '0';
        $session      = Mage::getSingleton('customer/session');
        /* @var $session Mage_Customer_Model_Session */
        if ($session->isLoggedIn()) {
            $loggedIn     = '0';
            $customerName = Mage::helper('core')->escapeHtml($session->getCustomer()->getName());
        }

        $replace = array(
            '###FULLACTIONNAME###'      => $fullActionName,
            '###CUSTOMERNAME###'        => $customerName,
            '###ISLOGGEDIN###'          => $loggedIn,
            '###NUMBEROFITEMSINCART###' => Mage::helper('checkout/cart')->getSummaryCount(),
        );

        // apply default configuration in any case
        $defaultConf = $this->_config->getActionConfiguration('default');
        if ($defaultConf) {
            $this->applyConf($defaultConf, $response, $replace);
        }

        // check if there is a configured configuration for this full action name
        $conf = $this->_config->getActionConfiguration($fullActionName);
        if (!$conf) {
            // load the "uncached" configuration if no other configuration was found
            $conf = $this->_config->getActionConfiguration('uncached');
        }

        // apply the configuration
        if ($conf) {
            $this->applyConf($conf, $response, $replace);
        }

        $this->_applyCustomMaxAgeFromDb($controllerAction->getRequest(), $response);

        return $this;
    }

    /**
     * Apply configuration (set headers and cookies)
     *
     * @param Mage_Core_Model_Config_Element $conf
     * @param Mage_Core_Controller_Response_Http $response
     * @param array $replace
     */
    protected function applyConf(Mage_Core_Model_Config_Element $conf, Mage_Core_Controller_Response_Http $response,
        array $replace
    ) {
        foreach ($conf->headers->children() as $key => $value) {
            $value = str_replace(array_keys($replace), array_values($replace), $value);
            $response->setHeader($key, $value, true);
        }
        if ($conf->cookies) {
            $cookie = Mage::getModel('core/cookie');
            /* @var $cookie Mage_Core_Model_Cookie */
            foreach ($conf->cookies->children() as $name => $cookieConf) {
                if (1 == $cookieConf->disabled) {
                    continue;
                }
                $value    = (string) $cookieConf->value;
                $value    = str_replace(array_keys($replace), array_values($replace), $value);
                $period   = $cookieConf->period ? (string) $cookieConf->period : null;
                $path     = $cookieConf->path ? (string) $cookieConf->path : null;
                $domain   = $cookieConf->domain ? (string) $cookieConf->domain : null;
                $secure   = $cookieConf->secure ? filter_var($cookieConf->secure, FILTER_VALIDATE_BOOLEAN) : null;
                $httponly = $cookieConf->httponly ? filter_var($cookieConf->httponly, FILTER_VALIDATE_BOOLEAN) : null;

                $cookie->set($name, $value, $period, $path, $domain, $secure, $httponly);
            }
        }
    }

    /**
     * Apply custom Cache-Control: max-age from db
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     */
    protected function _applyCustomMaxAgeFromDb(Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        // apply custom max-age from db
        $urls = array($request->getRequestUri());
        $alias = $request->getAlias(Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS);
        if ($alias) {
            $urls[] = $alias;
        }
        /** @var $customUrlModel Aoe_Static_Model_CustomUrl */
        $customUrlModel = Mage::getModel('aoestatic/customUrl');
        $customUrlModel->setStoreId(Mage::app()->getStore()->getId());
        $customUrlModel->loadByRequestPath($urls);

        if ($customUrlModel->getId() && $customUrlModel->getMaxAge()) {
            $response->setHeader('Cache-Control', 'max-age=' . (int) $customUrlModel->getMaxAge(), true);
            $response->setHeader('X-Magento-Lifetime', (int) $customUrlModel->getMaxAge(), true);
            $response->setHeader('aoestatic', 'cache', true);
        }
    }

    /**
     * Add layout handle 'aoestatic_cacheable' or 'aoestatic_notcacheable'
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeLoadLayout(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        /* @var $event Varien_Event */
        $controllerAction = $event->getAction();
        /* @var $controllerAction Mage_Core_Controller_Varien_Action */
        $fullActionName = $controllerAction->getFullActionName();

        $conf = $this->_config->getActionConfiguration($fullActionName);

        $handle = $conf ? 'aoestatic_cacheable' : 'aoestatic_notcacheable';

        $observer->getEvent()->getLayout()->getUpdate()->addHandle($handle);
    }
}
