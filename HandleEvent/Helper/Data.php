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

    public function getTimestamp()
    {
        $offset = date_default_timezone_get();
        $dt     = new DateTime();
        return $dt->format('Y-m-d') . 'T' . $dt->format('H:i:s') . sprintf("%s%02d:%02d", ($offset >= 0) ? '+' : '-', abs($offset / 3600), abs($offset % 3600) / 60);
        ;
    }

    public function getStoreAppID()
    {
        return "magento." . parse_url(Mage::getBaseUrl(), PHP_URL_HOST);
    }
}
