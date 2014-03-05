<?php

require_once 'Mage/Checkout/controllers/CartController.php';

class Bloyal_LoyaltyIntegrator_IndexController extends Mage_Checkout_CartController {

    public function customcouponPostAction() {

        Mage::getSingleton('core/session')->setBloyalCoupon('Custom Discount__20');
        
        $quote = $this->_getQuote();
        $quoteid = $quote->getId();
        $discountAmount = 20;

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
Mage::log('BEFORE Observer', Zend_Log::DEBUG, 'loyalty.log');
                    $quote->save();
Mage::log('BEFORE Observer 1', Zend_Log::DEBUG, 'loyalty.log');
                    $quote->setGrandTotal($quote->getBaseSubtotal() - $discountAmount)
                            ->setBaseGrandTotal($quote->getBaseSubtotal() - $discountAmount)
                            ->setSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)
                            ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)
                            ->save();
Mage::log('BEFORE Observer 2', Zend_Log::DEBUG, 'loyalty.log');


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



        /**
         * No reason continue with empty shopping cart
         */
        /* 	$response = array();
          $response['status'] = 'ERROR';
          if (!$this->_getCart()->getQuote()->getItemsCount()) {
          //$this->_goBack();
          return;
          }
          //echo $this->getRequest()->getParam('remove'); die;
          $couponCode = (string) $this->getRequest()->getParam('coupon_code');

          if ($this->getRequest()->getParam('remove') == 1) {
          $couponCode = '';
          }
          $oldCouponCode = $this->_getQuote()->getCouponCode();

          if (!strlen($couponCode) && !strlen($oldCouponCode)) {
          //$this->_goBack();
          return;
          }

          try {
          $this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
          $this->_getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
          ->collectTotals()
          ->save();

          if (strlen($couponCode)) {
          if ($couponCode == $this->_getQuote()->getCouponCode()) {
          //$this->_getSession()->addSuccess(
          //		$this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode))
          //);
          $response['msg'] =$this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode));
          $response['status'] = 'SUCCESS';

          }
          else {
          //$this->_getSession()->addError(
          //		$this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode))
          //);
          $response['msg'] = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
          $response['status'] = 'ERROR';
          }
          } else {
          $response['status'] = 'SUCCESS';
          //$this->_getSession()->addSuccess($this->__('Coupon code was canceled.'));
          $response['msg'] = $this->__('Coupon code was canceled.');
          }

          } catch (Mage_Core_Exception $e) {
          //$this->_getSession()->addError($e->getMessage());
          } catch (Exception $e) {
          //$this->_getSession()->addError($this->__('Cannot apply the coupon code.'));
          //Mage::logException($e);
          $response['msg'] = $this->__('Cannot apply the coupon code.');
          }
         */


        $this->loadLayout(false);
        $review = $this->getLayout()->getBlock('roots')->toHtml();
        $response['review'] = $review;
        //$this->_goBack();
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

}
