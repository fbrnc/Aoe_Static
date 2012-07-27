<?php

class Aoe_Static_Model_Config extends Varien_Simplexml_Config {

    /**
     * Class constructor
     * load cache configuration
     *
     * @param $data
     */
    public function __construct($data = null) {
        parent::__construct($data);
        $this->setCacheId('cache_config');
        $this->_cacheChecksum   = null;
        $this->_cache = Mage::app()->getCache();

        $canUsaCache = Mage::app()->useCache('config');
        if ($canUsaCache) {
            if ($this->loadCache()) {
                return $this;
            }
        }

        $config = Mage::getConfig()->loadModulesConfiguration('aoe_static.xml');
        $this->setXml($config->getNode());

        if ($canUsaCache) {
            $this->saveCache(array(Mage_Core_Model_Config::CACHE_TAG));
        }
        return $this;
    }

	/**
	 * Get action configuration
	 *
	 * @param $fullActionName
	 * @return false|Mage_Core_Model_Config_Element
	 */
	public function getActionConfiguration($fullActionName) {
		$configuration = $this->getNode('aoe_static/'.$fullActionName);
		if (!$configuration || 1 == $configuration->disabled) {
			return false;
		}
		$use = (string)$configuration->use;
		if ($use) {
			$configuration = $this->getActionConfiguration($use);
		}
		return $configuration;
	}

}
