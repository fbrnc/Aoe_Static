<?php
/**
 * Class Aoe_VarnishAsyncCache_Block_Adminhtml_BlacklistUrlPattern_Edit_Form
 *
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoemedia.de>
 */
class Aoe_Static_Block_Adminhtml_BlacklistUrlPattern_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Set form id and title
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('blacklist_url_pattern_form');
        $this->setTitle(Mage::helper('aoestatic')->__('Blacklist url pattern'));
    }

    /**
     * Prepare form layout
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var $model Aoe_Static_Model_BlacklistUrlPattern */
        $model = Mage::registry('blacklist_url_pattern');

        $form = new Varien_Data_Form(
            array(
                'id'     => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post'
            )
        );

        // set form data either from model values or from session
        $formValues = array(
            'pattern' => $model->getPattern(),
        );

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => Mage::helper('aoestatic')->__('Blacklist url pattern')
        ));

        $fieldset->addField('pattern', 'text', array(
            'label'     => Mage::helper('aoestatic')->__('Pattern'),
            'title'     => Mage::helper('aoestatic')->__('Pattern'),
            'class'     => 'required-entry',
            'name'      => 'pattern',
            'required'  => true,
            'value'     => $formValues['pattern'],
            'note'      => 'Should be valid <a href="http://www.php.net/manual/en/reference.pcre.pattern.syntax.php" target="_blank">PHP PCRE Pattern</a>'
        ));

        $form->setUseContainer(true);
        $form->setAction(Mage::helper('adminhtml')->getUrl('*/*/save', array('id' => $model->getId())));
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
