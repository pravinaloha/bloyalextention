<?php

class Bloyal_Gift_Sales_Model_Order extends Mage_Sales_Model_Order {

    public function getBloyalGiftNumber() {
        $order = $this->getOrder();
        $bloyalGiftNumber = $order->getGiftCardNumber();
        return $bloyalGiftNumber;
    }

}