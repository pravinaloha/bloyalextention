<?php

class Bloyal_Pay_Block_Form_Pay extends Mage_Payment_Block_Form {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('bloyal/pay/form/pay.phtml');
    }

}