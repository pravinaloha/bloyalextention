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

class Bloyal_Master_Adminhtml_ExecutionController extends Mage_Adminhtml_Controller_Action {

	/**
     * Index action 
     *
     * @param null
     * @return unknown
     */

	public function indexAction(){
		
		$this->_title($this->__('bLoyal'))->_title($this->__('Last Executions'));
			 
		$this->_initAction()
            ->renderLayout();
	}
	

	/**
     * Init action
     *
     * @param null
     * @return unknown
     */
	protected function _initAction(){
		
		$this->loadLayout()
				->_setActiveMenu('bloyal/execution')
				->_addBreadcrumb($this->__('bLoyal'), $this->__('bLoyal'))
				->_addBreadcrumb($this->__('Executions'), $this->__('Executions'));
		return $this;
	}
	
	/**
     * Grid action 
     *
     * @param null
     * @return unknown
     */
	public function gridAction(){
		
		$this->loadLayout(false);
		$this->renderLayout();
	}
	

	/**
     * New Action
     *
     * @param null
     * @return boolean
     */
	public function newAction(){
		
		//Get modules
		$modules = (array)Mage::getConfig()->getNode('modules')->children();
		
    	if(isset($modules['Bloyal_CatalogIntegrator'])){
			Mage::getSingleton('bloyalCatalog/cron')->productsUpdater(false);
			$this->_getSession()->addNotice(Mage::helper('bloyalMaster')->__('The Catalog Integrator Products Updater was executed.'));
		}
     
		if(isset($modules['Bloyal_OrderIntegrator'])){
            
            Mage::getModel('bloyalOrder/cron')->updateOrder(false);
			$this->_getSession()->addNotice(Mage::helper('bloyalMaster')->__('The Order Integrator Order Updater was executed.'));
			
			Mage::getModel('bloyalOrder/cron')->submitOrderToBloyal(false);
			$this->_getSession()->addNotice(Mage::helper('bloyalMaster')->__('The Order Integrator Order Submiter was executed.'));
            
		}
		
		return true;
	}

	/**
     * Is allow 
     *
     * @param null
     * @return unknown
     */

	protected function _isAllowed(){
		
		return Mage::getSingleton('admin/session')->isAllowed('bloyal/executions');
	}
	
}