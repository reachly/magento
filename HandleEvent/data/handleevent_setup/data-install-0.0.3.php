<?php

$helper = Mage::helper('reachly_handleevent');

$whArr   = array();

$whArr["topic"]      = "app/installed";
$whArr["updated_at"] = $helper->getTimestamp();
$whArr["app_id"]     = $helper->getStoreAppID();

$json = json_encode($whArr);

$helper->postData($json, 'app');
