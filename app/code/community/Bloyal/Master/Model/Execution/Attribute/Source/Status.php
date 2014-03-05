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

class Bloyal_Master_Model_Execution_Attribute_Source_Status{
	
 	/**
     * Constant for status running
     */
	CONST  RUNNING 		= 'running';

	/**
     * Constant for status error
     */

	CONST  ERROR 	 	= 'error';

	/**
     * Constant for status completed
     */

	CONST  COMPLETED 	= 'completed';
	
	/**
     * Function to get all execution status 
     *
     * @param null
     * @return array
     */

	public function getAllOptions(){

		$array = array(self::RUNNING 	=> Mage::helper('bloyalCatalog')->__('Running'),
					   self::ERROR 		=> Mage::helper('bloyalCatalog')->__('Error'),
					   self::COMPLETED 	=> Mage::helper('bloyalCatalog')->__('Completed'));
						
		return $array; 
	}
	
}