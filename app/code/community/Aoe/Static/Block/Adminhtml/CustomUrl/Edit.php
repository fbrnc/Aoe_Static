<?php
/**
 * Class Aoe_Static_Block_Adminhtml_CustomUrl_Edit
 *
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoemedia.de>
 */
class Aoe_Static_Block_Adminhtml_CustomUrl_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
     /**
     * Initialize edit form container
     */
    public function __construct()
    {
        $this->_objectId    = 'id';
        $this->_blockGroup = 'aoestatic';
        $this->_controller = 'adminhtml_customUrl';

        parent::__construct();

        if ($this->_isAllowedAction('save')) {
            $this->_updateButton('save', 'label', Mage::helper('aoestatic')->__('Save custom url'));
            $this->_addButton('saveandcontinue', array(
                    'label'     => Mage::helper('aoestatic')->__('Save and Continue Edit'),
                    'onclick'   => 'saveAndContinueEdit()',
                    'class'     => 'save',
                ), -100);
        } else {
            $this->_removeButton('save');
        }

        if ($this->_isAllowedAction('delete')) {
            $this->_updateButton('delete', 'label', Mage::helper('aoestatic')->__('Delete custom url'));
        } else {
            $this->_removeButton('delete');
        }

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('custom_url')->getId()) {
            return Mage::helper('aoestatic')->__("Edit custom url (ID: %d)",
                $this->escapeHtml(Mage::registry('custom_url')->getId())
            );
        } else {
            return Mage::helper('aoestatic')->__('New custom url');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/aoestatic_customUrl/' . $action);
    }
}
