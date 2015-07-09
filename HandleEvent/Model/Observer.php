<?php
class Reachly_HandleEvent_Model_Observer
{
    public function setCartToken($observer)
    {
        $cookie = Mage::getSingleton('core/cookie');
        if (!isset($_COOKIE['cart'])) {
            $token = substr(md5(rand()), 0, 32);
            $cookie->set('cart', $token, time() + 60 * 60 * 24 * 365 * 2, '/');
        }
    }

    protected function getCartToken()
    {
        $cookie = Mage::getSingleton('core/cookie');
        return $cookie->get('cart');
    }

    public function processCartEvent($observer)
    {
        $cartArr = array();
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

        $cartArr["items"]             = $items;
        $cartArr["token"]             = $this->getCartToken();
        $cartArr["total_price"]       = $totaPrice;
        $cartArr["total_weight"]      = $totaWeight;
        $cartArr["item_count"]        = sizeof($allItems);
        $cartArr["requires_shipping"] = false;

        $json = json_encode($cartArr);

        $cartFile = fopen(Mage::getBaseDir()."/cart.json", "w");
        fwrite($cartFile, $json);
    }
}
