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


class Bloyal_Master_Model_Resource_Execution_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {

	/**
     * Default constructor
     *
     * @param null
     * @return null
     */
	public function _construct(){

	  $this->_init('bloyalMaster/Execution');
 	}

  }


?>
