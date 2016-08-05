<?php
/**
 * Class Aoe_Static_Block_Adminhtml_CustomUrl_Grid
 *
 * @author Dmytro Zavalkin
 */
class Aoe_Static_Block_Adminhtml_CustomUrl_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Init grid default properties
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('custom_url_list_grid');
        $this->setDefaultSort('custom_url_id');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection for grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection Aoe_Static_Model_Resource_CustomUrl_Collection */
        $collection = Mage::getResourceModel('aoestatic/customUrl_collection');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Return row URL for js event handlers
     *
     * @param Aoe_Static_Model_CustomUrl $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    /**
     * Prepare Grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('custom_url_id', array(
            'header' => Mage::helper('aoestatic')->__('ID'),
            'width'  => '10px',
            'index'  => 'custom_url_id'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'     => Mage::helper('aoestatic')->__('Store View'),
                'width'      => '200px',
                'index'      => 'store_id',
                'type'       => 'store',
                'store_view' => true,
            ));
        }

        $this->addColumn('request_path', array(
            'header' => Mage::helper('aoestatic')->__('Request Path'),
            'width'  => '80%',
            'index'  => 'request_path'
        ));
        $this->addColumn('max_age', array(
            'header' => Mage::helper('aoestatic')->__('Max Age'),
            'width'  => '50px',
            'index'  => 'max_age'
        ));

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('aoestatic')->__('Actions'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('aoestatic')->__('Delete'),
                        'url'     => array(
                            'base' => '*/*/delete',
                        ),
                        'field'   => 'id',
                        'confirm' => Mage::helper('aoestatic')->__('Are you sure you want to delete custom url?')
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Prepare grid massaction actions
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('custom_url_id');
        $this->getMassactionBlock()->setFormFieldName('custom_url_ids');
        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('aoestatic')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('aoestatic')->__('Are you sure you want delete selected custom url(s)?')
        ));

        return $this;
    }
    /**
     * Grid url getter
     *
     * @return string current grid url
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
