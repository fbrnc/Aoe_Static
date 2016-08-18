<?php

/**
 * EsiController
 * Renders an individual block requested via ESI
 *
 * @author Fabrizio Branca
 */
class Aoe_Static_EsiController extends Mage_Core_Controller_Front_Action
{

    /**
     * Index action. This action is called via ESI
     */
    public function indexAction()
    {
        $this->loadLayout();
        $blockName = $this->getRequest()->getParam('block');
        $block = $this->getLayout()->getBlock($blockName); /* @var $block Mage_Core_Block_Abstract */
        $response = $block ? $block->toHtml() : 'BLOCK NOT FOUND';

        if ($block && $block->getMaxAge()) {
            /** @var Aoe_Static_Model_Cache_Control $cacheControl */
            $cacheControl = Mage::getSingleton('aoestatic/cache_control');
            $cacheControl->addMaxAge($block->getMaxAge());
            $cacheControl->addTag($block->getBanTags());
            $cacheControl->applyCacheHeaders();
        }

        $this->getResponse()->setBody($response);
    }
}
