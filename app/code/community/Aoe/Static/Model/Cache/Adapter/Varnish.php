<?php

/**
 * Class Aoe_Static_Model_Cache_Adapter_Varnish
 */
class Aoe_Static_Model_Cache_Adapter_Varnish implements Aoe_Static_Model_Cache_Adapter_Interface
{
    /** @var array  */
    protected $_varnishServers = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_varnishServers = Mage::helper('aoe_static')->trimExplode("\n", Mage::getStoreConfig('dev/aoestatic/servers'), true);
    }

    /**
     * Purges all cache on all Varnish servers.
     *
     * @return array errors if any
     */
    public function purgeAll()
    {
        return $this->purge(array('.*'));
    }

    /**
     * Purge an array of urls on all varnish servers.
     *
     * @param array $urls
     * @return array with all errors
     */
    public function purge(array $urls)
    {
        $errors = array();

        // Init curl handler
        $curlHandlers = array(); // keep references for clean up
        $multiHandler = curl_multi_init();

        foreach ($this->_varnishServers as $varnishServer) {
            foreach ($urls as $url) {
                $varnishUrl = "http://" . $varnishServer . '/' . $url;

                $curlHandler = curl_init();
                curl_setopt($curlHandler, CURLOPT_URL, $varnishUrl);
                curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, 'PURGE');
                curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, 0);

                curl_multi_add_handle($multiHandler, $curlHandler);
                $curlHandlers[] = $curlHandler;
            }
        }

        do {
            curl_multi_exec($multiHandler, $active);
        } while ($active);

        // Error handling and clean up
        foreach ($curlHandlers as $curlHandler) {
            $info = curl_getinfo($curlHandler);

            if (curl_errno($curlHandler)) {
                $errors[] = "Cannot purge url {$info['url']} due to error" . curl_error($curlHandler);
            } else if ($info['http_code'] != 200 && $info['http_code'] != 404) {
                $errors[] = "Cannot purge url {$info['url']}, http code: {$info['http_code']}. curl error: " . curl_error($curlHandler);
            }

            curl_multi_remove_handle($multiHandler, $curlHandler);
            curl_close($curlHandler);
        }
        curl_multi_close($multiHandler);

        return $errors;
    }

    public function purgeTags(array $tags)
    {
        $errors = array();
        // Init curl handler
        $curlHandlers = array(); // keep references for clean up
        $multiHandler = curl_multi_init();

        foreach ($this->_varnishServers as $varnishServer) {
            foreach ($tags as $tag) {
                $varnishUrl = "http://" . $varnishServer;

                $curlHandler = curl_init();
                curl_setopt($curlHandler, CURLOPT_URL, $varnishUrl);
                curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, 'BAN');
                curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curlHandler, CURLOPT_HTTPHEADER, array('X-Invalidates: ' . Aoe_Static_Model_Cache_Control::DELIMITER . $tag . Aoe_Static_Model_Cache_Control::DELIMITER));

                curl_multi_add_handle($multiHandler, $curlHandler);
                $curlHandlers[] = $curlHandler;
            }
        }

        do {
            curl_multi_exec($multiHandler, $active);
        } while ($active);

        // Error handling and clean up
        foreach ($curlHandlers as $curlHandler) {
            $info = curl_getinfo($curlHandler);

            if (curl_errno($curlHandler)) {
                $errors[] = "Cannot purge tag {$info['url']} due to error" . curl_error($curlHandler);
            } else if ($info['http_code'] != 200 && $info['http_code'] != 404) {
                $errors[] = "Cannot purge tag {$info['url']}, http code: {$info['http_code']}. curl error: " . curl_error($curlHandler);
            }

            curl_multi_remove_handle($multiHandler, $curlHandler);
            curl_close($curlHandler);
        }
        curl_multi_close($multiHandler);

        return $errors;
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
