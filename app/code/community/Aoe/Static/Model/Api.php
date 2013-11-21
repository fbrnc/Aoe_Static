<?php

class Aoe_Static_Model_Api extends Mage_Api_Model_Resource_Abstract
{
    /**
     * Purge url
     *
     * @param array $urls
     * @return array errors
     */
    public function purge($urls) {
        /** @var $helper Aoe_Static_Helper_Data */
        $helper = Mage::helper('aoestatic');
        $errors = $helper->purge($urls);
        return $errors;
    }

    /**
     * Purge tags
     *
     * @param array $tags
     * @return array errors
     */
    public function purgeTags($tags) {
        /** @var $helper Aoe_Static_Helper_Data */
        $helper = Mage::helper('aoestatic');
        $errors = $helper->purgeTags($tags);
        return $errors;
    }

    /**
     * Purge all
     *
     * @return array errors
     */
    public function purgeAll() {
        /** @var $helper Aoe_Static_Helper_Data */
        $helper = Mage::helper('aoestatic');
        $errors = $helper->purgeAll();
        return $errors;
    }
}
