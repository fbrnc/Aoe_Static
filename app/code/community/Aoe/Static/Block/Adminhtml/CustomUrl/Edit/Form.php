<?php
/**
 * Class Aoe_Static_Block_Adminhtml_CustomUrl_Edit_Form
 *
 * @author Dmytro Zavalkin
 */
class Aoe_Static_Block_Adminhtml_CustomUrl_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Set form id and title
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('custom_url_form');
        $this->setTitle(Mage::helper('aoestatic')->__('Custom urls'));
    }

    /**
     * Prepare form layout
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var $model Aoe_Static_Model_CustomUrl */
        $model = Mage::registry('custom_url');

        $form = new Varien_Data_Form(
            array(
                'id'     => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post'
            )
        );

        // set form data either from model values or from session
        $formValues = array(
            'store_id'     => $model->getStoreId(),
            'request_path' => $model->getRequestPath(),
            'max_age'      => $model->getMaxAge(),
        );

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => Mage::helper('aoestatic')->__('Custom urls')
        ));

        // get store switcher or a hidden field with its id
        if (!Mage::app()->isSingleStoreMode()) {
            $stores  = Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm();
            $element = $fieldset->addField('store_id', 'select', array(
                'label'     => Mage::helper('aoestatic')->__('Store'),
                'title'     => Mage::helper('aoestatic')->__('Store'),
                'class'     => 'required-entry',
                'name'      => 'store_id',
                'required'  => true,
                'values'    => $stores,
                'value'     => $formValues['store_id'],
            ));
            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
            $element->setRenderer($renderer);
        } else {
            $fieldset->addField('store_id', 'hidden', array(
                'name'      => 'store_id',
                'value'     => Mage::app()->getStore(true)->getId()
            ));
        }

        $fieldset->addField('request_path', 'text', array(
            'label'     => Mage::helper('aoestatic')->__('Request Path'),
            'title'     => Mage::helper('aoestatic')->__('Request Path'),
            'class'     => 'required-entry',
            'name'      => 'request_path',
            'required'  => true,
            'value'     => $formValues['request_path']
        ));

        $fieldset->addField('max_age', 'text', array(
            'label'     => Mage::helper('aoestatic')->__('Max Age'),
            'title'     => Mage::helper('aoestatic')->__('Max Age'),
            'class'     => 'required-entry validate-digits',
            'name'      => 'max_age',
            'required'  => true,
            'value'     => $formValues['max_age']
        ));

        $form->setUseContainer(true);
        $form->setAction(Mage::helper('adminhtml')->getUrl('*/*/save', array('id' => $model->getId())));
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
