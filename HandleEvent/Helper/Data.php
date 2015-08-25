<?php
class Reachly_HandleEvent_Helper_Data extends Mage_Core_Helper_Abstract
{
    function __construct()
    {
        $this->cookie = Mage::getSingleton('core/cookie');
    }

    //cookieIsSet returns boolean representing whether cookie is set or not
    public function cookieIsSet($name)
    {
        $value = $this->cookie->get($name);
        if ($value == "") {
          return false;
        } else {
          return true;
        }
    }

    //setCartToken generates and sets new cart token if none exists or order token is present
    public function setCartToken()
    {
        $orderSet = $this->cookieIsSet('order');
        if ($orderSet) {
            $this->deleteCheckoutToken();
            $this->deleteOrderToken();
        }

        if (!$this->cookieIsSet('cart') || $orderSet) {
            $cartToken = substr(md5(rand()), 0, 32);
            $this->cookie->set('cart', $cartToken, 60 * 60 * 24 * 365 * 2, '/');
        }
    }

    //setCheckoutToken generates and sets new checkout token if none exists and returns existing one otherwise
    public function setCheckoutToken()
    {
        if (!$this->cookieIsSet('checkout')) {
            $checkoutToken = substr(md5(rand()), 0, 32);
            $this->cookie->set('checkout', $checkoutToken, 60 * 60 * 24 * 365 * 2, '/');
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

    //setOrderToken generates and sets new order token if none exists and returns existing one otherwise
    public function setOrderToken()
    {
        if (!$this->cookieIsSet('order')) {
            $orderToken = substr(md5(rand()), 0, 32);
            $this->cookie->set('order', $orderToken, 60 * 60 * 24 * 365 * 2, '/');
            $resp = $orderToken;
        } else {
            $resp = $this->getOrderToken();
        }
        return $resp;
    }

    //getCartToken returns existing cart token
    public function getCartToken()
    {
        return $this->cookie->get('cart');
    }

    //getCheckoutToken returns existing checkout token
    public function getCheckoutToken()
    {
        return $this->cookie->get('checkout');
    }

    //getOrderToken returns existing order token
    public function getOrderToken()
    {
        return $this->cookie->get('order');
    }

    //deleteCheckoutToken deletes checkout token cookie
    public function deleteCheckoutToken()
    {
        $this->cookie->set('checkout', '', -300, '/');
    }

    //deleteOrderToken deletes order token cookie
    public function deleteOrderToken()
    {
        $this->cookie->set('order', '', -300, '/');
    }

    //getTimestamp returns current time in ISO-8601 format
    public function getTimestamp()
    {
        $offset = date_default_timezone_get();
        $dt     = new DateTime();
        $formattedOffset = sprintf("%s%02d:%02d", ($offset >= 0) ? '+' : '-', abs($offset / 3600), abs($offset % 3600) / 60);
        return $dt->format('Y-m-d') . 'T' . $dt->format('H:i:s') . $formattedOffset;
    }

    //getStoreAppID returns store's host in format 'magento.store-hostname'
    public function getStoreAppID()
    {
        return "magento." . parse_url(Mage::getBaseUrl(), PHP_URL_HOST);
    }

    //getHandle generates handle string from product title
    public function getHandle($title)
    {
        //TODO: handle multiple spaces
        return str_replace(' ', '.', strtolower($title));
    }

    //getProductTimestamps returns product's creation and modification time in ISO-8601 format
    public function getProductTimestamps($product)
    {
        $t        = new DateTime('now', new DateTimeZone(Mage::getStoreConfig('general/locale/timezone')));
        $offset   = $t->format('P');
        $mageDate = Mage::getModel('core/date');

        $created = $product->getCreatedAt();
        $updated = $product->getUpdatedAt();

        $createdAt = $mageDate->date("Y-m-d", $created) . "T" . $mageDate->date("H:i:s", $created) . $offset;
        $updatedAt = $mageDate->date("Y-m-d", $updated) . "T" . $mageDate->date("H:i:s", $updated) . $offset;

        return array(
            $createdAt,
            $updatedAt
        );
    }

    //getProductTags returns an array of tags for product specified
    public function getProductTags($product)
    {
        $tagsArr = array();

        $tagsModel = Mage::getModel('tag/tag');
        $tags      = $tagsModel->getResourceCollection()->addPopularity()->addStatusFilter($tagsModel->getApprovedStatus())
                  ->addProductFilter($product->getId())->setFlag('relation', true)->addStoreFilter(
                  Mage::app()->getStore()->getId()
        )->setActiveFilter()->load()->getItems();
        foreach ($tags as $tag) {
            array_push($tagsArr, $tag->getName());
        }

        return $tagsArr;
    }

    //getProductCustomOptions returns product's custom options
    public function getProductCustomOptions($product)
    {
        $optionsArr = array();

        $counter = 1;

        $options = Mage::getModel('catalog/product_option')->getProductOptionCollection($product);
        foreach ($options as $option) {
            $optArr             = array();
            $optArr["name"]     = $option->getDefaultTitle();
            $optArr["position"] = $counter;
            $counter++;
            array_push($optionsArr, $optArr);
        }

        return $optionsArr;
    }

    //getItems returns list of cart items
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
                $itemPrice          = (float) $product->getPrice();
                $itemWeight         = (float) $product->getWeight();
                $totaPrice          = $totaPrice + $itemPrice;
                $item["price"]      = $itemPrice;
                $item["weight"]     = $itemWeight;
                $item["product_id"] = (int) $product->getId();
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

    //getCartData returns cart data
    public function getCartData()
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

    //postData posts json data to specified endpoint address
    public function postData($json, $endpoint)
    {
        $apiURL = Mage::getStoreConfig('settings/endpoint_url') . '/' . $endpoint;

        $appID     = Mage::getStoreConfig('reachly_handleevent_options/section_one/field_app_id');
        $secretKey = Mage::getStoreConfig('reachly_handleevent_options/section_one/field_secret_key');

        $auth = $appID . ":" . base64_encode(hash_hmac('sha256', $json, $secretKey));

        $client = new Varien_Http_Client();

        $client->setUri($apiURL)->setMethod('POST')->setConfig(array(
            'maxredirects' => 0,
            'timeout' => 15
        ));

        $client->setHeaders(array(
            'Content-Length: ' . strlen($json),
            'Authorization: ' . $auth
        ));
        $client->setRawData($json, "application/json;charset=UTF-8");

        $reqCounter = 0;
        do {
            $success = true;
            try {
                $response = $client->request();
            }
            catch (Zend_Http_Client_Exception $e) {
                $success = false;
                $reqCounter++;
            }
        } while (!$success && $reqCounter < 3);
    }
}
