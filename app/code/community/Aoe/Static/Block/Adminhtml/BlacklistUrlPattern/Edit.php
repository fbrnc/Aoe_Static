<?php
/**
 * Class Aoe_VarnishAsyncCache_Block_Adminhtml_BlacklistUrlPattern_Edit
 *
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoemedia.de>
 */
class Aoe_Static_Block_Adminhtml_BlacklistUrlPattern_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
     /**
     * Initialize edit form container
     */
    public function __construct()
    {
        $this->_objectId    = 'id';
        $this->_blockGroup = 'aoestatic';
        $this->_controller = 'adminhtml_blacklistUrlPattern';

        parent::__construct();

        if ($this->_isAllowedAction('save')) {
            $this->_updateButton('save', 'label', Mage::helper('aoestatic')->__('Save blacklist url pattern'));
            $this->_addButton('saveandcontinue', array(
                    'label'     => Mage::helper('aoestatic')->__('Save and Continue Edit'),
                    'onclick'   => 'saveAndContinueEdit()',
                    'class'     => 'save',
                ), -100);
        } else {
            $this->_removeButton('save');
        }

        if ($this->_isAllowedAction('delete')) {
            $this->_updateButton('delete', 'label',
                Mage::helper('aoestatic')->__('Delete blacklist url pattern')
            );
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
        if (Mage::registry('blacklist_url_pattern')->getId()) {
            return Mage::helper('aoestatic')->__("Edit blacklist url pattern (ID: %d)",
                $this->escapeHtml(Mage::registry('blacklist_url_pattern')->getId())
            );
        } else {
            return Mage::helper('aoestatic')->__('New blacklist url pattern');
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
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/aoestatic_blacklistUrlPattern/' . $action);
    }
}
