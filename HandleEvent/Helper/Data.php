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

    public function getHandle($title)
    {
        return str_replace(' ', '.', strtolower($title));
    }

    public function postData($json, $endpoint)
    {
        $apiURL = 'http://127.0.0.1:8042';

        $appID     = Mage::getStoreConfig('reachly_handleevent_options/section_one/field_app_id');
        $secretKey = Mage::getStoreConfig('reachly_handleevent_options/section_one/field_secret_key');

        $auth = $appID . ":" . base64_encode(hash_hmac('sha256', $json, $secretKey));

        $url = $apiURL . '/' . $endpoint;
        $ch  = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'Content-Length: ' . strlen($json),
            'Authorization: ' . $auth
        ));

        curl_exec($ch);
        curl_close($ch);
    }
}
