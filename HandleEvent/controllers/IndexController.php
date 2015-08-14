<?php
class Reachly_HandleEvent_IndexController extends Mage_Core_Controller_Front_Action
{
    public function productsAction()
    {
        $helper = Mage::helper('reachly_handleevent');

        $resultArr = array();
        $prodArr   = array();

        $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');

        foreach ($collection as $product) {
            $dataArr = array();

            $dataArr["id"]         = $product->getId();
            $productName           = $product->getName();
            $dataArr["title"]      = $productName;
            $dataArr["handle"]     = $helper->getHandle($productName);
            $timeArr               = $helper->getProductTimestamps($product);
            $dataArr["created_at"] = $timeArr[0];
            $dataArr["updated_at"] = $timeArr[1];

            array_push($prodArr, $dataArr);
        }

        $resultArr["products"] = $prodArr;

        $json = json_encode($resultArr);

        echo ($json);
    }

    public function cartAction()
    {

    }
}
