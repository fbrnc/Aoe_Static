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
    const TAG_DELIMITER = ' ';

    /** @var string */
    const PART_DELIMITER = '-';

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
            if ($timestamp > 0 && (!$this->_maxAge || ($timestamp < $this->_maxAge))) {
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
     * adds tag(s) to current cache
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
            $tag = $this->normalizeTag($tag, true);
            if (!isset($this->_tags[$tag])) {
                $this->_tags[$tag] = 0;
            }
            $this->_tags[$tag]++;
        }

        return $this;
    }

    /**
     * Parse the requested tag and return a clean version
     *
     * @param string $tag
     * @param bool   $withStore
     *
     * @return string
     */
    public function normalizeTag($tag, $withStore = false)
    {
        if(is_array($tag)) {
            $tag = implode(self::PART_DELIMITER, $tag);
        }

        $tag = str_replace(array("\r\n", "\r", "\n", self::TAG_DELIMITER), '_', strtoupper(trim($tag)));

        if ($withStore) {
            $tag .= self::PART_DELIMITER . Mage::app()->getStore()->getId();
        }

        return $tag;
    }

    /**
     * applies cache-headers if enabled is true (default)
     *
     * @return $this
     */
    public function applyCacheHeaders()
    {
        if ($this->_enabled && $this->_maxAge) {
            $maxAge = (int) $this->_maxAge;
            $response = Mage::app()->getResponse();
            $response->setHeader('Cache-Control', 'max-age=' . $maxAge, true);
            $response->setHeader('Expires', gmdate("D, d M Y H:i:s", time() + $maxAge) . ' GMT', true);
            $response->setHeader('X-Tags', implode(self::TAG_DELIMITER, array_keys($this->_tags)));
            $response->setHeader('X-Aoestatic', 'cache', true);
            $response->setHeader('X-Aoestatic-Lifetime', (int) $maxAge, true);
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
        $tags = array();
        $product = Mage::registry('product');
        if ($product) {
            $tags[] = 'product-' . $product->getId();
            //add child products tags
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                $childProductsIds = $product->getTypeInstance()->getUsedProductIds();
                foreach($childProductsIds as $id) {
                    $tags[] = 'product-' . $id;
                }
            }
        }
        if (($layer = $this->_getLayer()) && ($layer->getCurrentCategory()->getId() != $layer->getCurrentStore()->getRootCategoryId()) && ($layer->apply()->getProductCollection())) {
            /** @var Mage_Catalog_Model_Layer $layer */
            $ids = $layer->getProductCollection()->getLoadedIds();
            foreach ($ids as $id) {
                $tags[] = 'product-' . $id;
            }
        }
        if (Mage::registry('current_category')) {
            /** @var Mage_Catalog_Model_Category $currentCategory */
            $currentCategory = Mage::registry('current_category');
            $tags[] = 'category-' . $currentCategory->getId();
        }
        $this->addTag($tags);
        return $this;
    }
}
