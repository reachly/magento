<?php
class Reachly_HandleEvent_Model_Observer
{
    public function setCartToken($observer)
    {
        $cookie = Mage::getSingleton('core/cookie');
        if (!isset($_COOKIE['cart'])) {
            $cartToken = substr(md5(rand()), 0, 32);
            $checkoutToken = substr(md5(rand()), 0, 32);

            $cookie->set('cart', $cartToken, time() + 60 * 60 * 24 * 365 * 2, '/');
            $cookie->set('checkout', $checkoutToken, time() + 60 * 60 * 24 * 365 * 2, '/');
        }
    }

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

    protected function timezone_offset_string($offset)
    {
            return sprintf( "%s%02d:%02d", ( $offset >= 0 ) ? '+' : '-', abs( $offset / 3600 ), abs( $offset % 3600 ) / 60 );
    }

    public function processCheckoutEvent($observer)
    {
        //Mage::log($this->getCartToken()."/".$this->getCheckoutToken());

        $whArr = array();
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

        $dataArr["line_items"]             = $items;
        $dataArr["cart_token"]             = $this->getCartToken();
        $dataArr["token"]             = $this->getCheckoutToken();
        $dataArr["total_price"]       = $totaPrice;
        $dataArr["total_weight"]      = $totaWeight;
        $dataArr["item_count"]        = sizeof($allItems);
        $dataArr["currency"] = Mage::app()->getStore()->getCurrentCurrencyCode();

        $whArr["data"] = $dataArr;
        $whArr["topic"] = "checkouts/create";

        $dt = new DateTime();
        $formattedTime = $dt->format('Y-m-d').'T'.$dt->format('H:i:s').$this->timezone_offset_string(date_default_timezone_get());
        $whArr["created_at"] = $formattedTime;
        $whArr["updated_at"] = $formattedTime;

        $json = json_encode($whArr);

        $appID = "7a47d23ed6ae5fa5bd8697678d3f8b32632f8916";
        $secretKey = "8e80f1a5b494b5150cb513fd332ba752cc6c481ae34ddcb3cf08e0b2dea256ba";

        $auth = $appID.":".base64_encode(hash_hmac('sha256', $json, $secretKey));


        $url = 'http://127.0.0.1:8042/checkout/';
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'Content-Length: '.strlen($json), 'Authorization: '.$auth));

        $json_response = curl_exec($ch);
        //$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        /*if ( $status != 201 ) {
          Mage::log("Error: call to URL failed with status $status, response $json_response, curl_error " . curl_error($ch) . ", curl_errno " . curl_errno($ch));
        }*/

        curl_close($ch);
    }
}
