<?php

class Aoe_Static_Model_Config extends Mage_Core_Model_Config_Base {

    /**
     * Key name for storage of cache data
     *
     * @var string
     */
    const CACHE_KEY_NAME = 'cache_config';
    /**
     * Tag name for cache type, used in mass cache cleaning
     *
     * @var string
     */
    const CACHE_TAG_NAME = 'config';
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
     * Class constructor
     * load cache configuration
     *
     * @param string $sourceData
     */
    public function __construct($sourceData = null)
    {
        $tags = array(self::CACHE_TAG_NAME);
        $useCache = Mage::app()->useCache(self::CACHE_TAG_NAME);
        $this->setCacheId(self::CACHE_KEY_NAME);
        $this->setCacheTags($tags);
        if ($useCache && ($cache = Mage::app()->loadCache(self::CACHE_KEY_NAME))) {
            parent::__construct($cache);
        } else {
            parent::__construct(self::CONFIGURATION_TEMPLATE);
            Mage::getConfig()->loadModulesConfiguration(self::CONFIGURATION_FILENAME, $this);
            if ($useCache) {
                $xmlString = $this->getXmlString();
                Mage::app()->saveCache($xmlString, self::CACHE_KEY_NAME, $tags);
            }
        }
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
