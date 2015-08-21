<?php
class Reachly_HandleEvent_Model_Observer
{
    function __construct()
    {
        $this->helper = Mage::helper('reachly_handleevent');
    }

    //processCartEvent is being triggered when customer's cart is updated
    public function processCartEvent()
    {
        $this->helper->setCartToken();
    }

    //processCheckoutEvent is being triggered on checkout event
    public function processCheckoutEvent()
    {
        $checkoutArr = $this->helper->setCheckoutToken();

        $whArr   = array();
        $dataArr = array();

        if ($checkoutArr[0]) {
            $whArr["topic"] = "checkouts/create";
        } else {
            $whArr["topic"] = "checkouts/update";
        }
        $whArr["updated_at"] = $this->helper->getTimestamp();
        $whArr["app_id"]     = $this->helper->getStoreAppID();

        $dataArr["cart_token"] = $this->helper->getCartToken();
        $dataArr["token"]      = $checkoutArr[1];

        $dataArr = array_merge($dataArr, $this->helper->getCartData());

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $this->helper->postData($json, 'checkout');
    }

    //processCheckoutEvent is being triggered on order event
    public function processOrderEvent()
    {
        $orderToken = $this->helper->setOrderToken();

        $whArr   = array();
        $dataArr = array();

        $whArr["topic"]      = "orders/create";
        $whArr["updated_at"] = $this->helper->getTimestamp();
        $whArr["app_id"]     = $this->helper->getStoreAppID();

        $dataArr["cart_token"]     = $this->helper->getCartToken();
        $dataArr["checkout_token"] = $this->helper->getCheckoutToken();
        $dataArr["token"]          = $orderToken;

        $dataArr = array_merge($dataArr, $this->helper->getCartData());

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $this->helper->postData($json, 'order');
    }

    //processCheckoutEvent is being triggered on product save event
    public function productSaveEvent($observer)
    {
        $product = $observer->getEvent()->getProduct();

        $whArr   = array();
        $dataArr = array();

        $currentTime = $this->helper->getTimestamp();

        $createdAt = $product->getCreatedAt();
        $updatedAt = $product->getUpdatedAt();

        if (empty($createdAt)) {
            $createdAt      = $currentTime;
            $updatedAt      = $currentTime;
            $whArr["topic"] = "products/create";
            //TODO: get valid ID on create action
            $productID      = Mage::getModel('catalog/product')->getCollection()->getLastItem()->getId() + 1;
        } else {
            $timeArr = $this->helper->getProductTimestamps($product);

            $createdAt = $timeArr[0];
            $updatedAt = $timeArr[1];

            $whArr["topic"] = "products/update";
            $productID      = $product->getId();
        }
        $whArr["updated_at"] = $currentTime;
        $whArr["app_id"]     = $this->helper->getStoreAppID();

        $dataArr["id"]         = $productID;
        $productName           = $product->getName();
        $dataArr["title"]      = $productName;
        $dataArr["handle"]     = $this->helper->getHandle($productName);
        $dataArr["created_at"] = $createdAt;
        $dataArr["updated_at"] = $updatedAt;

        //TODO: add variants

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $this->helper->postData($json, 'product');
    }

    //productDeleteEvent is being triggered on product delete event
    public function productDeleteEvent($observer)
    {
        $product = $observer->getEvent()->getProduct();

        $whArr   = array();
        $dataArr = array();

        $whArr["topic"]      = "products/delete";
        $whArr["updated_at"] = $this->helper->getTimestamp();
        $whArr["app_id"]     = $this->helper->getStoreAppID();

        $dataArr["id"]    = $product->getId();
        $dataArr["title"] = $product->getName();

        $whArr["data"] = $dataArr;

        $json = json_encode($whArr);

        $this->helper->postData($json, 'product');

        //TODO: handle multiple items
    }
}
