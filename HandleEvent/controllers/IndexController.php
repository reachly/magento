<?php
class Reachly_HandleEvent_IndexController extends Mage_Core_Controller_Front_Action
{
    //<store URL>/index.php/reachly/index/products
    public function productsAction()
    {
        $helper = Mage::helper('reachly_handleevent');

        $resultArr = array();
        $prodArr   = array();

        $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');

        foreach ($collection as $product) {
            $dataArr = array();
            $tagsArr = array();

            $dataArr["id"]           = $product->getId();
            $productName             = $product->getName();
            $dataArr["title"]        = $productName;
            $dataArr["handle"]       = $helper->getHandle($productName);
            $timeArr                 = $helper->getProductTimestamps($product);
            $dataArr["created_at"]   = $timeArr[0];
            $dataArr["updated_at"]   = $timeArr[1];
            $dataArr["product_type"] = $product->getTypeId();
            $dataArr["tags"]         = $helper->getProductTags($product);
            $dataArr["vendor"]       = $product->getAttributeText('manufacturer');

            array_push($prodArr, $dataArr);
        }

        $resultArr["products"] = $prodArr;

        $json = json_encode($resultArr);

        echo ($json);
    }

    //<store URL>/index.php/reachly/index/cart
    public function cartAction()
    {

    }
}
