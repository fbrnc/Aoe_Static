<?php
/**
 * Class Aoe_Static_Block_Adminhtml_CustomUrl
 *
 * @author Dmytro Zavalkin
 */
class Aoe_Static_Block_Adminhtml_CustomUrl extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Block constructor
     */
    public function __construct()
    {
        $this->_blockGroup = 'aoestatic';
        $this->_controller = 'adminhtml_customUrl';
        $this->_headerText = Mage::helper('aoestatic')->__('Urls max-age management');

        parent::__construct();

        if ($this->_isAllowedAction('save')) {
            $this->_updateButton('add', 'label', Mage::helper('aoestatic')->__('Add new custom url'));
        } else {
            $this->_removeButton('add');
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
