<?php
class Reachly_HandleEvent_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getCartToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        return $cookie->get('cart');
    }

    public function getCheckoutToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        return $cookie->get('checkout');
    }

    public function getOrderToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        return $cookie->get('order');
    }

    public function deleteCheckoutToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        $cookie->set('checkout', '', -300, '/');
    }

    public function deleteOrderToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        $cookie->set('order', '', -300, '/');
    }
}
