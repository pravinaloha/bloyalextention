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
 * @package     Model_Observer
 * @copyright   Copyright (c) 2012 bLoyal Inc. (http://www.bloyal.com)
 * @license     http://www.bloyal.com
 */

/**
 * Media library Image model
 *
 * @category   Bloyal
 * @package    Bloyal_LoyaltyIntegrator
 */
class Bloyal_LoyaltyIntegrator_Model_Observer {

    /**
     * Function to unset bloayl message session
     * @param Varien_Event_Observer $observer
     */
    public function unsetBloaylSession(Varien_Event_Observer $observer) {
        $event = $observer->getEvent();
        Mage::getSingleton('core/session')->setBloyalCouponCode('');
        Mage::log($event->getName(), Zend_Log::DEBUG, 'loyalty.log');
    }
    
    

    /**
     * Function to apply bLoyal Discount code.
     * @param Varien_Event_Observer $observer
     */
    public function applyCouponDiscount(Varien_Event_Observer $observer) {

        $quote = $observer->getEvent()->getQuote();
        $quoteid = $quote->getId();
        $couponCode = $quote->getCouponCode();

        $arrData = Mage::app()->getRequest()->getPost();

        $updatedAt = date('U', strtotime($observer->getQuote()->getUpdatedAt()));
        $now = time();
        if (($updatedAt + 3) > $now) {

            if (Mage::getSingleton('core/session')->getBloyalCouponCode() && !$couponCode) {
                Mage::getSingleton('checkout/session')->getMessages(true);
                if ($strMessage = Mage::getSingleton('checkout/session')->getBloyalMsg()) {
                    Mage::getSingleton('checkout/session')->getMessages(true);
                    if ($strMessage === 'Success')
                        Mage::getSingleton('checkout/session')->addSuccess('Coupon code "BL20" was applied.');
                    elseif ($strMessage === 'Fail') {
                        Mage::getSingleton('checkout/session')->addError('Coupon code "BL20" is Invalid.');
                    }
                }
            }

            Mage::log('Success 2', Zend_Log::DEBUG, 'loyalty.log');
        }

        if (!$couponCode) {
            if (isset($arrData['coupon_code']) && $arrData['coupon_code'] && !$arrData['remove']) {
                // Success
                Mage::getSingleton('core/session')->setBloyalCouponCode($arrData['coupon_code']);
            }

            if (isset($arrData['remove'])) {
                //Error 
                Mage::getSingleton('core/session')->setBloyalCouponCode('');
            }
        } else {
            Mage::getSingleton('core/session')->setBloyalCouponCode('');
        }

        if (!$couponCode && Mage::getSingleton('core/session')->getBloyalCouponCode()) {
            if (Mage::getSingleton('core/session')->getBloyalCouponCode() === 'SALE') {
                $discountAmount = 20;
                Mage::getSingleton('checkout/session')->setBloyalMsg('Success');
            } else {
                $discountAmount = 0;
                Mage::getSingleton('checkout/session')->setBloyalMsg('Fail');
            }

            if ($quoteid) {
                if ($discountAmount > 0) {

                    $total = $quote->getBaseSubtotal();
                    $quote->setSubtotal(0);
                    $quote->setBaseSubtotal(0);

                    $quote->setSubtotalWithDiscount(0);
                    $quote->setBaseSubtotalWithDiscount(0);

                    $quote->setGrandTotal(0);
                    $quote->setBaseGrandTotal(0);


                    $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');
                    foreach ($quote->getAllAddresses() as $address) {

                        $address->setSubtotal(0);
                        $address->setBaseSubtotal(0);

                        $address->setGrandTotal(0);
                        $address->setBaseGrandTotal(0);

                        $address->collectTotals();

                        $quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
                        $quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

                        $quote->setSubtotalWithDiscount(
                                (float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount()
                        );
                        $quote->setBaseSubtotalWithDiscount(
                                (float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount()
                        );

                        $quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
                        $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());

                        $quote->save();

                        $quote->setGrandTotal($quote->getBaseSubtotal() - $discountAmount)
                                ->setBaseGrandTotal($quote->getBaseSubtotal() - $discountAmount)
                                ->setSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)
                                ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)
                                ->save();



                        if ($address->getAddressType() == $canAddItems) {
                            //echo $address->setDiscountAmount; exit;
                            $address->setSubtotalWithDiscount((float) $address->getSubtotalWithDiscount() - $discountAmount);
                            $address->setGrandTotal((float) $address->getGrandTotal() - $discountAmount);
                            $address->setBaseSubtotalWithDiscount((float) $address->getBaseSubtotalWithDiscount() - $discountAmount);
                            $address->setBaseGrandTotal((float) $address->getBaseGrandTotal() - $discountAmount);
                            if ($address->getDiscountDescription()) {
                                $address->setDiscountAmount(-($address->getDiscountAmount() - $discountAmount));
                                $address->setDiscountDescription($address->getDiscountDescription() . ', Custom Discount');
                                $address->setBaseDiscountAmount(-($address->getBaseDiscountAmount() - $discountAmount));
                            } else {
                                $address->setDiscountAmount(-($discountAmount));
                                $address->setDiscountDescription('Custom Discount');
                                $address->setBaseDiscountAmount(-($discountAmount));
                            }
                            $address->save();
                        }//end: if
                    } //end: foreach
                    //echo $quote->getGrandTotal();

                    foreach ($quote->getAllItems() as $item) {
                        //We apply discount amount based on the ratio between the GrandTotal and the RowTotal
                        $rat = $item->getPriceInclTax() / $total;
                        $ratdisc = $discountAmount * $rat;
                        $item->setDiscountAmount(($item->getDiscountAmount() + $ratdisc) * $item->getQty());
                        $item->setBaseDiscountAmount(($item->getBaseDiscountAmount() + $ratdisc) * $item->getQty())->save();
                    }
                }
            }
        }
    }

}
