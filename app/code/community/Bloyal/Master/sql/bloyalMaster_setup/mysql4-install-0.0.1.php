<?php

$installer = $this;

$installer->startSetup();

if(!$installer->getConnection()->isTableExists($installer->getTable('bloyalMaster/execution'))){
	$table = $installer->getConnection()
	    ->newTable($installer->getTable('bloyalMaster/execution'))
	    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	        'identity'  => true,
	        'unsigned'  => true,
	        'nullable'  => false,
	        'primary'   => true,
	        ), 'Entity Id')
	    ->addColumn('last_time_run', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
	        'nullable'  => false,
	        ), 'Last Time Run')
	    ->addColumn('process_type', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
	        ), 'Process Type')        
	    ->setComment('bLoyal Last Time Execution');
	$installer->getConnection()->createTable($table);
}

$installer->endSetup();