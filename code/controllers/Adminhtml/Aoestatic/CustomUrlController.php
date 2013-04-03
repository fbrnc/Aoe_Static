<?php
/**
 * Class Aoe_Static_Adminhtml_Aoestatic_CustomUrlController
 *
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoemedia.de>
 */
class Aoe_Static_Adminhtml_Aoestatic_CustomUrlController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return $this
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('system/aoestatic_customUrl')
            ->_addBreadcrumb(
                  Mage::helper('aoestatic')->__('System'),
                  Mage::helper('aoestatic')->__('System')
            )
            ->_addBreadcrumb(
                  Mage::helper('aoestatic')->__('Urls max-age management'),
                  Mage::helper('aoestatic')->__('Urls max-age management')
            );
        return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->_title($this->__('System'))
             ->_title($this->__('Urls max-age management'));

        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Create new custom url
     */
    public function newAction()
    {
        // the same form is used to create and edit
        $this->_forward('edit');
    }

    /**
     * Edit custom url
     */
    public function editAction()
    {
        $this->_title($this->__('System'))
             ->_title($this->__('Urls max-age management'));

        /** @var $model Aoe_Static_Model_CustomUrl */
        $model = Mage::getModel('aoestatic/customUrl');

        $customUrlId = $this->getRequest()->getParam('id');
        if ($customUrlId) {
            $model->load($customUrlId);

            if (!$model->getId()) {
                $this->_getSession()->addError(
                    Mage::helper('aoestatic')->__('Custom url does not exist.')
                );
                $this->_redirect('*/*/');
                return;
            }
            // prepare title
            $breadCrumb = Mage::helper('aoestatic')->__('Edit custom url (ID: %d)', $model->getId());
        } else {
            $breadCrumb = Mage::helper('aoestatic')->__('New Item');
        }

        // Init breadcrumbs
        $this->_title($breadCrumb);
        $this->_initAction()->_addBreadcrumb($breadCrumb, $breadCrumb);

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        Mage::register('custom_url', $model);

        $this->renderLayout();
    }

    /**
     * Save action
     */
    public function saveAction()
    {
        $redirectPath   = '*/*';
        $redirectParams = array();

        // check if data sent
        $data = $this->getRequest()->getPost();
        if ($data) {
            // init model and set data
            /** @var $model Aoe_Static_Model_CustomUrl */
            $model = Mage::getModel('aoestatic/customUrl');

            // if custom url exists, try to load it
            $customUrlId = $this->getRequest()->getParam('id');
            if ($customUrlId) {
                $model->load($customUrlId);
            }

            $model->addData($data);

            try {
                $hasError = false;

                // save the data
                $model->save();

                // display success message
                $this->_getSession()->addSuccess(
                    Mage::helper('aoestatic')->__('Custom url has been saved.')
                );

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $redirectPath   = '*/*/edit';
                    $redirectParams = array('id' => $model->getId());
                }
            } catch (Mage_Core_Exception $e) {
                $hasError = true;
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $hasError = true;
                $this->_getSession()->addException($e,
                    Mage::helper('aoestatic')->__('An error occurred while saving custom url.')
                );
            }

            if ($hasError) {
                $this->_getSession()->setFormData($data);
                $redirectPath   = '*/*/edit';
                $redirectParams = array('id' => $this->getRequest()->getParam('id'));
            }
        }

        $this->_redirect($redirectPath, $redirectParams);
    }

    /**
     * Delete action
     */
    public function deleteAction()
    {
        // check if we know what should be deleted
        $customUrlId = $this->getRequest()->getParam('id');
        if ($customUrlId) {
            try {
                // init model and delete
                /** @var $model Aoe_Static_Model_CustomUrl */
                $model = Mage::getModel('aoestatic/customUrl');
                $model->load($customUrlId);
                if (!$model->getId()) {
                    Mage::throwException(Mage::helper('aoestatic')->__('Unable to find a custom url.'));
                }
                $model->delete();

                // display success message
                $this->_getSession()->addSuccess(
                    Mage::helper('aoestatic')->__('Custom url has been deleted.')
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('aoestatic')->__('An error occurred while deleting custom url.')
                );
            }
        }

        // go to grid
        $this->_redirect('*/*/');
    }

    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'new':
            case 'save':
                return Mage::getSingleton('admin/session')->isAllowed('system/aoestatic_customUrl/save');
                break;
            case 'delete':
                return Mage::getSingleton('admin/session')->isAllowed('system/aoestatic_customUrl/delete');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('system/aoestatic_customUrl');
                break;
        }
    }

    /**
     * Grid ajax action
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
