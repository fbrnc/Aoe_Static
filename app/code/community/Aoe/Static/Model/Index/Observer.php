<?php
/**
 * Class Aoe_Static_Model_Index_Observer
 *
 * Observer handling changelog indexer events
 *
 * @category Helper
 * @package  Aoe_Static
 * @author   AOE Magento Team <team-magento@aoe.com>
 * @license  none none
 * @link     www.aoe.com
 */

class Aoe_Static_Model_Index_Observer
{

    /**
     * Invalidate the cache on receiving full reindex events
     *
     * @param Varien_Event_Observer $observer The observer
     * @return Aoe_Static_Model_Index_Observer
     */
    public function invalidateCacheAfterFullReindex($observer)
    {
        Mage::app()->getCacheInstance()->invalidateType(array('aoestatic'));
    }

    /**
     * Clean product cache after partial reindex event
     *
     * @param Varien_Event_Observer $observer The observer
     * @return Aoe_Static_Model_Index_Observer
     */
    public function cleanProductsCacheAfterPartialReindex($observer)
    {
        if (!$this->_useAoeStaticCache()) {
            return $this;
        }

        $entityIds = $observer->getEvent()->getProductIds();
        if (!is_array($entityIds) || empty($entityIds)) {
            return $this;
        }

        $skippableProductIds = Mage::registry(Aoe_Static_Model_Observer::REGISTRY_SKIPPABLE_NAME);
        if (null == $skippableProductIds) {
            $skippableProductIds = [];
        }

        $tags = [];
        foreach ($entityIds as $_entityId) {
            if (in_array($_entityId, $skippableProductIds)) {
                continue;
            }

            $tags[] = $this->_getCacheControl()->normalizeTag(['product', $_entityId]);
        }

        if (!empty($tags)) {
            $helper = $this->_getDataHelper();
            $helper->purgeTags($tags);
        }

        return $this;
    }

    /**
     * Clean categories cache after partial reindex event
     *
     * @param Varien_Event_Observer $observer The observer
     * @return Aoe_Static_Model_Index_Observer
     */
    public function cleanCategoriesCacheAfterPartialReindex($observer)
    {
        if (!$this->_useAoeStaticCache()) {
            return $this;
        }

        $entityIds = $observer->getEvent()->getCategoryIds();
        if (!is_array($entityIds) || empty($entityIds)) {
            return $this;
        }

        $tags = [];
        foreach ($entityIds as $_entityId) {
            $tags[] = $this->_getCacheControl()->normalizeTag(['category', $_entityId]);
        }

        $helper = $this->_getDataHelper();
        $helper->purgeTags($tags);

        return $this;
    }

    /**
     * Clean cache by tags created on partial reindexes
     *
     * @param Varien_Event_Observer $observer The observer
     * @return Aoe_Static_Model_Index_Observer
     */
    public function cleanCacheByTags($observer)
    {
        if (!$this->_useAoeStaticCache()) {
            return $this;
        }

        $tags = $observer->getEvent()->getTags();
        if (empty($tags)) {
            return $this;
        }

        $purgeTags = [];
        foreach ($tags as $_tag) {
            $tagFields = explode('_', $_tag);
            if (3 == count($tagFields)) {
                if (in_array($tagFields[1], array('product', 'category'))) {
                    $purgeTags[] = $this->_getCacheControl()->normalizeTag(array($tagFields[1], $tagFields[2]));
                }
            }
        }

        if (empty($purgeTags)) {
            return $this;
        }

        $this->_getDataHelper()->purgeTags($purgeTags);

        return $this;
    }

    /**
     * Determine if aoestatic is enabled as cache
     *
     * @return boolean
     */
    protected function _useAoeStaticCache()
    {
        return Mage::app()->useCache('aoestatic');
    }

    /**
     * Get the modules data helper
     *
     * @return Aoe_Static_Helper_Data
     */
    protected function _getDataHelper()
    {
        return Mage::helper('aoestatic');
    }

    /**
     * Get cache control singleton
     *
     * @return Aoe_Static_Model_Cache_Control
     */
    protected function _getCacheControl()
    {
        return Mage::getSingleton('aoestatic/cache_control');
    }

}
