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

class Bloyal_OrderIntegrator_Model_Cron {

    /**
     * Function to submit orders to bLoyal
     *
     * @param $source
     * @return unknown
     */
	public function submitOrderToBloyal($source){

        //Load order model
		$orderModel = Mage::getModel('bloyalOrder/order');
        
        //Load bloyal order helper
		$helper = Mage::helper('bloyalOrder');

        // Check for API and Order module status
		if(!(int)$helper->getGeneralConfig('bloyalOrder/active') || !$orderModel->getApi() || !$orderModel->getMageClient()){
			$helper->sendNotification('Connectivity problem','');
			return $this;
		}

		$type = Bloyal_OrderIntegrator_Model_Order::TYPE;
		$row = $helper->createRow($type, 'submitOrderToBloyal', $source);
		
		$collection = $orderModel->getCollection()
								 ->addFieldToSelect('*')
								 ->addFieldToFilter('status',array('nin'=> array( Bloyal_OrderIntegrator_Model_Order::STATE_COMPLETE,
																				  Bloyal_OrderIntegrator_Model_Order::STATE_ORDER_APPROVED,
																				  Bloyal_OrderIntegrator_Model_Order::STATE_COMPLETE_AFTER_CANCELED,
																				  Bloyal_OrderIntegrator_Model_Order::STATE_CANCELED)))
								 ->addFieldToFilter('submit_order_retries',array('lt'=> '3'))
								 ->addFieldToFilter('add_payment_retries',array('lt'=> '3'))
								 ->addFieldToFilter('approve_order_retries',array('lt'=> '3'));

		$collectionToArray = $collection->toArray();
        

		if($collectionToArray['totalRecords'] > 0){

			$orders = array();
			foreach($collectionToArray['items'] as $bItem) $orders[$bItem['increment_id']] = $bItem['status'];

			$ordersCollection = Mage::getResourceModel('sales/order_collection')->addFieldToFilter('increment_id', array('in'=>array_keys($orders)));
			foreach($ordersCollection as $order){

				$incrementId = $order->getIncrementId();
				$orderModel->setOrder($order);
				$orderModel->setBloyalInfo($collection->getItemByColumnValue('increment_id',$incrementId));

				try{
					if(array_key_exists($incrementId, $orders)){
						switch($orders[$incrementId]){
							case Bloyal_OrderIntegrator_Model_Order::STATE_NEW:

								$orderModel->doSubmitOrder();
								$orderModel->doAddPayment();
								$orderModel->doApproveOrder();
								break;
							case Bloyal_OrderIntegrator_Model_Order::STATE_ORDER_SUBMITTED:

								$orderModel->doAddPayment();
								$orderModel->doApproveOrder();
								break;
							case Bloyal_OrderIntegrator_Model_Order::STATE_PAYMENT_ADDED:

								$orderModel->doApproveOrder();
								break;
						}
					}
				}catch(Exception $e){

					$fullError = $e->faultstring;
					$fullCode = $e->faultcode;

					$msg = 'Order With Problem, please check this errors: <br/>';
					$msg .= (strlen($fullError) >0 && strlen($fullCode) >0)? ('Error Msg: '. $fullError . "<br/>" . 'Error Code: '. $fullCode) : ($e->getMessage()) ;

					$bloyalInfo = $orderModel->getBloyalInfo();
					if($bloyalInfo->getData('submit_order_retries') || $bloyalInfo->getData('add_payment_retries') || $bloyalInfo->getData('approve_order_retries')){

						$retries = 'THIS ORDER HAD SOMETHING WRONG SO STOP TO SEND INFO TO BLOYAL<BR>THE PROCESS TO SEND THIS ORDER TO BLOYAL IT\'S INCOMPLETE!:';
						$retries .= '<br/>Retries:<br/>';
						$retries .= '<b>Submit Order retries: '.$bloyalInfo->getData('submit_order_retries').'</b><br/>';
						$retries .= '<b>Add Payment retries: '.$bloyalInfo->getData('add_payment_retries').'</b><br/>';
						$retries .= '<b>Approve Order retries: '.$bloyalInfo->getData('approve_order_retries').'</b><br/>';

						$msg = $retries . '<br>'. $msg;
					}

					$order->addStatusHistoryComment($msg,Bloyal_OrderIntegrator_Model_Order::STATE_NEEDREVIEW_SENDINFO);
					$order->save();

				}
			}
		}
		
		$helper->updateRow($row,Bloyal_Master_Model_Execution_Attribute_Source_Status::COMPLETED);
		
		return $this;
	}

    /**
     * Function to Update orders to Magento
     *
     * @param $source
     * @return unknown
     */
	public function updateOrder($source){

		$orderModel = Mage::getModel('bloyalOrder/order');
		$helper = Mage::helper('bloyalOrder');

		if(!(int)$helper->getGeneralConfig('bloyalOrder/active') || !$orderModel->getApi() || !$orderModel->getMageClient()){
			$helper->sendNotification('Connectivity problem','');
			return $this;
		}
		
		$type = Bloyal_OrderIntegrator_Model_Order::TYPE;		 
		$row = $helper->createRow($type, 'updateOrder', $source);
		$helper->getLastExecution($type, 'updateOrder');
        
		$orderModel->getUpdatedOrders($helper->getLastExecution($type, 'updateOrder'));		
				
        // If orders found into bloyal with status changed
        
		if(count($orderModel->getBloyalIds())){
            if(isset($orderModel->getBloyalIds()->OrderNumber) && $orderModel->getBloyalIds()->OrderNumber)
            {
                $this->updateStatusFromBloyalToMagento($orderModel->getBloyalIds()->OrderNumber);
            }
            else
            {
                foreach($orderModel->getBloyalIds() as $bloyalOrderId){
                     $this->updateStatusFromBloyalToMagento($bloyalOrderId->OrderNumber);
                }
            }
		}

		$helper->updateRow($row,Bloyal_Master_Model_Execution_Attribute_Source_Status::COMPLETED);
		
		return $this;
	}
    
    /**
     * Function to update status of each order into magento
     *
     * @param Integer $orderId
     * @return unknown
     */
	public function updateStatusFromBloyalToMagento($orderId){
        
        $orderModel = Mage::getModel('bloyalOrder/order');
		$helper = Mage::helper('bloyalOrder');
        $method = $helper->getGeneralConfig('bloyalOrder/method');
        
        $bloyalOrder = $orderModel->getBloyalOrder($orderId);
        
        if(($method == Bloyal_OrderIntegrator_Model_Order::ACTION_AUTHORIZE && $bloyalOrder->Status =='Closed') ||
					($method == Bloyal_OrderIntegrator_Model_Order::ACTION_AUTHORIZE_CAPTURE && $bloyalOrder->Status =='Approved' && $bloyalOrder->Shipments->OrderShipment->Status == 'Shipped')){

					$incrementId =  $bloyalOrder->ExternalId;
					if(!$incrementId){
						Mage::log('The bLoyalOrderId = '.$orderId.' do not have IncrementId in bLoyal.','3','bLoyal_complete.log');
						continue;
					}

					try {
						Mage::log('Processing IncrementId = '.$incrementId . ' bLoyalOrderId = '.$orderId,Zend_Log::DEBUG,'bLoyal_complete.log');
						
			    		$mageOrder = $orderModel->getSalesOrderInfo($incrementId);
			    		if($mageOrder['status'] != 'complete'){
			    			$orderModel->doShipment();

			    			if($method == Bloyal_OrderIntegrator_Model_Order::ACTION_AUTHORIZE){
			    				$orderModel->doInvoice();
			    				$orderModel->doCapture();
			    				$orderModel->doLast();
			    			}
			    		}

			    	}catch(Exception $e){
			    		if((bool)$helper->getGeneralConfig('bloyalOrder/logs')){
			    			$msg = 'Crashed order: '.$incrementId;
			    			if(isset($e->faultstring)) $msg .= ' Fault String: '.$e->faultstring;
			    			if(isset($e->faultcode)) $msg .= ' Fault Code: '.$e->faultcode;
			    			if($e->getMessage()) $msg .= ' Message: '.$e->getMessage();
			    			Mage::log($msg,Zend_Log::DEBUG,'bloyalIssues.log');
			    		}

			    	}
				}
        
    }
    
    

    /**
     * Function to send orders problems notifications
     *
     * @param null
     * @return unknown
     */
	public function sendOrdersProblemsNotification(){

		$orderModel = Mage::getModel('bloyalOrder/order');
		$helper = Mage::helper('bloyalOrder');

		if(!(int)$helper->getGeneralConfig('bloyalOrder/active') || !$orderModel->getApi() || !$orderModel->getMageClient()){
			$helper->sendNotification('Connectivity problem','');
			return $this;
		}

		$collection = Mage::getResourceModel('bloyalOrder/order_collection')->addFieldToSelect('*')
																			->addFieldToFilter('notification','0');

		$collection->getSelect()->where('main_table.submit_order_retries = ? ' .
										'OR main_table.add_payment_retries = ? ' .
										'OR main_table.approve_order_retries = ?',3);

		if(count($collection)){

			$ordersProblems = $collection->load();

			$html = '<table class="order-content" border="1" cellspacing="0" >' .
						'<tr>' .
							'<th style="padding:0 5px">Mage Order Id</th><th style="padding:0 5px">Bloyal OrderId</th><th style="padding:0 5px">Submit Order Retries</th><th style="padding:0 5px">Add Payment Retries</th><th style="padding:0 5px">Approve Order Retries</th>' .
						'</tr>';

			foreach($ordersProblems as $_order){
				$html .= '<tr>' .
							'<td style="text-align:center">'.$_order->getIncrementId().'</td><td style="text-align:center">'.$_order->getBloyalId().'</td><td style="text-align:center">'.$_order->getData('submit_order_retries').'</td><td style="text-align:center">'.$_order->getData('add_payment_retries').'</td><td style="text-align:center">'.$_order->getData('approve_order_retries').'</td>' .
						'</tr>';
				$_order->setNotification(1);
				$_order->save();
			}

			$html .= '</table>';
			$html .= '<style>.order-content td{text-align:center} .order-content th {padding:0 5px}</style>';

			$message = 'The system detected some orders with problems trying to register a order in bLoyal System.<br/>Please check Manualy the information of All the Order in the Table below.';

			$helper->sendNotification($html, $message);
		}

		return $this;
	}
}
