<?php
/**
 * Observer model
 *
 * @author Fabrizio Branca
 */
class Aoe_Static_Model_Observer
{
    /**
     * Indicates, if there are messages to show on the current page
     * @var bool
     */
    protected $messagesToShow = false;

    /**
     * Temporary storage for already processed entries
     *
     * @var array
     */
    public $_processedTags = array();

    const REGISTRY_SKIPPABLE_NAME = 'aoestatic_skippableProductsForPurging';

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

        /** @var Aoe_Static_Model_Cache_Marker $cacheMarker */
        $cacheMarker = Mage::getSingleton('aoestatic/cache_marker');
        $cacheMarker->addMarkerValues(array(
            '###FULLACTIONNAME###'      => $fullActionName,
            '###CUSTOMERNAME###'        => $customerName,
            '###ISLOGGEDIN###'          => $loggedIn,
            '###NUMBEROFITEMSINCART###' => Mage::helper('checkout/cart')->getSummaryCount(),
        ));

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

        if (!$this->messagesToShow) {
            /** @var Aoe_Static_Model_Cache_Control $cacheControl */
            $cacheControl = Mage::getSingleton('aoestatic/cache_control');
            $cacheControl->addCustomUrlMaxAge($controllerAction->getRequest());
            $cacheControl->collectTags();
            $cacheControl->applyCacheHeaders();
        }

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
        /** @var Aoe_Static_Model_Cache_Marker $cacheMarker */
        $cacheMarker = Mage::getSingleton('aoestatic/cache_marker');
        if (property_exists($conf, 'headers')) {
            foreach ($conf->headers->children() as $key => $value) {
                // skip aoestatic header if we have messages to display
                if ($this->messagesToShow && ($key == 'aoestatic')) {
                    continue;
                }
                $value = $cacheMarker->replaceMarkers($value);
                $response->setHeader($key, $value, true);
            }
        }
        if (property_exists($conf, 'cookies') && $conf->cookies) {
            $cookie = Mage::getModel('core/cookie');
            /* @var $cookie Mage_Core_Model_Cookie */
            foreach ($conf->cookies->children() as $name => $cookieConf) {
                if (1 == $cookieConf->disabled) {
                    continue;
                }
                $scope    = $cookieConf->scope ? (string) $cookieConf->scope : 'customer';
                $value    = (string) $cookieConf->value;
                $value    = $cacheMarker->replaceMarkers($value);
                $period   = $cookieConf->period ? (string) $cookieConf->period : null;
                $path     = $cookieConf->path ? (string) $cookieConf->path : null;
                $domain   = $cookieConf->domain ? (string) $cookieConf->domain : null;
                $secure   = $cookieConf->secure ? filter_var($cookieConf->secure, FILTER_VALIDATE_BOOLEAN) : null;
                $httponly = $cookieConf->httponly ? filter_var($cookieConf->httponly, FILTER_VALIDATE_BOOLEAN) : null;

                $scopePart = '';
                if (!in_array($scope, array('customer', 'global', 'website', 'store'))) {
                    Mage::log("[AOE_Static::applyConf] Invalid scope '$scope'", Zend_Log::ERR);
                } else {
                    if ($scope == 'customer') {
                        $scopePart = Mage::getStoreConfig('customer/account_share/scope') == 0 ? 'g' : 'w';
                    } else if ($scope == 'global') {
                        $scopePart = 'g';
                    } elseif ($scope == 'website') {
                        $scopePart = 'w'.Mage::app()->getWebsite()->getId();
                    } elseif ($scope == 'store') {
                        $scopePart = 's'.Mage::app()->getStore()->getId();
                    }
                }

                $name = 'aoestatic_' . $scopePart . '_' . $name;

                $cookie->set($name, $value, $period, $path, $domain, $secure, $httponly);
            }
        }
        if (property_exists($conf, 'cache') && $conf->cache) {
            Mage::getSingleton('aoestatic/cache_control')->addMaxAge($conf->cache->maxage);
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

        /* @var $controllerAction Mage_Core_Controller_Varien_Action */
        $controllerAction = $observer->getAction();
        $fullActionName = $controllerAction->getFullActionName();

        $handle = $this->_config->getActionConfiguration($fullActionName) ? 'aoestatic_cacheable' : 'aoestatic_notcacheable';

        $observer->getLayout()->getUpdate()->addHandle($handle);
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

    /**
     * collect tags
     *
     * @param Varien_Event_Observer $observer
     */
    public function coreBlockAbstractToHtmlAfter(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();

        if ($block instanceof Mage_Cms_Block_Block && $block->getBlock()) {
            Mage::getSingleton('aoestatic/cache_control')->addTag('block-' . $block->getBlock()->getId());
        } else if ($block instanceof Mage_Cms_Block_Page) {
            Mage::getSingleton('aoestatic/cache_control')->addTag('page-' . ($block->getPageId() ?: ($block->getPage() ? $block->getPage()->getId() : Mage::getSingleton('cms/page')->getId())));
        } else if (($block instanceof Mage_Catalog_Block_Product_Abstract) && $block->getProductCollection()) {
            $tags = array();
            foreach ($block->getProductCollection()->getLoadedIds() as $id) {
                $tags[] = 'product-' . $id;
            }
            Mage::getSingleton('aoestatic/cache_control')->addTag($tags);
        }
    }

    /**
     * manual cache purging of specified URLs
     *
     * @param Varien_Event_Observer $event
     */
    public function controllerActionPredispatchAdminhtmlCacheIndex(Varien_Event_Observer $event)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $event->getControllerAction()->getRequest();

        if ($purgeUrls = $request->getParam('aoe_purge_urls')) {
            $purgeUrls = array_map('trim', explode("\n", trim($purgeUrls)));

            foreach (Mage::helper('aoestatic')->purge($purgeUrls, false) as $message) {
                Mage::getSingleton('adminhtml/session')->addNotice($message);
            }

            /** @var Mage_Core_Controller_Response_Http $response */
            $response = $event->getControllerAction()->getResponse();
            $response->setRedirect(Mage::getModel('adminhtml/url')->getUrl('*/cache/index', array()));
            $response->sendResponse();
            exit;
        }

        if ($purgeTags = $request->getParam('aoe_purge_tags')) {
            $purgeTags = array_map('trim', explode("\n", trim($purgeTags)));

            foreach (Mage::helper('aoestatic')->purgeTags($purgeTags, false) as $message) {
                Mage::getSingleton('adminhtml/session')->addNotice($message);
            }

            /** @var Mage_Core_Controller_Response_Http $response */
            $response = $event->getControllerAction()->getResponse();
            $response->setRedirect(Mage::getModel('adminhtml/url')->getUrl('*/cache/index', array()));
            $response->sendResponse();
            exit;
        }
    }

    /**
     * @see Mage_Core_Model_Cache
     *
     * @param Mage_Core_Model_Observer $observer
     * @return Aoe_Static_Model_Observer
     */
    public function catalogCategorySaveCommitAfter($observer)
    {
        /** @var $category Mage_Catalog_Model_Category */
        $category = $observer->getCategory();
        if ($category->getData('include_in_menu')) {
            // notify user that varnish needs to be refreshed
            Mage::app()->getCacheInstance()->invalidateType(array('aoestatic'));
        }
        return $this;
    }

    /**
     * Listens to application_clean_cache event and gets notified when a product/category/cms model is saved
     *
     * @param $observer Mage_Core_Model_Observer
     * @return Aoe_Static_Model_Observer
     */
    public function applicationCleanCache($observer)
    {
        // if Varnish is not enabled on admin don't do anything
        if (!Mage::app()->useCache('aoestatic')) {
            return $this;
        }

        /** @var Aoe_Static_Helper_Data $helper */
        $helper = Mage::helper('aoestatic');
        /** @var Mage_Adminhtml_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        $tags = $observer->getTags();

        // check if we should process tags from product which has no relevant changes
        $skippableProductIds = Mage::registry(self::REGISTRY_SKIPPABLE_NAME);
        if (null !== $skippableProductIds) {
            foreach ((array) $tags as $tag) {
                if (preg_match('/^catalog_product_(\d+)?/', $tag, $match)) {
                    if (isset($match[1]) && in_array($match[1], $skippableProductIds)) {
                        return $this;
                    }
                }
            }
        }

        $purgetags = array();
        if ($tags == array()) {
            $errors = Mage::helper('aoestatic')->purgeAll();
            if (!empty($errors)) {
                $session->addError($helper->__("Static Purge failed"));
            } else {
                $session->addSuccess($helper->__("The Static cache storage has been flushed."));
            }

            return $this;
        }

        // compute the urls for affected entities
        foreach ((array)$tags as $tag) {
            if (in_array($tag, $this->_processedTags)) {
                continue;
            }

            $this->_processedTags[] = $tag;

            //catalog_product_100 or catalog_category_186
            $tag_fields = explode('_', $tag);
            if (count($tag_fields) == 3) {
                if (in_array($tag_fields[1], array('product', 'category', 'page', 'block'))) {
                    $purgetags[] = $tag_fields[1] . '-' . $tag_fields[2];
                }
            }
        }
        if (!empty($purgetags)) {
            $errors = Mage::helper('aoestatic')->purgeTags($purgetags);
            if (!empty($errors)) {
                $session->addError($helper->__("Some Static purges failed: <br/>") . implode("<br/>", $errors));
            } else {
                $count = count($purgetags);
                if ($count > 5) {
                    $purgetags = array_slice($purgetags, 0, 5);
                    $purgetags[] = '...';
                    $purgetags[] = $helper->__("(Total number of purged urls: %d)", $count);
                }
                $session->addSuccess(
                    $helper->__("Tag purges have been submitted successfully:<br/>") . implode("<br />", $purgetags)
                );
            }
        }
        return $this;
    }

    public function controllerActionPredispatchAdminhtmlCacheMassRefresh(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $observer->getControllerAction()->getRequest();
        $types = $request->getParam('types');

        if (is_array($types) && in_array('aoestatic', $types)) {
            foreach (Mage::helper('aoestatic')->purgeAll() as $message) {
                Mage::getSingleton('adminhtml/session')->addNotice($message);
            }
        }
    }
}
