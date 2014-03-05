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
 * @package     Bloyal_OrderIntegrator
 * @copyright   Copyright (c) 2014 bLoyal Inc. (http://www.bloyal.com)
 * @license     http://www.bloyal.com
 */


/**
 * Captcha block
 *
 * @category   Community
 * @package    Bloyal_OrderIntegrator
 */

class Bloyal_OrderIntegrator_Helper_Data extends Bloyal_Master_Helper_Data{
	
	public function getGeneralConfig($field){
	
		return Mage::getStoreConfig('bloyalorder/'.$field, Mage::app()->getStore()->getStoreId());
	}
}