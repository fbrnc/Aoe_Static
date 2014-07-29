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
        $serverConfig = Mage::getStoreConfig('dev/aoestatic/servers');
        if (strpos($serverConfig, ',') !== false) {
            // old format - comma-separated list of servers
            $this->_varnishServers = Mage::helper('aoestatic')->trimExplode(",", $serverConfig, true);
        } else {
            // new format - newline-separated list of servers
            $this->_varnishServers = Mage::helper('aoestatic')->trimExplode("\n", $serverConfig, true);
        }
    }

    /**
     * Purges all cache on all Varnish servers.
     *
     * @return array errors if any
     */
    public function purgeAll()
    {
        return $this->purge(array('R:.*'));
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

        $regexPatterns = array();
        foreach($urls as $k => $url) {
            if(strpos($url, 'R:') === 0) {
                unset($urls[$k]);
                $regexPatterns[] = substr($url, 2);
            }
        }
        $regexPatterns = (empty($regexPatterns) ? '' : '((' . implode(')|(', $regexPatterns) . '))');

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

            if(!empty($regexPatterns)) {
                $curlHandler = curl_init();
                curl_setopt($curlHandler, CURLOPT_URL, "http://" . $varnishServer);
                curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, 'BAN');
                curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curlHandler, CURLOPT_HTTPHEADER, array('X-Url: ' . $regexPatterns));

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

        // Tag delimiter
        $td = str_replace(' ', '\x20', preg_quote(Aoe_Static_Model_Cache_Control::TAG_DELIMITER));

        // Part delimiter
        $pd = str_replace(' ', '\x20', preg_quote(Aoe_Static_Model_Cache_Control::PART_DELIMITER));

        foreach ($tags as $k => $tag) {
            if (strpos($tag, 'R:') === 0) {
                $tag = substr($tag, 2);
            } else {
                $tag = preg_quote($tag) . "({$pd}[^{$td}]+)?";
            }
            $tags[$k] = $tag;
        }

        $regex = "(?U)(^|{$td})((" . implode(')|(', $tags) . "))($|{$td})";

        foreach ($this->_varnishServers as $varnishServer) {
            $varnishUrl = "http://" . $varnishServer;

            $curlHandler = curl_init();
            curl_setopt($curlHandler, CURLOPT_URL, $varnishUrl);
            curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, 'BAN');
            curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curlHandler, CURLOPT_HTTPHEADER, array('X-Tags: ' . $regex));

            curl_multi_add_handle($multiHandler, $curlHandler);
            $curlHandlers[] = $curlHandler;
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
