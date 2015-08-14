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
        $tags      = $tagsModel->getResourceCollection()->addPopularity()->addStatusFilter($tagsModel->getApprovedStatus())->addProductFilter($product->getId())->setFlag('relation', true)->addStoreFilter(Mage::app()->getStore()->getId())->setActiveFilter()->load()->getItems();
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

    public function postData($json, $endpoint)
    {
        $apiURL = 'http://' . Mage::getStoreConfig('reachly_handleevent_options/section_one/field_endpoint');

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
