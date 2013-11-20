<?php
/**
 * Class Aoe_VarnishAsyncCache_Block_Adminhtml_BlacklistUrlPattern
 *
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoemedia.de>
 */
class Aoe_Static_Block_Adminhtml_BlacklistUrlPattern extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Block constructor
     */
    public function __construct()
    {
        $this->_blockGroup = 'aoestatic';
        $this->_controller = 'adminhtml_blacklistUrlPattern';
        $this->_headerText = Mage::helper('aoestatic')->__('AOE Static Cache Url Blacklist');

        parent::__construct();

        if ($this->_isAllowedAction('save')) {
            $this->_updateButton('add', 'label',
                Mage::helper('aoestatic')->__('Add new blacklist url pattern')
            );
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
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/aoestatic_blacklistUrlPattern/' . $action);
    }
}
