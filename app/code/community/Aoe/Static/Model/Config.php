<?php

class Aoe_Static_Model_Config extends Mage_Core_Model_Config_Base
{
    /**
     * Key name for storage of cache data
     *
     * @var string
     */
    const CACHE_ID = 'aoe_static_cache';

    /**
     * Tag name for cache type, used in mass cache cleaning
     *
     * @var string
     */
    const CACHE_TAG = 'config';

    /**
     * Filename that will be collected from different modules
     *
     * @var string
     */
    const CONFIGURATION_FILENAME = 'aoe_static.xml';

    /**
     * Initial configuration file template, then merged in one file
     *
     * @var string
     */
    const CONFIGURATION_TEMPLATE = '<?xml version="1.0"?><config></config>';

    /**
     * @var null|array
     */
    protected $_markers = null;

    /**
     * Class constructor
     * load cache configuration
     *
     * @param string $sourceData Source data
     * @SuppressWarnings
     */
    public function __construct($sourceData = null)
    {
        $tags = array(self::CACHE_TAG);
        $useCache = Mage::app()->useCache('config');
        $this->setCacheId(self::CACHE_ID);
        $this->setCacheTags($tags);
        if ($useCache && ($cache = Mage::app()->loadCache(self::CACHE_ID))) {
            parent::__construct($cache);
        } else {
            parent::__construct(self::CONFIGURATION_TEMPLATE);
            Mage::getConfig()->loadModulesConfiguration(self::CONFIGURATION_FILENAME, $this);
            if ($useCache) {
                $xmlString = $this->getXmlString();
                Mage::app()->saveCache($xmlString, self::CACHE_ID, $tags);
            }
        }
    }

    /**
     * Get action configuration
     *
     * @param string $fullActionName Full action name
     * @return false|Mage_Core_Model_Config_Element
     */
    public function getActionConfiguration($fullActionName)
    {
        $configuration = $this->getNode('aoe_static/' . $fullActionName);
        if (!$configuration || (1 == $configuration->disabled)) {

            return false;
        }
        $use = (string) $configuration->use;
        if ($use) {
            $configuration = $this->getActionConfiguration($use);
        }

        return $configuration;
    }

    /**
     * @return Varien_Simplexml_Element
     */
    public function getMarkersCallbackConfiguration()
    {
        if (is_null($this->_markers)) {
            $this->_markers = $this->getNode('aoe_static/default/markers');
        }

        return $this->_markers;
    }

    /**
     * Get callback string like some_module/fooclass::getValue for a given marker name
     * @param string $marker Marker string
     * @return string callback
     */
    public function getMarkerCallback($marker)
    {
        $callback = '';
        $configuration = $this->getMarkersCallbackConfiguration();
        $markerWithoutHash = str_replace('#', '',$marker);
        if (isset($configuration->$markerWithoutHash->valueCallback)) {
            $callback = (string) $configuration->$markerWithoutHash->valueCallback;
        }

        return $callback;
    }

    /**
     * fetch configured adapters
     *
     * @return array
     */
    public function getAdapters()
    {
        $adapters = $this->getNode('aoe_static_purging/adapters');
        if (!$adapters) {

            return array();
        }

        return $adapters->asArray();
    }

    /**
     * @return bool
     */
    public function useAsyncCache()
    {
        return Mage::getStoreConfigFlag('dev/aoestatic/use_aoe_asynccache');
    }

    /**
     * @return bool
     */
    public function isLoadCurrentProduct()
    {
        return Mage::getStoreConfigFlag('dev/aoestatic/load_current_product');
    }
}
