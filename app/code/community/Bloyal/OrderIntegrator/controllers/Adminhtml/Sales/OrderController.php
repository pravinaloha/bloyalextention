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


require_once 'Enterprise/SalesArchive/controllers/Adminhtml/Sales/OrderController.php';

class Bloyal_OrderIntegrator_Adminhtml_Sales_OrderController extends Enterprise_SalesArchive_Adminhtml_Sales_OrderController {

	public function viewAction(){
		
     	$this->_title($this->__('Sales'))->_title($this->__('Orders'));
     	$addCss = false;
        if ($order = $this->_initOrder()) {
        	if($order->canEdit() && $order->getBloyalInfo() && $order->getBloyalInfo()->needReviewOrder()){
        		$addCss = true;
				$this->_getSession()->addWarning($this->__('This Order was reported with Problem sending to Director, Please check Order Logs for more information.'));
			}
            $this->_initAction();

            if($addCss) $this->getLayout()->getBlock('head')->addCss('bloyal/css/master.css');

            $this->_title(sprintf("#%s", $order->getRealOrderId()));

            $this->renderLayout();
		}
    }

    public function restoreRetriesAction(){

    	if ($order = $this->_initOrder()) {
			try {

	    		if ($bLoyalInfo = $order->getBloyalInfo()){
					$save = false;
					if($bLoyalInfo->getData('submit_order_retries') == '3'){
						$bLoyalInfo->setData('submit_order_retries',0);
						$save = true;
					}
					if($bLoyalInfo->getData('add_payment_retries') == '3'){
						$bLoyalInfo->setData('add_payment_retries',0);
						$save = true;
					}
					if($bLoyalInfo->getData('approve_order_retries') == '3'){
						$bLoyalInfo->setData('approve_order_retries',0);
						$save = true;
					}
					if($save){

						$bLoyalInfo->setData('notification',0);
						$bLoyalInfo->save();
						$this->_getSession()->addSuccess($this->__('Restore retries Successfully Complete.'));
					}

	    		}else {
	    			$this->_getSession()->addError($this->__('No bLoyal Information. Please check the information on Director.'));
	    		}

			}catch (Mage_Core_Exception $e) {

                $this->_getSession()->addError($e->getMessage());

            }catch (Exception $e) {

                $this->_getSession()->addError($this->__('Restore Retries can\'t be complete.'));

	    	}
		}

    	$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
	}
}