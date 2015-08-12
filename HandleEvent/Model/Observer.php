<?php
class Reachly_HandleEvent_Model_Observer
{
    public function setCartToken()
    {
        $orderSet = isset($_COOKIE['order']);
        if ($orderSet) {
            Mage::helper('reachly_handleevent')->deleteCheckoutToken();
            Mage::helper('reachly_handleevent')->deleteOrderToken();
        }

        if (!isset($_COOKIE['cart']) || $orderSet) {
            $cookie    = Mage::getSingleton('core/cookie');
            $cartToken = substr(md5(rand()), 0, 32);
            $cookie->set('cart', $cartToken, 60 * 60 * 24 * 365 * 2, '/');
        }
    }

    protected function setCheckoutToken()
    {
        if (!isset($_COOKIE['checkout'])) {
            $cookie        = Mage::getSingleton('core/cookie');
            $checkoutToken = substr(md5(rand()), 0, 32);
            $cookie->set('checkout', $checkoutToken, 60 * 60 * 24 * 365 * 2, '/');
            $respArr = array(
                true,
                $checkoutToken
            );
        } else {
            $respArr = array(
                false,
                Mage::helper('reachly_handleevent')->getCheckoutToken()
            );
        }
        return $respArr;
    }

    protected function setOrderToken()
    {
        if (!isset($_COOKIE['order'])) {
            $cookie     = Mage::getSingleton('core/cookie');
            $orderToken = substr(md5(rand()), 0, 32);
            $cookie->set('order', $orderToken, 60 * 60 * 24 * 365 * 2, '/');
            $resp = $orderToken;
        } else {
            $resp = Mage::helper('reachly_handleevent')->getOrderToken();
        }
        return $resp;
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

    protected function getItems()
    {
        $items = array();

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
                $itemWeight         = $product->getWeight();
                $totaPrice          = $totaPrice + $itemPrice;
                $item["price"]      = $itemPrice;
                $item["weight"]     = $itemWeight;
                $item["product_id"] = $product->getId();
                $item["title"]      = $product->getName();

                $totaWeight = $totaWeight + $itemWeight;

                array_push($items, $item);

                $qty--;
            }
        }

        return array(
            $items,
            $totaPrice,
            $totaWeight
        );
    }

    protected function getCartData()
    {
        $dataArr = array();

        $itemsData               = $this->getItems();
        $dataArr["line_items"]   = $itemsData[0];
        $dataArr["total_price"]  = $itemsData[1];
        $dataArr["total_weight"] = $itemsData[2];
        $dataArr["item_count"]   = sizeof($itemsData[0]);
        $dataArr["currency"]     = Mage::app()->getStore()->getCurrentCurrencyCode();

        return $dataArr;
    }

    protected function getTimestamp()
    {
        $dt = new DateTime();
        return $dt->format('Y-m-d') . 'T' . $dt->format('H:i:s') . $this->timezoneOffsetString(date_default_timezone_get());
    }

    protected function getStoreAppID()
    {
        return "magento." . parse_url(Mage::getBaseUrl(), PHP_URL_HOST);
    }

    public function processCheckoutEvent()
    {
        $checkoutArr = $this->setCheckoutToken();

        $whArr   = array();
        $dataArr = array();

        if ($checkoutArr[0]) {
            $whArr["topic"] = "checkouts/create";
        } else {
            $whArr["topic"] = "checkouts/update";
        }
        $whArr["updated_at"] = $this->getTimestamp();
        $whArr["app_id"]     = $this->getStoreAppID();

        $dataArr["cart_token"] = Mage::helper('reachly_handleevent')->getCartToken();
        $dataArr["token"]      = $checkoutArr[1];

        $dataArr = array_merge($dataArr, $this->getCartData());

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $this->postData($json, 'checkout');
    }

    public function processOrderEvent()
    {
        $orderToken = $this->setOrderToken();

        $whArr   = array();
        $dataArr = array();

        $whArr["topic"]      = "orders/create";
        $whArr["updated_at"] = $this->getTimestamp();
        $whArr["app_id"]     = $this->getStoreAppID();

        $dataArr["cart_token"]     = Mage::helper('reachly_handleevent')->getCartToken();
        $dataArr["checkout_token"] = Mage::helper('reachly_handleevent')->getCheckoutToken();
        $dataArr["token"]          = $orderToken;

        $dataArr = array_merge($dataArr, $this->getCartData());

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $this->postData($json, 'order');
    }
}
