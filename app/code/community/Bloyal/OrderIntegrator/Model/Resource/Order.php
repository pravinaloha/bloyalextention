<?php

class Bloyal_OrderIntegrator_Model_Resource_Order extends Mage_Core_Model_Resource_Db_Abstract {

	public function _construct(){
		
		$this->_init('bloyalOrder/order', 'entity_id');
	}
}