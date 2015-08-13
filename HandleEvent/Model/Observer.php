<?php
class Reachly_HandleEvent_Model_Observer
{
    public function setCartToken()
    {
        $orderSet = isset($_COOKIE['order']);
        if ($orderSet) {
            $helper = Mage::helper('reachly_handleevent');
            $helper->deleteCheckoutToken();
            $helper->deleteOrderToken();
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

    public function processCheckoutEvent()
    {
        $helper = Mage::helper('reachly_handleevent');

        $checkoutArr = $this->setCheckoutToken();

        $whArr   = array();
        $dataArr = array();

        if ($checkoutArr[0]) {
            $whArr["topic"] = "checkouts/create";
        } else {
            $whArr["topic"] = "checkouts/update";
        }
        $whArr["updated_at"] = $helper->getTimestamp();
        $whArr["app_id"]     = $helper->getStoreAppID();

        $dataArr["cart_token"] = $helper->getCartToken();
        $dataArr["token"]      = $checkoutArr[1];

        $dataArr = array_merge($dataArr, $this->getCartData());

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $helper->postData($json, 'checkout');
    }

    public function processOrderEvent()
    {
        $helper = Mage::helper('reachly_handleevent');

        $orderToken = $this->setOrderToken();

        $whArr   = array();
        $dataArr = array();

        $whArr["topic"]      = "orders/create";
        $whArr["updated_at"] = $helper->getTimestamp();
        $whArr["app_id"]     = $helper->getStoreAppID();

        $dataArr["cart_token"]     = $helper->getCartToken();
        $dataArr["checkout_token"] = $helper->getCheckoutToken();
        $dataArr["token"]          = $orderToken;

        $dataArr = array_merge($dataArr, $this->getCartData());

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $helper->postData($json, 'order');
    }

    public function productEvent($observer)
    {
        $helper  = Mage::helper('reachly_handleevent');
        $product = $observer->getEvent()->getProduct();

        $whArr   = array();
        $dataArr = array();

        $currentTime = $helper->getTimestamp();

        $createdAt = $product->getCreatedAt();
        $updatedAt = $product->getUpdatedAt();

        if (empty($createdAt)) {
            $createdAt      = $currentTime;
            $updatedAt      = $currentTime;
            $whArr["topic"] = "products/create";
        } else {
            $t              = new DateTime('now', new DateTimeZone(Mage::getStoreConfig('general/locale/timezone')));
            $offset         = $t->format('P');
            $mageDate       = Mage::getModel('core/date');
            $updatedAt      = $mageDate->date("Y-m-d", $updatedAt) . "T" . $mageDate->date("H:i:s", $updatedAt) . $offset;
            $createdAt      = $mageDate->date("Y-m-d", $createdAt) . "T" . $mageDate->date("H:i:s", $createdAt) . $offset;
            $whArr["topic"] = "products/update";
        }
        $whArr["updated_at"] = $currentTime;
        $whArr["app_id"]     = $helper->getStoreAppID();

        $dataArr["id"]         = $product->getId();
        $productName           = $product->getName();
        $dataArr["title"]      = $productName;
        $dataArr["handle"]     = $helper->getHandle($productName);
        $dataArr["created_at"] = $createdAt;
        $dataArr["updated_at"] = $updatedAt;

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $helper->postData($json, 'product');
    }
}
