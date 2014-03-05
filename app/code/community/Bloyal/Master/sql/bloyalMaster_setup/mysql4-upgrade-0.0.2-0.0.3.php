<?php

$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

/****removing old deprecated table****/
if($connection->isTableExists('bloyal_last_execution'))	$connection->dropTable('bloyal_last_execution');
/****removing old deprecated table****/

$table = $installer->getTable('bloyalMaster/execution');
if($connection->isTableExists($table)){
		
	$connection->addColumn($table,
						   'status',
						   array('type'		=> Varien_Db_Ddl_Table::TYPE_TEXT,
								 'comment' 	=> 'Status',
								 'length'  	=> '32',
						   		 'default'	=> Bloyal_Master_Model_Execution_Attribute_Source_Status::COMPLETED));

	$connection->addColumn($table,
						   'qty',
						   array('type'		=> Varien_Db_Ddl_Table::TYPE_INTEGER,
								 'comment' 	=> 'Quantity Executed',
								 'length'  	=> '9',
						   		 'default'	=> 0));

	$connection->addColumn($table,
						   'finished_at',
						   array('type'		=> Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
								'comment' 	=> 'Finished Time'));
	$connection->addColumn($table,
						   'code',
						   array('type'		=> Varien_Db_Ddl_Table::TYPE_TEXT,
								 'comment' 	=> 'Code',
						   		 'length'  	=> '32'));
	
	/****adjusting existing table content to new version****/

	$array = array(Bloyal_CatalogIntegrator_Model_Catalog::TYPE,Bloyal_OrderIntegrator_Model_Order::TYPE);
	$collection = Mage::getModel('bloyalMaster/execution')->getCollection();

	foreach($collection as $item){		
		if($item->getProcessType() == Bloyal_OrderIntegrator_Model_Order::TYPE){
			$item->setCode('updateOrder');
		}elseif($item->getProcessType() == Bloyal_CatalogIntegrator_Model_Catalog::TYPE){
			$item->setCode('productsUpdater');
		}
		
		if(!in_array($item->getProcessType(),$array)) $item->setProcessType(Bloyal_CatalogIntegrator_Model_Catalog::TYPE); 		
 		$item->save();
	}
	
	/****adjusting existing table content to new version****/
}

$installer->endSetup();