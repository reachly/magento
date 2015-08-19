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
        $formattedOffset = sprintf("%s%02d:%02d", ($offset >= 0) ? '+' : '-', abs($offset / 3600), abs($offset % 3600) / 60);
        return $dt->format('Y-m-d') . 'T' . $dt->format('H:i:s') . $formattedOffset;
    }

    public function getStoreAppID()
    {
        return "magento." . parse_url(Mage::getBaseUrl(), PHP_URL_HOST);
    }

    public function getHandle($title)
    {
        //TODO: handle multiple spaces
        return str_replace(' ', '.', strtolower($title));
    }

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

    public function getProductImages($product)
    {
        $imagesArr = array();

        $counter = 1;

        $images = Mage::getModel('catalog/product')->load($product->getId())->getMediaGalleryImages();
        foreach ($images as $image) {
            $imgArr               = array();
            $imgArr["id"]         = $image->getId();
            $imgArr["src"]        = $image->getUrl();
            $imgArr["position"]   = $counter;
            $imgArr["product_id"] = $product->getId();
            $counter++;
            array_push($imagesArr, $imgArr);
        }

        return $imagesArr;
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

    public function postData($json, $endpoint)
    {
        $apiURL = 'http://' . Mage::getStoreConfig('reachly_handleevent_options/section_one/field_endpoint') . '/' . $endpoint;

        $appID     = Mage::getStoreConfig('reachly_handleevent_options/section_one/field_app_id');
        $secretKey = Mage::getStoreConfig('reachly_handleevent_options/section_one/field_secret_key');

        $auth = $appID . ":" . base64_encode(hash_hmac('sha256', $json, $secretKey));

        $iClient = new Varien_Http_Client();
        $iClient->setUri($apiURL)->setMethod('POST')->setConfig(array(
            'maxredirects' => 0,
            'timeout' => 5
        ));
        $iClient->setHeaders(array(
            'Content-Length: ' . strlen($json),
            'Authorization: ' . $auth
        ));
        $iClient->setRawData($json, "application/json;charset=UTF-8");
        $response = $iClient->request();
    }
}
