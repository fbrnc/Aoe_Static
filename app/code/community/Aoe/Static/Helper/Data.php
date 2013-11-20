<?php

/**
 * Data helper
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 */
class Aoe_Static_Helper_Data extends Mage_Core_Helper_Abstract
{
    /** @var null|Aoe_Static_Model_Config */
    protected $_config = null;

    /** @var array */
    protected $_adapterInstances = array();

    /** @var array|null */
    protected $_blacklist = null;

    /**
     * @return Aoe_Static_Model_Config
     */
    public function getConfig()
    {
        if (is_null($this->_config)) {
            $this->_config = Mage::getModel('aoestatic/config');
        }
        return $this->_config;
    }

    /**
     * instantiates and caches active adapters
     *
     * @return array
     */
    protected function _getAdapterInstances()
    {
        foreach ($this->getConfig()->getAdapters() as $key => $adapter) {
            if (!isset($this->_adapterInstances[$key])) {
                $adapterInstance = Mage::getSingleton($adapter['model']);
                $adapterInstance->setConfig($adapter['config']);
                $this->_adapterInstances[$key] = $adapterInstance;
            }
        }

        return $this->_adapterInstances;
    }

    /**
     * calls purgeAll on all adapter instances
     *
     * @return array
     */
    public function purgeAll()
    {
        $result = array();
        foreach ($this->_getAdapterInstances() as $adapter) {
            /** @var Aoe_Static_Model_Adapter_Interface $adapter */
            $result = array_merge($result, $adapter->purgeAll());
        }
        return $result;
    }

    /**
     * Get array of blacklist url patterns
     *
     * @return array
     */
    public function  getBlacklist()
    {
        if (is_null($this->_blacklist)) {
            /** @var Aoe_Static_Model_Resource_BlacklistUrlPattern_Collection $collection */
            $collection = Mage::getResourceModel('aoestatic/blacklistUrlPattern_collection');
            $this->_blacklist = $collection->getColumnValues('pattern');
        }
        return $this->_blacklist;
    }

    /**
     * calls purge on every adapter with given URLs
     *
     * @todo names/consts
     * @param array $urls
     * @param bool $queue
     * @return array
     */
    public function purge(array $urls, $queue = true)
    {
        $urls = array_filter($urls, function ($e) { return strlen($e) ? true : false; });
        $result = array();
        foreach ($this->_getAdapterInstances() as $adapter) {
            /** @var Aoe_Static_Model_Adapter_Interface $adapter */

            // queue if async cache is enabled in config and not forced to purge directly
            if ($this->getConfig()->useAsyncCache() && $queue) {
                foreach ($urls as $url) {
                    /** @var $asyncCache Aoe_AsyncCache_Model_Asynccache */
                    $asyncCache = Mage::getModel('aoeasynccache/asynccache');
                    $asyncCache->setTstamp(time())
                        ->setMode(Aoe_Static_Helper_Data::MODE_PURGEVARNISHURL)
                        ->setTags($url)
                        ->setStatus(Aoe_AsyncCache_Model_Asynccache::STATUS_PENDING)
                        ->save();
                }
            } else {
                $result = array_merge($result, $adapter->purge($urls));
            }
        }
        return $result;
    }

    public function purgeTags($tags)
    {
        $result = array();
        foreach ($tags as $tag) {
            foreach ($this->_getAdapterInstances() as $adapter) {
                /** @var Aoe_Static_Model_Cache_Adapter_Interface $adapter */
                $result = array_merge($result, $adapter->purgeTags($tag));
            }
        }
        return $result;
    }
}
