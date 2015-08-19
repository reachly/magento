<?php
class Reachly_HandleEvent_Model_Observer
{
    public function processCartEvent()
    {
      $helper = Mage::helper('reachly_handleevent');
      $helper->setCartToken();
    }

    public function processCheckoutEvent()
    {
        $helper = Mage::helper('reachly_handleevent');

        $checkoutArr = $helper->setCheckoutToken();

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

        $dataArr = array_merge($dataArr, $helper->getCartData());

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $helper->postData($json, 'checkout');
    }

    public function processOrderEvent()
    {
        $helper = Mage::helper('reachly_handleevent');

        $orderToken = $helper->setOrderToken();

        $whArr   = array();
        $dataArr = array();

        $whArr["topic"]      = "orders/create";
        $whArr["updated_at"] = $helper->getTimestamp();
        $whArr["app_id"]     = $helper->getStoreAppID();

        $dataArr["cart_token"]     = $helper->getCartToken();
        $dataArr["checkout_token"] = $helper->getCheckoutToken();
        $dataArr["token"]          = $orderToken;

        $dataArr = array_merge($dataArr, $helper->getCartData());

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $helper->postData($json, 'order');
    }

    public function productSaveEvent($observer)
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
            //TODO: get valid ID on create action
            $productID      = Mage::getModel('catalog/product')->getCollection()->getLastItem()->getId() + 1;
        } else {
            $timeArr = $helper->getProductTimestamps($product);

            $createdAt = $timeArr[0];
            $updatedAt = $timeArr[1];

            $whArr["topic"] = "products/update";
            $productID      = $product->getId();
        }
        $whArr["updated_at"] = $currentTime;
        $whArr["app_id"]     = $helper->getStoreAppID();

        $dataArr["id"]         = $productID;
        $productName           = $product->getName();
        $dataArr["title"]      = $productName;
        $dataArr["handle"]     = $helper->getHandle($productName);
        $dataArr["created_at"] = $createdAt;
        $dataArr["updated_at"] = $updatedAt;

        //TODO: add variants

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $helper->postData($json, 'product');
    }

    public function productDeleteEvent($observer)
    {
        //TODO: send products/delete
    }
}
