<?php

class Aoe_Static_Model_Cache_Control
{
    /** @var array Tags for tag-based purging */
    protected $_tags = array();

    /** @var int minimum maxage */
    protected $_maxAge = 0;

    /** @var bool switch to disable sending out of cache headers */
    protected $_enabled = true;

    /** @var string */
    const DELIMITER = '/';

    /**
     * @return $this
     */
    public function enable()
    {
        $this->_enabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disable()
    {
        $this->_enabled = false;
        return $this;
    }

    /**
     * computes minimum max-age
     *
     * @param int|array $maxAge
     */
    public function addMaxAge($maxAge)
    {
        if (!is_array($maxAge)) {
            $maxAge = array($maxAge);
        }

        foreach ($maxAge as $timestamp) {
            if (!$this->_maxAge || ($timestamp < $this->_maxAge)) {
                $this->_maxAge = $timestamp;
            }
        }
    }

    /**
     * loads specific max-age from database
     *
     * @param $request Mage_Core_Controller_Request_Http
     */
    public function addCustomUrlMaxAge($request)
    {
        // apply custom max-age from db
        $urls = array($request->getRequestString());
        $alias = $request->getAlias(Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS);
        if ($alias) {
            $urls[] = $alias;
        }
        /** @var $customUrlModel Aoe_Static_Model_CustomUrl */
        $customUrlModel = Mage::getModel('aoestatic/customUrl');
        $customUrlModel->setStoreId(Mage::app()->getStore()->getId());
        $customUrlModel->loadByRequestPath($urls);

        if ($customUrlModel->getId() && $customUrlModel->getMaxAge()) {
            $this->addMaxAge($customUrlModel->getMaxAge());
        }
    }

    /**
     * adds tag(s) to currect cache
     *
     * @param $tags array|string
     * @return $this
     */
    public function addTag($tags)
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }

        foreach ($tags as $tag) {
            if (!isset($this->_tags[$tag])) {
                $this->_tags[$tag] = 0;
            }
            $this->_tags[$tag]++;
        }

        return $this;
    }

    /**
     * applies cache-headers if enabled is true (default)
     *
     * @return $this
     */
    public function applyCacheHeaders()
    {
        if ($this->_enabled) {
            $response = Mage::app()->getResponse();
            $response->setHeader('X-Invalidated-By', self::DELIMITER . implode(self::DELIMITER, array_keys($this->_tags)) . self::DELIMITER);
            $response->setHeader('Cache-Control', 'max-age=' . (int) $this->_maxAge, true);
            $response->setHeader('X-Magento-Lifetime', (int) $this->_maxAge, true);
            $response->setHeader('aoestatic', 'cache', true);
        }

        return $this;
    }

    /**
     * get current category layer
     *
     * @return Mage_Catalog_Model_Layer
     */
    protected function _getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }

    /**
     * collect various possible tags from current products and category/layer pages
     *
     * @return $this
     */
    public function collectTags()
    {
        if (Mage::registry('product')) {
            $this->addTag('product-' . Mage::registry('product')->getId());
        }
        if (($layer = $this->_getLayer()) && ($layer->getProductCollection())) {
            /** @var Mage_Catalog_Model_Layer $layer */
            $ids = $layer->getProductCollection()->getAllIds();
            $tags = array();
            foreach ($ids as $id) {
                $tags[] = 'product-' . $id;
            }
            $this->addTag($tags);
        }
        if (Mage::registry('current_category')) {
            /** @var Mage_Catalog_Model_Category $currentCategory */
            $currentCategory = Mage::registry('current_category');
            $this->addTag('category-' . $currentCategory->getId());
        }
        return $this;
    }
}
