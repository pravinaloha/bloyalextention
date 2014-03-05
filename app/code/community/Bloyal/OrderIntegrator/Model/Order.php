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
 * @category    Model
 * @package     Model_Cron
 * @copyright   Copyright (c) 2012 bLoyal Inc. (http://www.bloyal.com)
 * @license     http://www.bloyal.com
 */

/**
 * Media library Image model
 *
 * @category   Bloyal
 * @package    Bloyal_OrderIntegrator
 */

class Bloyal_OrderIntegrator_Model_Order extends Bloyal_Master_Model_Abstract {

    const ACTION_AUTHORIZE_CAPTURE          = 'authorize_capture';
    const ACTION_AUTHORIZE                  = 'authorize';
    const TYPE                              = 'o';
    const STATE_NEW                         = 'new';
    const STATE_CANCELED                    = 'canceled';
    const STATE_COMPLETE                    = 'complete';
    const STATE_PAYMENT_ADDED               = 'payment_added';
    const STATE_ORDER_APPROVED              = 'order_approved';
    const STATE_ORDER_SUBMITTED             = 'order_submitted';
    const STATE_NEEDREVIEW_SENDINFO         = 'needreview_sendinfo';
    const STATE_COMPLETE_AFTER_CANCELED     = 'complete_after_canceled';

    protected $_order            = '';
    protected $_itemsQty         = array();
    protected $_mageOrder        = '';
    protected $_invoiceId        = '';
    protected $_bloyalIds        = array();
    protected $_bloyalInfo       = '';
    protected $_bloyalOrder      = '';
    protected $_bloyal_customer = '';
    protected $_customer_is_new = false;


    public function _construct(){

        parent::_construct();
        $this->_init('bloyalOrder/order');
    }

    public function getOrder(){

        return $this->_order;
    }

    public function setOrder($order){

        $this->_order = $order;
        return $this;
    }

    public function getBloyalInfo(){

        return $this->_bloyalInfo;
    }

    public function setBloyalInfo($info){

        $this->_bloyalInfo = $info;
        return $this;
    }

    public function getInvoiceId(){

        return $this->_invoiceId;
    }

    public function setInvoiceId($id){

        $this->_invoiceId = $id;
        return $this;
    }

    public function getBloyalIds(){
    
        return $this->_bloyalIds;
    }
    
    /**
     * Function to submit orders to bLoyal
     *
     * @param null
     * @return unknown
     */
    public function doSubmitOrder(){

        $bloyalInfo = $this->getBloyalInfo();
        $bloyalInfo->setData('submit_order_retries', (int)$bloyalInfo->getData('submit_order_retries')+1);
        $bloyalInfo->save();

        $order = $this->getOrder();

        $orderToBloyal = array( 'Title'     => 'Web Store Order: '.date('Y-m-d H:i:s'),
                                'Customer'  => $this->getCustomer(),
                                'Shipments' => array('OrderShipment' => $this->getShipmentInfo()),
                                'Lines'     => $this->getOrderLineInfo(),
                                'ExternalId'=> $order->getIncrementId(),
                                'Comment'   => $this->getOrderComments());

        $options = array();

        $options[] = 'Validate';

        if($this->_customer_is_new){
            $this->_customer_is_new = false;
            $options[] = 'CreateCustomer';
        }

        $params = array('credential'=> $this->getCredentials(),
                        'deviceKey' => Mage::helper('bloyalMaster')->getBloyalConfig('general/device_key'),
                        'order'     => $orderToBloyal,
                        'options'   => $options);

        if((bool)Mage::helper('bloyalOrder')->getGeneralConfig('bloyalOrder/logs')){
            Mage::log('doSubmit',Zend_Log::DEBUG,'bloyalDebug.log');
            Mage::log($params,Zend_Log::DEBUG,'bloyalDebug.log');
        }

        $orderResult = $this->getApi()->SubmitOrder($params)->SubmitOrderResult;

        if($orderResult){

            $order->addStatusHistoryComment('Order was update In Bloyal with  OrderNumber: '. $orderResult);
            $order->save();

            $bloyalInfo->setData('updated_time', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
            $bloyalInfo->setData('bloyal_id', $orderResult);
            $bloyalInfo->setData('status', self::STATE_ORDER_SUBMITTED);
            $bloyalInfo->save();
        }
        return $this;
    }

    /**
     * Function to add payment
     *
     * @param null
     * @return unknown
     */
    public function doAddPayment(){

        $paymentCode = '';

        $bloyalInfo = $this->getBloyalInfo();
        $bloyalInfo->setData('add_payment_retries', (int)$bloyalInfo->getData('add_payment_retries')+1);
        $bloyalInfo->save();

        $order = $this->getOrder();

        $paymentData = $order->getPayment();

        $credit_card_array =
                array( 
                        'AE' => 'AMEX',
                        'VI' => 'Visa',
                        'MC' => 'MasterCard',
                        'DI' => 'DdoSubmitOrderISCOVER',
                        'JCB'=> 'JCB',
                        'MCI'=> 'MAESTRO INTERNATIONAL',
                        'SM' => 'SWITCH/MAESTRO',
                        'SO' => 'SOLO',
                        'OT' => 'OTHER'
                    );

        if($paymentData->getMethod() == 'authorizenet') {
            $paymentCode = 'VC';
            $amount = $paymentData->getData('base_amount_authorized');
        }elseif($paymentData->getMethod() == 'paypal_express'){
            $paymentCode = 'PayPros';
            $amount = $paymentData->getData('base_amount_authorized');
        }elseif($paymentData->getMethod() == 'checkmo'){
            $paymentCode = 'DIRCASH';
            $amount = $order->getGrandTotal();
        }elseif($paymentData->getMethod() == 'ccsave'){
            $paymentCode = 'AXIA';
            $amount = $order->getGrandTotal();
        }elseif($paymentData->getMethod() == 'free'){
            $paymentCode = 'AXIA';
            $amount = $order->getGrandTotal();
        }

        $shipments = $this->getShipmentInfo();
        $shipments['Title'] = '';

        unset($shipments['Recipient']['Instructions'], $shipments['Recipient']['Primary'], $shipments['Recipient']['Title'],
              $shipments['Recipient']['TaxDetails'], $shipments['Recipient']['TotalAmount']);

        if($paymentCode){

            $info = array('TenderCode'=> $paymentCode,
                           'Amount'    => $amount);

            //if(Mage::helper('bloyalOrder')->getGeneralConfig('bloyalOrder/method') == self::ACTION_AUTHORIZE_CAPTURE) $info['External'] = true;
            $info['External'] = true;

            $params = array('credential'=> $this->getCredentials(),
                            'orderNumber'=> $bloyalInfo->getBloyalId(),
                            'shipment'   => $shipments,
                            'payment'    => $info);

            if((bool)Mage::helper('bloyalOrder')->getGeneralConfig('bloyalOrder/logs')){
                Mage::log('addPayment',Zend_Log::DEBUG,'bloyalDebug.log');
                Mage::log($params,Zend_Log::DEBUG,'bloyalDebug.log');
            }

            $paymentRequest = $this->getApi()->AddOrderPayment($params);
            $addPaymentId = $paymentRequest->AddOrderPaymentResult;
            if($addPaymentId){

                $bloyalInfo->setData('bloyal_payment_id', $addPaymentId);
                $bloyalInfo->setStatus(self::STATE_PAYMENT_ADDED);
                $bloyalInfo->save();

                $order->addStatusHistoryComment('Order was update In Bloyal with  PaymentNumber: '. $addPaymentId);
                $order->save();
            }
        }

        return $this;
    }

    /**
     * Function to Approve orders
     *
     * @param null
     * @return unknown
     */
    public function doApproveOrder(){

        $bloyalInfo = $this->getBloyalInfo();
        $orderInfo = $this->getOrder();

        $bloyalInfo->setData('approve_order_retries', (int)$bloyalInfo->getData('approve_order_retries')+1);
        $bloyalInfo->save();

        $params = array( 'credential'   => $this->getCredentials(),
                         'orderNumber'  => $bloyalInfo->getBloyalId());

        if((bool)Mage::helper('bloyalOrder')->getGeneralConfig('bloyalOrder/logs')){
            
            Mage::log('ApproveOrder',Zend_Log::DEBUG,'bloyalDebug.log');
            Mage::log($params,Zend_Log::DEBUG,'bloyalDebug.log');
            
        }

        $method = Mage::helper('bloyalOrder')->getGeneralConfig('bloyalOrder/method');
        $approved_order = ($method == self::ACTION_AUTHORIZE_CAPTURE && $orderInfo->getPayment()->getMethod() != 'free')? $this->getApi()->CaptureOrder($params) : $this->getApi()->ApproveOrder($params);

        $bloyalInfo->setStatus(self::STATE_ORDER_APPROVED);
        $bloyalInfo->save();

        return $this;
    }

    /**
     * Function to get updated orders from bLoyal
     *
     * @param $lastDate
     * @return array bloyal order ids
     */
    public function getUpdatedOrders($lastDate = ''){

        try{
            
            $params = array('credential' => $this->getCredentials());
            if($lastDate) $params['changedSince'] = array('ChangeDate' => str_replace(' ','T',$lastDate),'OrderNumber' => 0); 

            $result = $this->getApi()->GetOrderChanges($params)->GetOrderChangesResult;
            
            $checkResult = (array)$result;
            
            if(!empty($checkResult)) $this->_bloyalIds = $result->OrderChange;
            
        }catch (Exception $e){
            
            return false;
        }

        return $this->_bloyalIds;
    }

    /**
     * Function to get bloyal orders
     *
     * @param Integer $bloyalOrderId
     * @return unknown
     */
    
    public function getBloyalOrder($bloyalOrderId = ''){

        if($bloyalOrderId){
            try{
                $this->_bloyalOrder = $this->getApi()->GetOrder(array('credential' => $this->getCredentials(),
                                                                      'orderNumber' => $bloyalOrderId))->GetOrderResult;
            }catch (Exception $e){
                return false;
            }
        }
        return $this->_bloyalOrder;
    }
    
    /**
     * Function to cancel bloyal orders
     *
     * @param String $message
     * @return unknown
     */
    public function doCancelOrder($message = ''){

        $bloyalInfo = $this->getBloyalInfo();

        $params = array('credential'       =>$this->getCredentials(),
                        'orderNumber'      =>$bloyalInfo->getBloyalId(),
                        'cancelReasonCode' =>$message);

        try{
            $this->getApi()->CancelOrder($params);
        }catch(Exception $e){
            Mage::log($e->faultstring .' '. $e->faultcode . ' bloyalId #'.$bloyalInfo->getBloyalId(),Zend_Log::DEBUG,'bloyalIssues.log');
        }
        return $this;
    }

    /**
     * Function to get customer details
     *
     * @param null
     * @return unknown
     */

    public function getCustomer(){

        $customer = array();

        if(isset($customer['ExternalId']) && $customer['ExternalId'] !=''){

            $this->_bloyal_customer = $customer;
            $customerInfo = array(  'Address1'          =>$customer->Address1,
                                    'Address2'          =>$customer->Address2,
                                    'BirthDate'         =>$customer->BirthDate,
                                    'City'              =>$customer->City,
                                    'CompanyName'       =>$customer->CompanyName,
                                    'EmailAddress'      =>$customer->EmailAddress,
                                    'Country'           =>$customer->Country,
                                    'FaxNumber'         =>$customer->FaxNumber,
                                    'FirstName'         =>$customer->FirstName,
                                    'FirstName2'        =>$customer->FirstName2,
                                    'LastName'          =>$customer->LastName,
                                    'LastName2'         =>$customer->LastName2,
                                    'MobilePhone'       =>$customer->MobilePhone,
                                    'NickName'          =>$customer->NickName,
                                    'Phone1'            =>$customer->Phone1,
                                    'Phone2'            =>$customer->Phone2,
                                    'State'             =>$customer->State,
                                    'ZipCode'           =>$customer->ZipCode,
                                    'AccountNumber'     =>$customer->AccountNumber,
                                    'ExternalReferences'=>$customer->ExternalReferences,
                                    'SignupStoreCode'   =>$customer->SignupStoreCode,
                                    'ExternalId'        =>$customer->ExternalId,
                            );
            
        }else{

            $this->_customer_is_new = true;
            $order = $this->getOrder();
            $custmoerAddress = $order->getBillingAddress();
            $streets = explode("\n", $custmoerAddress->getData('street'));

            $customerInfo = array(  'Address1'          => $streets[0],
                                    'Address2'          => (isset($streets[1])?$streets[1]:''),
                                    'BirthDate'         => Mage::getModel('core/date')->date('Y-m-d'),
                                    'City'              => $custmoerAddress->getData('city'),
                                    'CompanyName'       => '',
                                    'EmailAddress'      => $order->getData('customer_email'),
                                    'Country'           => $custmoerAddress->getData('country_id'),
                                    'FaxNumber'         => $custmoerAddress->getData('fax'),
                                    'FirstName'         => $order->getData('customer_firstname'),
                                    'FirstName2'        => '',
                                    'LastName'          => $order->getData('customer_lastname'),
                                    'LastName2'         => '',
                                    'MobilePhone'       => '',
                                    'NickName'          =>'',
                                    'Phone1'            => $custmoerAddress->getData('telephone'),
                                    'Phone2'            =>'',
                                    'State'             => Mage::getSingleton('directory/region')->load($custmoerAddress->getData('region_id'))->getCode(),
                                    'ZipCode'           => $custmoerAddress->getData('postcode'),
                                    'AccountNumber'     =>'',
                                    'ExternalReferences'=>'',
                                    'SignupStoreCode'   =>'',
                                    'ExternalId'        => $custmoerAddress->getData('customer_id'),
                                    );
        }
        return $customerInfo;
    }

    /**
     * Function to get shipping information
     *
     * @param null
     * @return unknown
     */
    public function getShipmentInfo(){

        $order = $this->getOrder();

        $paymentMethod = $order->getPayment()->getMethod();

        $shippingInfo = $order->getShippingAddress();
        $agentInfo = $this->getShippingAgentInfo();

        $streets = explode("\n", $shippingInfo->getData('street'));

        $shipment = array(
                        'Recipient'=>array( 
                                            'Address1' => $streets[0],
                                            'Address2'   => (isset($streets[1])?$streets[1]:''),
                                            'BirthDate'  => Mage::getModel('core/date')->date('Y-m-d'),
                                            'City'       => $shippingInfo->getData('city'),
                                            'CompanyName'=> '',
                                            'Country'    => $shippingInfo->getData('country_id'),
                                            'EmailAddress'=> $shippingInfo->getData('email'),
                                            'FaxNumber'  => '',
                                            'FirstName'  => $shippingInfo->getData('firstname'),
                                            'FirstName2' => '',
                                            'LastName'   => $shippingInfo->getData('lastname'),
                                            'LastName2'  => '',
                                            'MobilePhone'=> '',
                                            'NickName'   => '',
                                            'Phone1'     => $shippingInfo->getData('telephone'),
                                            'Phone2'     => '',
                                            'State'      => Mage::getSingleton('directory/region')->load($shippingInfo->getData('region_id'))->getCode(),
                                            'ZipCode'    => $shippingInfo->getData('postcode'),
                                            'Instructions'=> '',
                                            'Primary'    => '1',
                                            'Title'      => 'Web Shipping Address'),
                            'Tax'        => $order->getData('base_shipping_tax_amount'),
                            'TaxDetails' => array('0'=> array('Amount'=>$order->getData('base_shipping_tax_amount'),
                                                              'Code' => Mage::getSingleton('directory/region')->load($shippingInfo->getData('region_id'))->getCode(),
                                                              'Rate' => $order->getData('store_to_order_rate'))),
                            'TotalAmount'=> $order->getData('base_shipping_amount'),
                            'CarrierCode'=> ($agentInfo['carrier_code'] ? $agentInfo['carrier_code'] : 'No Carrier Code Info Available'),
                            'ServiceCode'=> ($agentInfo['service_code'] ? $agentInfo['service_code'] : 'No Service Code Info Available'),
                            'Charge'     => $order->getData('base_shipping_amount'),
                            'Type'       => 'Ship'); //Shipment Type can be "Ship", "Pickup", "Electronic"


        Mage::log($paymentMethod,Zend_Log::DEBUG,'bloyalDebug.log');

        if($paymentMethod == 'free'){
            $shipment['Discount'] = $order->getData('base_shipping_amount');
        };

        return $shipment;
    }

    /**
     * Function to order line information
     *
     * @param null
     * @return unknown
     */
    public function getOrderLineInfo(){

        $order = $this->getOrder();
        $itemCollection = $order->getItemsCollection();
        foreach($itemCollection as $item){

            /*if ordeItem is Configurable the orderItem Child no add to lines*/
            if($item->getParentItem()) continue;

            $dicountAmount = $item->getBaseDiscountAmount();
            if($dicountAmount > 0) $dicountAmount = $dicountAmount / $item->getQtyOrdered();

            /*we send only one kind of tax for each orderItem */
            $taxesDetails[0] = array('Amount'=> $item->getBaseTaxAmount(),
                                     'Code'  => Mage::getSingleton('directory/region')->load($order->getShippingAddress()->getData('region_id'))->getCode(),
                                     'Rate'  => $order->getData('store_to_order_rate'));

            $orderLine[] = array('ShipmentNumber' => $item->getOrderId(),
                                  'QuantityOrdered'=> $item->getQtyOrdered(),
                                  'TaxDetails'     => $taxesDetails,
                                  'Discount'       => $dicountAmount,
                                  'Product'        => array('LookupCode' => $item->getSku(),
                                                             'Name'       => $item->getName(),
                                                             'Price'      => $item->getBasePrice(),
                                                             'Weight'     => $item->getWeight()));
        }
        return $orderLine;
    }

    /**
     * Function to get orders comments
     *
     * @param null
     * @return unknown
     */

    public function getOrderComments(){

        $commentText = '';

        $commentsCollection = $this->getOrder()->getStatusHistoryCollection(true);
        
        foreach($commentsCollection as $comment){

            $text = $comment->getComment();
            if($text && substr($text,0,10) != 'Authorized'){
                $commentText .=  Mage::helper('core')->escapeHtml($text, array('b','br','strong','i','u'));
            }
        }

        return $commentText;
    }

    /**
     * Function to get shpping agent information
     *
     * @param null
     * @return unknown
     */

    public function getShippingAgentInfo(){

        $shippingMethod = $this->getOrder()->getData('shipping_method');
        $shippingInfo = explode('_',$shippingMethod);
        $carrier = $shippingInfo[0];

        switch($carrier){
            case 'ups';

                $carrierCode = 'UPS';
                $serviceCode = 'GND';
                break;
            case 'usps';

                $carrierCode = 'USPS';
                $serviceCode = 'USPS';
                break;

            case 'bordership':

                $carrierCode = 'BORDERSHIP';
                $serviceCode = 'BJUMP';
                break;

            default:

                $carrierCode = strtoupper($carrier);
                $serviceCode = strtoupper($shippingInfo[1]);
                break;
        }

        $agentInfo = array('carrier_code' => $carrierCode,
                            'service_code' => $serviceCode);

        return $agentInfo;
    }

    /**
     * Function to get sales orders information
     *
     * @param Integer $incrementId
     * @return unknown
     */
    
    public function getSalesOrderInfo($incrementId =''){

        if($incrementId){
            $this->_mageOrder = $this->getMageClient()->call($this->getSessionId(),
                                                             'sales_order.info',
                                                             $incrementId);
        }
        return $this->_mageOrder;
    }

    /**
     * Function to get Item Shipment Qty
     *
     * @param null
     * @return unknown
     */
    public function getItemShipmentQty(){

        $itemsQty = array();

        $mageOrder = $this->getSalesOrderInfo();
        $mageOrderItems = $mageOrder['items'];
        $bloyalOrderLines = $this->getBloyalOrder()->Lines->OrderLine;

        if(is_array($bloyalOrderLines)){
            foreach($bloyalOrderLines as $line){
                $sku = $line->Product->LookupCode;
                $shipmentQty = $line->QuantityDelivered;
                if($shipmentQty >0){
                    foreach($mageOrderItems as $item){
                        if($item['sku'] == $sku){
                            $itemsQty[$item['item_id']] = $shipmentQty;
                            break;
                        }
                    }
                }
            }
        }else {

            $sku = $bloyalOrderLines->Product->LookupCode;
            $shipmentQty = $bloyalOrderLines->QuantityDelivered;
            if($shipmentQty >0){
                foreach($mageOrderItems as $item){
                    if($item['sku'] == $sku){
                        $itemsQty[$item['item_id']] = $shipmentQty;
                        break;
                    }
                }
            }
        }

        return $itemsQty;
    }

    /**
     * Function to do Shipment
     *
     * @param null
     * @return unknown
     */
    public function doShipment(){

    $qty = $this->getItemShipmentQty();
    if(count($qty)==0) return $this;

        $mageOrder = $this->getSalesOrderInfo();
        $shipmentInfo = $this->getMageClient()->call($this->getSessionId(),
                                                    'sales_order_shipment.list',
                                                    array('filers'=>array('order_id'=>$mageOrder['order_id'])));

        if(!count($shipmentInfo)){

            $shipmentsEmails = true;
            $newShipmentId = $this->getMageClient()->call($this->getSessionId(),
                                                          'sales_order_shipment.create',
                                                          array($mageOrder['increment_id'],
                                                                 $qty,
                                                                 'Shipment created from Bloyal Data',
                                                                 $shipmentsEmails,
                                                                 false));

            $carriers = $this->getMageClient()->call($this->getSessionId(),
                                                     'sales_order_shipment.getCarriers',
                                                     $mageOrder['increment_id']);

            $bloyalOrder = $this->getBloyalOrder();
            $carrierCode = strtolower((string)$bloyalOrder->Shipments->OrderShipment->CarrierCode);

            if(isset($carriers[$carrierCode])){
                $title = $carriers[$carrierCode];
            }else{
                $title = $carrierCode;
                $carrierCode = 'custom';
            }

            $trackNumber = (isset($bloyalOrder->Shipments->OrderShipment->TrackingNumber->string))? $bloyalOrder->Shipments->OrderShipment->TrackingNumber->string : 'no info available';
            $this->getMageClient()->call($this->getSessionId(),
                                         'sales_order_shipment.addTrack',
                                         array($newShipmentId,
                                                $carrierCode,
                                                $title,
                                                $trackNumber));
        }
        return $this;
    }

    public function doInvoice(){
        $mageOrder = $this->getSalesOrderInfo();
        $invoiceInfo = $this->getMageClient()->call($this->getSessionId(),
                                                    'sales_order_invoice.list',
                                                    array('filers'=>array('order_id'=>$mageOrder['order_id'])));
        if(!count($invoiceInfo)){
            $id = $this->getMageClient()->call($this->getSessionId(),
                                               'sales_order_invoice.create',
                                                array($mageOrder['increment_id'],
                                                       $this->getItemShipmentQty(),
                                                       'Invoice created from Bloyal Data',
                                                        false,
                                                        false));
        }else{
            $id = $invoiceInfo[0]['increment_id'];
        }
        $this->setInvoiceId($id);
        return $this;
    }

    public function doCapture(){

        $capture = $this->getMageClient()->call($this->getSessionId(),
                                                'sales_order_invoice.capture',
                                                 $this->getInvoiceId());
        $message = ($capture)? 'Capture complete from Bloyal Data' : 'Could not capture from Bloyal Data, please check';
        $this->getMageClient()->call($this->getSessionId(),
                                     'sales_order_invoice.addComment',
                                     array($this->getInvoiceId(),
                                            $message,
                                            false,
                                            false));
        $this->setInvoiceId();
        return $this;
    }

    public function doLast(){

        $qty = 0;
        foreach($this->getItemShipmentQty() as $itemQtys) $qty = $qty + $itemQtys;

        $mageOrder = $this->getSalesOrderInfo();
        $shipmentDiff = (bool)(($qty%$mageOrder['total_qty_ordered']) == 0);

        $bloyalOrder = $this->getBloyalOrder();
        if(!$shipmentDiff) $this->doSaveOrder();

        try{
            $this->getApi()->CaptureOrder(array('credential' => $this->getCredentials(),
                                                'orderNumber' => $bloyalOrder->Number));
        }catch (Exception $e){
            return false;
        }
        unset($this->_bloyalOrder);

        return $this;
    }

    public function doSaveOrder() {

        $bloyalOrder = $this->getBloyalOrder();

        $purchaser = array('Address1'        =>$bloyalOrder->Customer->Address1,
                            'Address2'       =>$bloyalOrder->Customer->Address2,
                            'BirthDate'      =>$bloyalOrder->Customer->BirthDate,
                            'City'           =>$bloyalOrder->Customer->City,
                            'CompanyName'    =>$bloyalOrder->Customer->CompanyName,
                            'Country'        =>$bloyalOrder->Customer->Country,
                            'EmailAddress'   =>$bloyalOrder->Customer->EmailAddress,
                            'FaxNumber'      =>$bloyalOrder->Customer->FaxNumber,
                            'FirstName'      =>$bloyalOrder->Customer->FirstName,
                            'FirstName2'     =>$bloyalOrder->Customer->FirstName2,
                            'LastName'       =>$bloyalOrder->Customer->LastName,
                            'LastName2'      =>$bloyalOrder->Customer->LastName2,
                            'MobilePhone'    =>$bloyalOrder->Customer->MobilePhone,
                            'NickName'       =>$bloyalOrder->Customer->NickName,
                            'Phone1'         =>$bloyalOrder->Customer->Phone1,
                            'Phone2'         =>$bloyalOrder->Customer->Phone2,
                            'State'          =>$bloyalOrder->Customer->State,
                            'ZipCode'        =>$bloyalOrder->Customer->ZipCode,
                            'AccountNumber'  =>$bloyalOrder->Customer->AccountNumber,
                            'ExternalId'     =>$bloyalOrder->Customer->ExternalId,
                            'SignupStoreCode'=>$bloyalOrder->Customer->SignupStoreCode);

        $order_lines_total_price = 0;
        $order_lines_total_tax = 0;

        if(!is_array($bloyalOrder->Lines->OrderLine)){
            $orderLines = array($bloyalOrder->Lines->OrderLine);
        }else{
            $orderLines = $bloyalOrder->Lines->OrderLine;
        }

        foreach($orderLines as $orderLine){

            if($orderLine->QuantityDelivered>0){

                foreach($orderLine->TaxDetails as $tax_detail){
                        $tax_details[0] = array('Amount'=> $tax_detail->Amount,
                                                 'Code'  => $tax_detail->Code,
                                                 'Rate'  => $tax_detail->Rate);
                }

                $product_total_price     = $orderLine->Product->Price*$orderLine->QuantityDelivered;
                $product_total_tax_price = $tax_details[0]['Amount']*$orderLine->QuantityDelivered;
                $order_lines_total_price += $product_total_price;
                $order_lines_total_tax   += $product_total_tax_price;

                $order_lines[] = array( 'ExternalId'        => $orderLine->ExternalId,
                                        'Discount'          => $orderLine->Discount,
                                        'DiscountCode'      => $orderLine->DiscountCode,
                                        'DiscountReasonCode'=> $orderLine->DiscountReasonCode,
                                        'OrderDiscount'     => $orderLine->OrderDiscount,
                                        'Number'            => $orderLine->Number,
                                        'PriceSource'       => $orderLine->PriceSource,
                                        'ShipmentNumber'    => $orderLine->ShipmentNumber,
                                        'QuantityOrdered'   => $orderLine->QuantityOrdered,
                                        'QuantityDelivered' => $orderLine->QuantityDelivered,
                                        'Tax'               => $product_total_tax_price,
                                        'TaxDetails'        => $tax_details,
                                        'TotalAmount'       => $product_total_price+$product_total_tax_price,
                                        'TotalPrice'        => $product_total_price,
                                        'TotalWeight'       => $orderLine->Product->Weight*$orderLine->QuantityDelivered,
                                        'Product'           => array(   'Cost'      => $orderLine->Product->Cost,
                                                                        'LookupCode'=> $orderLine->Product->LookupCode,
                                                                        'Name'      => $orderLine->Product->Name,
                                                                        'Price'     => $orderLine->Product->Price,
                                                                        'Weight'    => $orderLine->Product->Weight));
                }
        }

        $payment[] = array( 'Amount'            => $bloyalOrder->Payments->OrderPayment->Amount,
                            'AuthorizationCode' => $bloyalOrder->Payments->OrderPayment->AuthorizationCode,
                            'Authorized'        => $bloyalOrder->Payments->OrderPayment->Authorized,
                            'Captured'          => $bloyalOrder->Payments->OrderPayment->Captured,
                            'External'          => $bloyalOrder->Payments->OrderPayment->External,
                            'Number'            => $bloyalOrder->Payments->OrderPayment->Number,
                            'TenderCode'        => $bloyalOrder->Payments->OrderPayment->TenderCode,
                            'Title'             => $bloyalOrder->Payments->OrderPayment->Title,
                            'TransactionCode'   => $bloyalOrder->Payments->OrderPayment->TransactionCode);


        $shipment[] = array('CarrierCode' => $bloyalOrder->Shipments->OrderShipment->CarrierCode,
                            'Charge'      => $bloyalOrder->Shipments->OrderShipment->Charge,
                            'LocationCode'=> $bloyalOrder->Shipments->OrderShipment->LocationCode,
                            'NetAmount'   => $bloyalOrder->Shipments->OrderShipment->NetAmount,
                            'Number'      => $bloyalOrder->Shipments->OrderShipment->Number,
                            'Recipient'   => array( 'Address1'    => $bloyalOrder->Shipments->OrderShipment->Recipient->Address1,
                                                    'Address2'    => $bloyalOrder->Shipments->OrderShipment->Recipient->Address2,
                                                    'BirthDate'   => $bloyalOrder->Shipments->OrderShipment->Recipient->BirthDate,
                                                    'City'        => $bloyalOrder->Shipments->OrderShipment->Recipient->City,
                                                    'CompanyName' => $bloyalOrder->Shipments->OrderShipment->Recipient->CompanyName,
                                                    'Country'     => $bloyalOrder->Shipments->OrderShipment->Recipient->Country,
                                                    'EmailAddress'=> $bloyalOrder->Shipments->OrderShipment->Recipient->EmailAddress,
                                                    'FaxNumber'   => $bloyalOrder->Shipments->OrderShipment->Recipient->FaxNumber,
                                                    'FirstName'   => $bloyalOrder->Shipments->OrderShipment->Recipient->FirstName,
                                                    'FirstName2'  => $bloyalOrder->Shipments->OrderShipment->Recipient->FirstName2,
                                                    'LastName'    => $bloyalOrder->Shipments->OrderShipment->Recipient->LastName,
                                                    'LastName2'   => $bloyalOrder->Shipments->OrderShipment->Recipient->LastName2,
                                                    'MobilePhone' => $bloyalOrder->Shipments->OrderShipment->Recipient->MobilePhone,
                                                    'NickName'    => $bloyalOrder->Shipments->OrderShipment->Recipient->NickName,
                                                    'Phone1'      => $bloyalOrder->Shipments->OrderShipment->Recipient->Phone1,
                                                    'Phone2'      => $bloyalOrder->Shipments->OrderShipment->Recipient->Phone2,
                                                    'State'       => $bloyalOrder->Shipments->OrderShipment->Recipient->State,
                                                    'ZipCode'     => $bloyalOrder->Shipments->OrderShipment->Recipient->ZipCode),
                            'ServiceCode'         => $bloyalOrder->Shipments->OrderShipment->ServiceCode,
                            'ShipDate'            => $bloyalOrder->Shipments->OrderShipment->ShipDate,
                            'SpecialInstructions' => $bloyalOrder->Shipments->OrderShipment->SpecialInstructions,
                            'Status'              => $bloyalOrder->Shipments->OrderShipment->Status,
                            'Tax'                 => $bloyalOrder->Shipments->OrderShipment->Tax,
                            'TaxDetails'          => array(array('Amount'=> $bloyalOrder->Shipments->OrderShipment->TaxDetails->TaxDetail->Amount,
                                                                   'Code' => $bloyalOrder->Shipments->OrderShipment->TaxDetails->TaxDetail->Code,
                                                                   'Rate' => $bloyalOrder->Shipments->OrderShipment->TaxDetails->TaxDetail->Rate)),
                            'Title'               => $bloyalOrder->Shipments->OrderShipment->Title,
                            'TotalAmount'         => $bloyalOrder->Shipments->OrderShipment->TotalAmount,
                            'Type'                => $bloyalOrder->Shipments->OrderShipment->Type);

        $order = array('Title'      =>$bloyalOrder->Title,
                        'Customer'  =>$purchaser,
                        'ExternalId'=>$bloyalOrder->ExternalId,
                        'Lines'     =>$order_lines,
                        'Number'    =>$bloyalOrder->Number,
                        'Shipments' =>$shipment);

        try{
            $this->getApi()->SaveOrder(array('credential' => $this->getCredentials(),
                                              'deviceKey'  => $this->_device_key,
                                              'order'      => $order));
        }catch(Exception $e){
            Mage::log($e->faultstring .' '. $e->faultcode . ' mageId #'.$bloyalOrder->ExternalId,3,'bloyalIssues.log');
        }

        return $this;
    }

    public function needReviewOrder(){

        return (bool)($this->getData('submit_order_retries') == '3' || $this->getData('add_payment_retries') == '3' || $this->getData('approve_order_retries') == '3');
    }

}
