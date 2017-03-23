<?php

/**
 * CallController
 * Renders the blocks that are requested via an ajax call
 *
 * @author Fabrizio Branca
 */
class Aoe_Static_CallController extends Mage_Core_Controller_Front_Action
{
    /**
     * places to look for messages
     *
     * @var array
     */
    protected $_sessions = array(
        'core/session', 'customer/session', 'catalog/session', 'checkout/session'
    );

    /** @var  Aoe_Static_Model_Config */
    protected $_config;

    /**
     * render requested blocks
     *
     * @param array|string $blocks
     * @return array
     */
    protected function _renderBlocks($blocks)
    {
        $result = array();

        if (!is_array($blocks)) {
            $blocks = array($blocks);
        }

        $layout = $this->getLayout();
        foreach ($blocks as $id => $requestedBlockName) {
            $tmpBlock = $layout->getBlock($requestedBlockName);
            if ($tmpBlock) {
                $result[$id] = $tmpBlock->toHtml();
            } else {
                $result[$id] = 'BLOCK NOT FOUND';
            }
        }

        return $result;
    }

    /**
     * Index action. This action is called by an ajax request
     *
     * @return void
     * @author Fabrizio Branca
     */
    public function indexAction()
    {
        // if (!$this->getRequest()->isXmlHttpRequest()) { Mage::throwException('This is not an XmlHttpRequest'); }
        $response = array();
        //$response['sid'] = Mage::getModel('core/session')->getEncryptedSessionId();

        if ($currentProductId = $this->getRequest()->getParam('currentProductId')) {
            /** @var Mage_Reports_Model_Product_Index_Viewed $report */
            $report = Mage::getModel('reports/product_index_viewed');
            $report->setProductId($currentProductId)
                ->save()
                ->calculate();

            /** @var Mage_Catalog_Model_Session $session */
            $session = Mage::getSingleton('catalog/session');
            $session->setLastViewedProductId($currentProductId);

            if ($this->getConfig()->isLoadCurrentProduct()) {
                /** @var Mage_Catalog_Model_Product $product */
                $product = Mage::getModel('catalog/product');
                $product
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($currentProductId);
                if ($product->getId()) {
                    Mage::register('product', $product);
                    Mage::register('current_product', $product);
                }
            }
        }

        $this->loadLayout();

        // get blocks
        $requestedBlockNames = $this->getRequest()->getParam('getBlocks');
        if ($requestedBlockNames) {
            $response['blocks'] = $this->_renderBlocks($requestedBlockNames);
        }

        // get messages
        $messages = array();
        foreach ($this->_sessions as $sessionStorage) {
            if (!isset($messages[$sessionStorage])) {
                $messages[$sessionStorage] = array();
            }
            foreach (Mage::getSingleton($sessionStorage)->getMessages(true)->getItems() as $message) {
                $type = $message->getType();
                if (!isset($messages[$sessionStorage][$type])) {
                    $messages[$sessionStorage][$type] = array();
                }
                $messages[$sessionStorage][$type][] = $message->getCode();
            }
        }
        $response['messages'] = $messages;

        $this->getResponse()->setBody(Zend_Json::encode($response));
    }

    /**
     * The same as Index action, but strips out the session_id and doesn't return messages
     *
     * @return void
     */
    public function secureAction()
    {
        $this->loadLayout();

        $response = array();
        $requestedBlockNames = $this->getRequest()->getParam('getBlocks');
        if ($requestedBlockNames) {
            $response['blocks'] = $this->_renderBlocks($requestedBlockNames);
        }

        // strip SID from responses
        $sid = Mage::getModel('core/session')->getEncryptedSessionId();
        foreach ($response['blocks'] as $id => &$content) {
            $content = str_replace($sid, '__NO_SID__', $content);
        }

        $this->getResponse()->setBody(Zend_Json::encode($response));
    }

    /**
     * @return Aoe_Static_Model_Config
     */
    public function getConfig()
    {
        if (!$this->_config) {
            /** @var Aoe_Static_Model_Config $config */
            $config = Mage::getSingleton('aoestatic/config');

            $this->setConfig($config);
        }

        return $this->_config;
    }

    /**
     * @param Aoe_Static_Model_Config $config Config
     * @return $this
     */
    public function setConfig(Aoe_Static_Model_Config $config)
    {
        $this->_config = $config;

        return $this;
    }
}
