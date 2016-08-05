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
        $this->getResponse()->setBody($response);
    }
}
