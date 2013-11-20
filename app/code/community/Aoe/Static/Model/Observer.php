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
     * Indicates, if there are messages to show on the current page
     * @var bool
     */
    protected $messagesToShow = false;

    /**
     * Temporary storage for already processed entries
     *
     * @var array
     */
    public $_tags_already_processed = array();

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
        foreach ($conf->headers->children() as $key => $value) {
            // skip aoestatic header if we have messages to display
            if ($this->messagesToShow && ($key == 'aoestatic')) {
                continue;
            }
            $value = $cacheMarker->replaceMarkers($value);
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
                $value    = $cacheMarker->replaceMarkers($value);
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
    public function coreBlockAbstractToHtmlBefore(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();

        if ($block instanceof Mage_Cms_Block_Block && $block->getBlock()) {
            Mage::getSingleton('aoestatic/cache_control')->addTag('block-' . $block->getBlock()->getId());
        } else if ($block instanceof Mage_Cms_Block_Page) {
            Mage::getSingleton('aoestatic/cache_control')->addTag('page-' . ($block->getPageId() ?: ($block->getPage() ? $block->getPage()->getId() : Mage::getSingleton('cms/page')->getId())));
        } else if (($block instanceof Mage_Catalog_Block_Product_Abstract) && $block->getProductCollection()) {
            $tags = array();
            foreach ($block->getProductCollection()->getAllIds() as $id) {
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

            // purge directly without queueing
            Mage::helper('aoestatic')->purge($purgeUrls, false);

            Mage::getSingleton('adminhtml/session')->addSuccess("The Aoe_Static cache storage has been flushed.");

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
     * @todo tzags isntead of urls
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

        $urls = array();
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
            if (in_array($tag, $this->_tags_already_processed)) {
                continue;
            }

            $this->_tags_already_processed[] = $tag;

            //catalog_product_100 or catalog_category_186
            $tag_fields = explode('_', $tag);
            if (count($tag_fields) == 3) {
                switch ($tag_fields[1]) {
                    case 'product':
                        // get urls for product
                        $product = Mage::getModel('catalog/product')->load($tag_fields[2]);
                        $urls = array_merge($urls, Mage::helper('aoestatic/url')->_getUrlsForProduct($product));
                        break;

                    case 'category':
                        $category = Mage::getModel('catalog/category')->load($tag_fields[2]);
                        $category_urls = Mage::helper('aoestatic/url')->_getUrlsForCategory($category);
                        $urls = array_merge($urls, $category_urls);
                        break;

                    case 'page':
                        $urls = Mage::helper('aoestatic/url')->_getUrlsForCmsPage($tag_fields[2]);
                        break;
                }
            }
        }
        // transform urls to relative urls
        $relativeUrls = array();
        foreach ($urls as $url) {
            $relativeUrls[] = parse_url($url, PHP_URL_PATH);
        }
        if (!empty($relativeUrls)) {
            $errors = Mage::helper('aoestatic')->purge($relativeUrls);
            if (!empty($errors)) {
                $session->addError($helper->__("Some Static purges failed: <br/>") . implode("<br/>", $errors));
            } else {
                $count = count($relativeUrls);
                if ($count > 5) {
                    $relativeUrls = array_slice($relativeUrls, 0, 5);
                    $relativeUrls[] = '...';
                    $relativeUrls[] = $helper->__("(Total number of purged urls: %d)", $count);
                }
                $session->addSuccess(
                    $helper->__("Purges have been submitted successfully:<br/>") . implode("<br />", $relativeUrls)
                );
            }
        }
        return $this;
    }
}
