<?php

$installer = $this;

$installer->startSetup();

if(!$installer->getConnection()->isTableExists($installer->getTable('bloyalOrder/order'))){
	$table = $installer->getConnection()
	    ->newTable($installer->getTable('bloyalOrder/order'))
	    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	        'identity'  => true,
	        'unsigned'  => true,
	        'nullable'  => false,
	        'primary'   => true,
	        ), 'Entity Id')
	    ->addColumn('increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
	        ), 'Increment Id')
		->addColumn('bloyal_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	        ), 'bLoyal Id')
		->addColumn('bloyal_customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	        ), 'bLoyal Customer Id')
	    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
	        ), 'Status')
	    ->addColumn('submit_order_retries', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	    	'unsigned'  => true,
	    	'default' 	=> 0
	       ), 'Submit Order Rretries')
		->addColumn('bloyal_payment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	       	'unsigned'  => true,
			'default' 	=> null
	    	), 'bLoyal Payment Id')
		->addColumn('add_payment_retries', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	    	'unsigned'  => true,
			'default' 	=> 0
			), 'Add Payment Retries')
		->addColumn('approve_order_retries', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
			'unsigned'  => true,
			'default' 	=> 0
			), 'Approve Order Retries')
		->addColumn('notification', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
			'unsigned'  => true,
			'nullable'  => false,
			'default'   => '0'
			), 'Notification')				              
	    ->addColumn('created_time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
	        'nullable'  => false,
	        ), 'Created At')
	    ->addColumn('updated_time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
	        'nullable'  => false,
	        ), 'Updated At')
	    ->addIndex($installer->getIdxName('bloyalOrder/order', array('increment_id')),
	        array('increment_id'))
	    ->addForeignKey($installer->getFkName('bloyalOrder/order', 'increment_id', 'sales/order', 'increment_id'),
	        'increment_id', $installer->getTable('sales/order'), 'increment_id',
	        Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
	    ->setComment('bLoyal Order Entity');
	$installer->getConnection()->createTable($table);
}

$installer->endSetup();