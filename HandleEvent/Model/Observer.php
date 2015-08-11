<?php
class Reachly_HandleEvent_Model_Observer
{
    protected function getCartToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        return $cookie->get('cart');
    }

    protected function getCheckoutToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        return $cookie->get('checkout');
    }

    public function setCartToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        if (!isset($_COOKIE['cart'])) {
            $cartToken = substr(md5(rand()), 0, 32);
            $cookie->set('cart', $cartToken, time() + 60 * 60 * 24 * 365 * 2, '/');
        }
    }

    public function setCheckoutToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        if (!isset($_COOKIE['checkout'])) {
            $checkoutToken = substr(md5(rand()), 0, 32);
            $cookie->set('checkout', $checkoutToken, time() + 60 * 60 * 24 * 365 * 2, '/');
            $respArr = array(
                true,
                $checkoutToken
            );
        } else {
            $respArr = array(
                false,
                $this->getCheckoutToken()
            );
        }
        return $respArr;
    }

    protected function timezoneOffsetString($offset)
    {
        return sprintf("%s%02d:%02d", ($offset >= 0) ? '+' : '-', abs($offset / 3600), abs($offset % 3600) / 60);
    }

    protected function postData($json, $endpoint)
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

    public function processCheckoutEvent($observer)
    {
        $checkoutArr = $this->setCheckoutToken();

        $whArr   = array();
        $dataArr = array();
        $items   = array();

        $cart     = Mage::getModel('checkout/cart')->getQuote();
        $allItems = $cart->getAllItems();

        $totaPrice  = 0;
        $totaWeight = 0;

        foreach ($allItems as $productItem) {
            $qty = $productItem->getQty();
            while ($qty > 0) {
                $product            = $productItem->getProduct();
                $item               = array();
                $itemPrice          = $product->getPrice();
                $totaPrice          = $totaPrice + $itemPrice;
                $item["price"]      = $itemPrice;
                $item["product_id"] = $product->getId();
                $item["title"]      = $product->getName();

                $totaWeight = $totaWeight + $product->getWeight();

                array_push($items, $item);

                $qty--;
            }
        }

        $dataArr["line_items"]   = $items;
        $dataArr["cart_token"]   = $this->getCartToken();
        $dataArr["token"]        = $checkoutArr[1];
        $dataArr["total_price"]  = $totaPrice;
        $dataArr["total_weight"] = $totaWeight;
        $dataArr["item_count"]   = sizeof($allItems);
        $dataArr["currency"]     = Mage::app()->getStore()->getCurrentCurrencyCode();

        $whArr["data"] = $dataArr;

        if ($checkoutArr[0]) {
            $whArr["topic"] = "checkouts/create";
        } else {
            $whArr["topic"] = "checkouts/update";
        }

        $dt                  = new DateTime();
        $whArr["updated_at"] = $dt->format('Y-m-d') . 'T' . $dt->format('H:i:s') . $this->timezoneOffsetString(date_default_timezone_get());
        $whArr["app_id"]     = "magento." . parse_url(Mage::getBaseUrl(), PHP_URL_HOST);

        $json = json_encode($whArr);

        $this->postData($json, 'checkout');
    }
}
