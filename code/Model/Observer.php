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
     * Local cache for calculated values of markers
     * @var null | array
     */
    protected $markersValues = null;

    /**
     * Indicates, if there are messages to show on the current page
     * @var bool
     */
    protected $messagesToShow = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_config = Mage::getSingleton('aoestatic/config');
    }

    /**
     * Set custom headers and cookies
     *
     * @param Varien_Event_Observer $observer
     * @return Aoe_Static_Model_Observer
     */
    public function processPostDispatch(Varien_Event_Observer $observer)
    {
        // check if we have messages to display
        $this->messagesToShow = $this->checkForMessages();

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

        $this->markersValues = array(
            '###FULLACTIONNAME###'      => $fullActionName,
            '###CUSTOMERNAME###'        => $customerName,
            '###ISLOGGEDIN###'          => $loggedIn,
            '###NUMBEROFITEMSINCART###' => Mage::helper('checkout/cart')->getSummaryCount(),
        );

        // apply default configuration in any case
        $defaultConf = $this->_config->getActionConfiguration('default');
        if ($defaultConf) {
            $this->applyConf($defaultConf, $response);
        }

        // check if there is a configured configuration for this full action name
        $conf = $this->_config->getActionConfiguration($fullActionName);
        if (!$conf) {
            // load the "uncached" configuration if no other configuration was found
            $conf = $this->_config->getActionConfiguration('uncached');
        }

        // apply the configuration
        if ($conf) {
            $this->applyConf($conf, $response);
        }

        $this->_applyCustomMaxAgeFromDb($controllerAction->getRequest(), $response);

        return $this;
    }

    /**
     * Apply configuration (set headers and cookies)
     *
     * @param Mage_Core_Model_Config_Element $conf
     * @param Mage_Core_Controller_Response_Http $response
     */
    protected function applyConf(Mage_Core_Model_Config_Element $conf, Mage_Core_Controller_Response_Http $response)
    {
        foreach ($conf->headers->children() as $key => $value) {
            // skip aoestatic header if we have messages to display
            if ($this->messagesToShow && ($key == 'aoestatic')) {
                continue;
            }
            $value = $this->replaceMarkers($value);
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
                $value    = $this->replaceMarkers($value);
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
        if (!$this->messagesToShow) {
            // apply custom max-age from db
            $urls = array($request->getRequestString());
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
    }

    /**
     * Add layout handle 'aoestatic_cacheable' or 'aoestatic_notcacheable'
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeLoadLayout(Varien_Event_Observer $observer)
    {
        // check if we have messages to display
        $this->messagesToShow = $this->checkForMessages();

        $event = $observer->getEvent();
        /* @var $event Varien_Event */
        $controllerAction = $event->getAction();
        /* @var $controllerAction Mage_Core_Controller_Varien_Action */
        $fullActionName = $controllerAction->getFullActionName();

        $conf = $this->_config->getActionConfiguration($fullActionName);

        $handle = $conf ? 'aoestatic_cacheable' : 'aoestatic_notcacheable';

        $observer->getEvent()->getLayout()->getUpdate()->addHandle($handle);
    }

    /**
     * Replaces markers in the $value, saves calculated value to the local cache
     *
     * @param string $value
     * @return string
     */
    protected function replaceMarkers($value) {
        $matches = array();
        preg_match_all('|###[^#]+###|', $value, $matches);
        $markersWithoutValues = array_diff($matches[0], array_keys($this->markersValues));
        foreach($markersWithoutValues as $marker) {
            $this->markersValues[$marker] = $this->getMarkerValue($marker);
        }
        $value = str_replace(array_keys($this->markersValues), array_values($this->markersValues), $value);
        return $value;
    }

    /**
     * Returns value of a given marker
     *
     * @param string $marker
     * @return string
     */
    protected function getMarkerValue($marker) {
        $markerValue = $marker;
        if (isset($this->markersValues[$marker]) && $this->markersValues[$marker] !== NULL) {
            $markerValue = $this->markersValues[$marker];
        } elseif ($this->_config->getMarkerCallback($marker)) {
            $markerValue = $this->executeCallback($this->_config->getMarkerCallback($marker));
        }
        return (string)$markerValue;
    }

    /**
     * Executes method defined in the marker callback configuration and returns the result
     *
     * @param string $callbackString
     * @return mixed
     */
    protected function executeCallback($callbackString) {
        $result = "";
        try {

            if ($callbackString) {
                if (!preg_match(Mage_Cron_Model_Observer::REGEX_RUN_MODEL, (string)$callbackString, $run)) {
                    Mage::throwException('Invalid model/method definition, expecting "model/class::method".');
                }
                if (!($model = Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
                    Mage::throwException('Invalid callback: %s::%s does not exist', $run[1], $run[2]);
                }
                $callback = array($model, $run[2]);
                $arguments = array();
            }
            if (empty($callback)) {
                Mage::throwException(Mage::helper('cron')->__('No callbacks found for marker'));
            }

            $result = call_user_func_array($callback, $arguments);

        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $result;
    }

    /**
     * Checks if there are messages to display
     *
     * @return bool
     */
    protected function checkForMessages()
    {
        if (
            (false === $this->messagesToShow) &&
            (Mage::app()->getLayout()->getMessagesBlock()->getMessageCollection()->count() > 0)
        ) {
            $this->messagesToShow = true;
        }

        return $this->messagesToShow;
    }
}
