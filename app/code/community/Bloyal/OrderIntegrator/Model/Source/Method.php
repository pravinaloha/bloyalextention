<?php 

class Bloyal_OrderIntegrator_Model_Source_Method{
	
    public function toOptionArray(){
    	
        return array(
            array(
                'value' => Bloyal_OrderIntegrator_Model_Order::ACTION_AUTHORIZE,
                'label' => Mage::helper('bloyalOrder')->__('Magento orders are authorized only')
            ),
//            array(
//                'value' => Bloyal_OrderIntegrator_Model_Order::ACTION_AUTHORIZE_CAPTURE,
//                'label' => Mage::helper('bloyalOrder')->__('Magento orders are authorized and captured')
//            ),
        );
    }
}

?>