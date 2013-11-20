<?php

interface Aoe_Static_Model_Cache_Adapter_Interface
{
    // called to purge all urls for this cache
    public function purgeAll();
    // called to purge a given array of urls
    public function purge(array $urls);
    // called to purge a given array of tags
    public function purgeTags(array $tags);
    // called to set config
    public function setConfig($config);
}
