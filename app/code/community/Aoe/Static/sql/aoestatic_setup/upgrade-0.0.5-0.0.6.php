<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * Create table 'aoestatic/blacklist_url_pattern'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('aoestatic/blacklist_url_pattern'))
    ->addColumn('pattern_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Pattern Id'
    )
    ->addColumn('pattern', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Pattern'
    )
    ->addIndex($installer->getIdxName('aoestatic/blacklist_url_pattern', array('pattern'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('pattern'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->setComment('Blacklist to filter urls added to async cache queue');

$installer->getConnection()->createTable($table);

$installer->endSetup();
