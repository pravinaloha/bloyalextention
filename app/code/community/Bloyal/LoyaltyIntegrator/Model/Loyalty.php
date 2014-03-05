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

class Bloyal_OrderIntegrator_Model_Loyalty extends Bloyal_Master_Model_Abstract {
    
    
    public function applyBloyalCoupon()
    {
        $orderResult = $this->getApi()->SubmitOrder();
    }
    
}