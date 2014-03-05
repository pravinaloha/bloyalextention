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
class Bloyal_Master_Block_Adminhtml_Execution_Grid extends Mage_Adminhtml_Block_Widget_Grid{

	/**
	 * Default constructor
	 */
	public function __construct(){

		parent::__construct();

		$this->setId('execution_grid');

		$this->setUseAjax(true); 

        $this->setDefaultSort('entity_id');

        $this->setDefaultDir('DESC');

		$this->setSaveParametersInSession(true);
	}

	 /**
	 * Get Collection Class
	 *
	 * @param null
	 * @return unknown
	 */
	protected function _getCollectionClass(){
		
		return 'bloyal/execution_grid_collection';
	}
	
	/**
     * Returns fields  
     *
     * @return fields
     */
 	protected function _prepareCollection(){

 		$collection = Mage::getResourceModel('bloyalMaster/execution_collection');
 		$this->setCollection($collection);
 		
 		return parent::_prepareCollection();
 	}
	
	/**
     * Returns collection 
     *
     * @return _prepareCollection
     */
	protected function _prepareColumns(){

		// Create helper object
		$helper = Mage::helper('bloyalMaster');

		// Add columns into form
		$this->addColumn('entity_id', array(
				'header'    => $helper->__('ID'),
				'index'     => 'entity_id',
				'width'     => '50px',
				'align'     => 'center',
				'type'      => 'text'
		));

		$this->addColumn('code', array(
				'header'    => $helper->__('Code'),
				'index'     => 'code',
				'type'      => 'text'
		));

	
		$this->addColumn('process_type', array(
				'header'    => $helper->__('Type'),
				'width'     => '120px',
				'align'     => 'center',
				'index'     => 'process_type',
				'type'      => 'options',
				'options' 	=> Mage::getSingleton('bloyalMaster/execution_attribute_source_type')->getAllOptions(),
		));
		
		$this->addColumn('execution_source', array(
				'header'    => $helper->__('Execution Source'),
				'width'     => '120px',
				'align'     => 'center',
				'index'     => 'execution_source',
				'type'      => 'options',
				'options' 	=> Mage::getSingleton('bloyalMaster/execution_attribute_source_executionsource')->getAllOptions(),
		));

		$this->addColumn('status', array(
				'header'    => $helper->__('Status'),
				'width'     => '120px',
				'align'     => 'center',
				'index'     => 'status',
				'type'      => 'options',
				'options' 	=> Mage::getSingleton('bloyalMaster/execution_attribute_source_status')->getAllOptions(),
		));
		
		$this->addColumn('started_at', array(
				'header'    => $helper->__('Started At'),
				'index'     => 'last_time_run',
				'width'     => '160px',
				'type'      => 'datetime',
				'align'     => 'center',
				'gmtoffset' => true
		));
		
		$this->addColumn('finished_at', array(
				'header'    => $helper->__('Finished At'),
				'index'     => 'finished_at',
				'width'     => '160px',
				'type'      => 'datetime',
				'align'     => 'center',
				'gmtoffset' => true
		));
								
		return $this;
	}
	

	/**
     * Returns grid url 
     *
     * @return String
     */
	public function getGridUrl(){
		
		return $this->getUrl('*/*/grid', array('_current'=>true));
	}
}