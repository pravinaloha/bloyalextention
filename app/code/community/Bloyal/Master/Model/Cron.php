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

class Bloyal_Master_Model_Cron {

	 /**
     * Clean execution table
     *
     * @param null
     * @return unknown
     */
	public function cleanExecutionTable(){

		$oneWeek = Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s','-1 week');
		
		$regs = Mage::getResourceModel('bloyalMaster/execution_collection')->addFieldToFilter('last_time_run',array('lt'=>$oneWeek));
		foreach($regs as $registry) $registry->delete();

		return $this;
	}
}
