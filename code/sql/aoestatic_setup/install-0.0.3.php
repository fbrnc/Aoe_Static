<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * Create table 'aoestatic/custom_url'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('aoestatic/custom_url'))
    ->addColumn('custom_url_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Url Id'
    )
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ), 'Store Id'
    )
    ->addColumn('request_path', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Request Path'
    )
    ->addColumn('max_age', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ), 'Url max-age'
    )
    ->addIndex($installer->getIdxName('aoestatic/custom_url', array('request_path', 'store_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('request_path', 'store_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->setComment('Urls with custom Cache-Control: max-age');
$installer->getConnection()->createTable($table);

$installer->endSetup();
