<?php

class Aoe_Static_Model_Cache_Control
{
    protected $_tags = array();
    protected $_maxAge = 0;
    protected $_enabled = true;

    public function enable()
    {
        $this->_enabled = true;
        return $this;
    }

    public function disable()
    {
        $this->_enabled = false;
        return $this;
    }

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
    }

    public function applyCacheHeaders()
    {
        if ($this->_enabled) {
            $response = Mage::app()->getResponse();
            $response->setHeader('X-Invalidated-By', '|' . implode('|', $this->_tags) . '|');
            $response->setHeader('Cache-Control', 'max-age=' . (int) $this->_maxAge, true);
            $response->setHeader('X-Magento-Lifetime', (int) $this->_maxAge, true);
            $response->setHeader('aoestatic', 'cache', true);
        }
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
    }
}
