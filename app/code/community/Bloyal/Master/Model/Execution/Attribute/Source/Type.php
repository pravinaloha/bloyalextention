<?php
/**
 * bLoyal
 *
 * NOTICE OF LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade bLoyal to newer
 * versions in the future. If you wish to customize bLoyal for your
 * needs please refer to http://www.bloyal.com for more information.
 *
 * @category    Bloyal
 * @package     Bloyal_Master
 * @copyright   Copyright (c) 2014 bLoyal Inc. (http://www.bloyal.com)
 * @license     http://www.bloyal.com
 */


/**
 * Captcha block
 *
 * @category   Community
 * @package    Bloyal_Master
 * @author     Bloyal Team
 */

class Bloyal_Master_Model_Execution_Attribute_Source_Type{
	
	/**
     * Function to get all execution source type 
     *
     * @param null
     * @return array
     */
	public function getAllOptions(){

		$array = array();
				
		// Get all child modules

		$modules = (array)Mage::getConfig()->getNode('modules')->children();
		
		if(isset($modules['Bloyal_CatalogIntegrator'])){
			$array[Bloyal_CatalogIntegrator_Model_Catalog::TYPE] = Mage::helper('bloyalCatalog')->__('Catalog');
		} 
		
		if(isset($modules['Bloyal_OrderIntegrator'])){
			$array[Bloyal_OrderIntegrator_Model_Order::TYPE] = Mage::helper('bloyalOrder')->__('Order');
		} 
		
		return $array; 
	}
	
}