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
     * Index action. This action is called by an ajax request
     *
     * @return void
     * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
     */
    public function indexAction()
    {
        // if (!$this->getRequest()->isXmlHttpRequest()) { Mage::throwException('This is not an XmlHttpRequest'); }
        $response = array();
        $response['sid'] = Mage::getModel('core/session')->getEncryptedSessionId();

        if ($currentProductId = $this->getRequest()->getParam('currentProductId')) {
            Mage::getSingleton('catalog/session')->setLastViewedProductId($currentProductId);
        }

        $this->loadLayout();
        $layout = $this->getLayout();

        $requestedBlockNames = $this->getRequest()->getParam('getBlocks');
        if (is_array($requestedBlockNames)) {
            foreach ($requestedBlockNames as $id => $requestedBlockName) {
                $tmpBlock = $layout->getBlock($requestedBlockName);
                if ($tmpBlock) {
                    $response['blocks'][$id] = $tmpBlock->toHtml();
                } else {
                    $response['blocks'][$id] = 'BLOCK NOT FOUND';
                }
            }
        }

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
     * The same as Index action, but strips out the session_id
     */
    public function secureAction()
    {
        // call original action
        $this->indexAction();

        // strip insecure data
        $response = Zend_Json::decode($this->getResponse()->getBody());
        $sid = $response['sid'];
        unset($response['sid']);
        foreach ($response['blocks'] as $id => &$content) {
            $content = str_replace($sid, '__NO_SID__', $content);
        }
        $this->getResponse()->setBody(Zend_Json::encode($response));
    }
}
