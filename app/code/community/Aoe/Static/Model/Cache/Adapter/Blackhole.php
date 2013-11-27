<?php

/**
 * Class Aoe_Static_Model_Cache_Adapter_Blackhole
 */
class Aoe_Static_Model_Cache_Adapter_Blackhole implements Aoe_Static_Model_Cache_Adapter_Interface
{

    /**
     * Purges all
     *
     * @return array errors if any
     */
    public function purgeAll()
    {
        Mage::log('[Aoe_Static Blackhole] Purge all');
        return array();
    }

    /**
     * Purge
     *
     * @param array $urls
     * @return array with all errors
     */
    public function purge(array $urls)
    {
        Mage::log('[Aoe_Static Blackhole] Purge urls: ' . implode(', ', $urls));
        return array();
    }

    /**
     * Purge by tag
     *
     * @param array $tags
     * @return array
     */
    public function purgeTags(array $tags)
    {
        Mage::log('[Aoe_Static Blackhole] Purge tags: ' . implode(', ', $tags));
        return array();
    }

    /**
     * sets varnish server urls
     *
     * @param string|array $config
     */
    public function setConfig($config)
    {
        // This adapter reads its configuration from core_config_data instead
    }
}
