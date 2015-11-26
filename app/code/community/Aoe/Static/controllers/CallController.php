<?php

/**
 * CallController
 * Renders the block that are requested via an ajax call
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Static_CallController extends Mage_Core_Controller_Front_Action
{
    /**
     * places to look for messages
     *
     * @var array
     */
    protected $_sessions = array('core/session', 'customer/session', 'catalog/session', 'checkout/session');

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
     * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
     */
    public function indexAction()
    {
        // if (!$this->getRequest()->isXmlHttpRequest()) { Mage::throwException('This is not an XmlHttpRequest'); }
        $response = array();
        //$response['sid'] = Mage::getModel('core/session')->getEncryptedSessionId();

        if ($currentProductId = $this->getRequest()->getParam('currentProductId')) {
            Mage::getModel('reports/product_index_viewed')
                ->setProductId($currentProductId)
                ->save()
                ->calculate();
            Mage::getSingleton('catalog/session')->setLastViewedProductId($currentProductId);
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
}
