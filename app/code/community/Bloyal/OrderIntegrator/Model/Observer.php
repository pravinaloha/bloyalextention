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

class Bloyal_OrderIntegrator_Model_Observer extends Bloyal_OrderIntegrator_Model_Order {

    public function filterOrder(Varien_Event_Observer $observer){

    	$order = $observer->getEvent()->getOrder();

    	if(!(int)Mage::helper('bloyalOrder')->getGeneralConfig('bloyalOrder/active') || !$order->getData('increment_id')) return $this;

		$this->setOrder($order);
		$this->setBloyalInfo($this->load($order->getData('increment_id'), 'increment_id'));

		if(!$this->getBloyalInfo()->getData() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED){
			$this->orderRegister();
		}elseif($order && $order->getState() == Mage_Sales_Model_Order::STATE_CANCELED){
			$this->orderCancel();
		}

		return $this;
    }

    protected function orderRegister(){

    	$status = self::STATE_NEW;
    	$order = $this->getOrder();
    	$bloyalInfo = $this->getBloyalInfo();

    	if($order->getData('relation_parent_real_id')){

    		$bloyalParent = $this->load($order->getData('relation_parent_real_id'), 'increment_id')->getData();
    		if($bloyalParent){

    			unset($bloyalParent['entity_id'], $bloyalParent['increment_id']);
    			$bloyalInfo->addData($bloyalParent);

    			if(!is_null($bloyalParent['bloyal_id']) && !is_null($bloyalParent['bloyal_payment_id'])){
    				$status = self::STATE_PAYMENT_ADDED;
    			}elseif(!is_null($bloyalParent['bloyal_id']) && is_null($bloyalParent['bloyal_payment_id'])){
    				$status = self::STATE_ORDER_SUBMITTED;
    			}
    		}
    	}

    	$bloyalInfo->setData(array('status'=>(string)$status,
				    			  'increment_id'=>$order->getData('increment_id'),
				    			  'created_time'=>$order->getData('created_at'),
				    			  'updated_time'=>$order->getData('updated_at'),
    							  'submit_order_retries'=>0,
				    			  'add_payment_retries'=>0,
				    			  'approve_order_retries'=>0))
    				->save();

    	return $this;
    }

    protected function orderCancel(){

    	$order          = $this->getOrder();
    	$bloyalInfo     = $this->getBloyalInfo();

    	$invoiceOrder   = Mage::getResourceModel('sales/order_invoice_grid_collection')
							    	->addFieldToSelect('entity_id')
							    	->addFieldToSelect('state')
    								->setOrderFilter($order);
        
    	$hasInvoice     = (bool)$invoiceOrder->count();
    	$shipmentOrder  = Mage::getResourceModel('sales/order_shipment_grid_collection')
							    	->addFieldToSelect('entity_id')
							    	->setOrderFilter($order);
        
    	$hasShipment    = (bool)$shipmentOrder->count();

    	if(!is_null($bloyalInfo->getBloyalId()) && (!$hasInvoice && !$hasShipment)){
    		$this->doCancelOrder();
    	}

    	$status = $bloyalInfo->getStatus();

    	if($status === self::STATE_NEW || $status === self::STATE_ORDER_SUBMITTED || $status === self::STATE_PAYMENT_ADDED){
    		$bloyalInfo->setStatus(self::STATE_CANCELED)
    					->save();
    	}elseif($status == self::STATE_COMPLETE && !$hasInvoice && !$hasShipment){
    		$bloyalInfo->setStatus(self::STATE_COMPLETE_AFTER_CANCELED)
    					->save();
    	}

    	return $this;
    }

    public function addBloyalInfo($observer){

    	$order = $observer->getEvent()->getOrder();
    	$bloyalOrder = Mage::getModel('bloyalOrder/order');

    	$bloyalOrder->load($order->getIncrementId(),'increment_id');

    	if($bloyalOrder->getId()){
    		$order->setBloyalInfo($bloyalOrder);
    	}

		return $this;

    }
    
    public function addButton($observer){
    	
    	$block = $observer->getEvent()->getBlock();
    	
    	if($block->getId() == 'sales_order_view' && $block->getType() == 'adminhtml/sales_order_view'){
    		$order = Mage::registry('sales_order');
    		if ($order && $order->canEdit() && $order->getBloyalInfo() && $order->getBloyalInfo()->needReviewOrder()) {
    			Mage::log($this->getBLoyalRestoreRetriesUrl());
    			$block->addButton('restore_bloyal', array(
    					'label'     => Mage::helper('sales')->__('Restore Order retries'),
    					'onclick'   => 'setLocation(\'' . $block->getUrl('*/*/restoreRetries') . '\')',
    					'class'     => 'bloyal'
    			),0,1);
    		}	
    	}
    	
    	return $this;
    }
    
}