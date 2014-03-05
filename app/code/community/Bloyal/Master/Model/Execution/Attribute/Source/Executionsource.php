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
class Bloyal_Master_Model_Execution_Attribute_Source_Executionsource{
	
 	/**
     * Constant for set execution source
     */
	CONST EXECUTION_SOURCE_MANUAL 	= 'm';

	/**
     * Constant for set execution source
     */
	CONST EXECUTION_SOURCE_CRON 	= 'c';
	
	/**
     * Function to get all execution source 
     *
     * @param null
     * @return array
     */
	public function getAllOptions(){
		
		$array = array($this->getManualSource()	=> 'Manual',
					   $this->getCronSource()	=> 'Cron');
		
		return $array;
		 
	}
	
	/**
     * Function to retrive Cron source
     *
     * @param null
     * @return String
     */
	public function getCronSource(){
		
		return self::EXECUTION_SOURCE_CRON;
	}
	

	/**
     * Function to retrive Manual source
     *
     * @param null
     * @return String
     */
	public function getManualSource(){
	
		return self::EXECUTION_SOURCE_MANUAL;
	}
}