<?php

$installer = $this;

$installer->startSetup();

if($installer->getConnection()->isTableExists($installer->getTable('bloyalMaster/execution'))){
	$installer->getConnection()->addColumn($installer->getTable('bloyalMaster/execution'),
										   'execution_source',
										   array('type' 	=> Varien_Db_Ddl_Table::TYPE_TEXT,
											     'comment' 	=> 'Execution Source',
												 'length'  => '32'));
}

$installer->endSetup();