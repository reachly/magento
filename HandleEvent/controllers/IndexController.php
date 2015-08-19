<?php
class Reachly_HandleEvent_IndexController extends Mage_Core_Controller_Front_Action
{
    protected function _construct()
    {
        $this->helper = Mage::helper('reachly_handleevent');
    }

    //<store URL>/index.php/reachly/index/products
    public function productsAction()
    {
        $resultArr = array();
        $prodArr   = array();

        $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect(array('name','manufacturer'));

        foreach ($collection as $product) {
            $dataArr = array();

            $dataArr["id"]           = $product->getId();
            $productName             = $product->getName();
            $dataArr["title"]        = $productName;
            $dataArr["handle"]       = $this->helper->getHandle($productName);
            $timeArr                 = $this->helper->getProductTimestamps($product);
            $dataArr["created_at"]   = $timeArr[0];
            $dataArr["updated_at"]   = $timeArr[1];
            $dataArr["product_type"] = $product->getTypeId();
            $dataArr["tags"]         = $this->helper->getProductTags($product);
            $dataArr["vendor"]       = $product->getAttributeText('manufacturer');
            $dataArr["options"]      = $this->helper->getProductCustomOptions($product);
            //TODO: add variants

            array_push($prodArr, $dataArr);
        }

        $resultArr["products"] = $prodArr;

        $json = json_encode($resultArr);

        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($json);
    }

    //<store URL>/index.php/reachly/index/cart
    public function cartAction()
    {
        $resultArr = array();

        $resultArr["cart_token"] = $this->helper->getCartToken();
        $resultArr               = array_merge($resultArr, $this->helper->getCartData());

        $json = json_encode($resultArr);

        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($json);
    }
}
