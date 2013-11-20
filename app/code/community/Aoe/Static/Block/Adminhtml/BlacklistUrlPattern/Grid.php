<?php
/**
 * Class Aoe_VarnishAsyncCache_Block_Adminhtml_BlacklistUrlPattern_Grid
 *
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoemedia.de>
 */
class Aoe_Static_Block_Adminhtml_BlacklistUrlPattern_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Init grid default properties
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('blacklist_url_list_grid');
        $this->setDefaultSort('blacklist_url_id');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection for grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection Aoe_Static_Model_Resource_BlacklistUrlPattern_Collection */
        $collection = Mage::getResourceModel('aoestatic/blacklistUrlPattern_collection');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Return row URL for js event handlers
     *
     * @param Aoe_Static_Model_BlacklistUrlPattern $row
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
        $this->addColumn('pattern_id', array(
            'header' => Mage::helper('aoestatic')->__('ID'),
            'width'  => '20px',
            'index'  => 'pattern_id'
        ));

        $this->addColumn('pattern', array(
            'header' => Mage::helper('aoestatic')->__('Url Pattern'),
            'width'  => '95%',
            'index'  => 'pattern'
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
                        'confirm' => Mage::helper('aoestatic')->__('Are you sure you want delete url pattern?')
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
        $this->setMassactionIdField('pattern_id');
        $this->getMassactionBlock()->setFormFieldName('pattern_ids');
        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('aoestatic')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('aoestatic')->__('Are you sure you want to delete the selected url pattern(s)?')
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
