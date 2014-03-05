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
class Bloyal_Master_Block_Adminhtml_Execution extends Mage_Adminhtml_Block_Widget_Grid_Container{
	
	public function __construct(){
		// set controller
  		$this->_controller = 'adminhtml_execution';

  		// set block group
  		$this->_blockGroup = 'bloyalMaster';

  		// set header text
 		$this->_headerText = Mage::helper('bloyalMaster')->__('Last Executions');
 		
 		parent::__construct();

 		// remove default add button
 		$this->_removeButton('add');


 		// Add new button
		$this->_addButton('new', array(
				'label'     => Mage::helper('bloyalMaster')->__('Run All Tasks Now'),
				'onclick' => "runAllbLoyalActions('{$this->getUrl('*/*/new')}')",
				'class'     => 'save'
				));
 	}
	
}