<?php
class Reachly_HandleEvent_Model_Observer
{
    protected function getUserID()
    {
      $cookie = Mage::getSingleton('core/cookie');
      return $cookie->get('_rly');
    }

    protected function getSessionData()
    {
      $cookie = Mage::getSingleton('core/cookie');
      $json = $cookie->get('_rlys');
      $json = stripslashes($json);
      return json_decode($json, true);
    }

    protected function increasePageCount()
    {
      $cookie = Mage::getSingleton('core/cookie');
      $json = $cookie->get('_rlys');
      $json = stripslashes($json);
      $sArr = json_decode($json);
      $sArr[1] = $sArr[1] + 1;
      $jsonNew = json_encode($sArr);
      $cookie->set('_rlys', $jsonNew, time()+60*60*24*365*2, '/');
    }

    public function setUserData($observer) {
      $cookie = Mage::getSingleton('core/cookie');
      if(!isset($_COOKIE['_rly'])) {
        $uid = dechex(mt_rand(1, 16777216))."-".dechex(mt_rand(1, 16777216))."-".dechex(time())."-".dechex(mt_rand(1, 16777216));
        $cookie->set('_rly', $uid, time()+60*60*24*365*2, '/');
      }
      if(!isset($_COOKIE['_rlys'])) {
        $sid = dechex(mt_rand(1, 16777216));
        $sArr = array($sid, 1);
        $json = json_encode($sArr);
        $cookie->set('_rlys', $json, time()+30*60, '/');
      }
    }

    public function processLoadEvent($observer)
    {
        $this->increasePageCount();
        $sessionData = $this->getSessionData();

        $timestamp = round(microtime(1) * 1000);
        $url = Mage::helper('core/url')->getCurrentUrl();
        $identity = $this->getUserID();
        $sessionID = $sessionData[0];
        $pageCount = $sessionData[1];
        $referer = Mage::helper('core/http')->getHttpReferer();

        Mage::log("\n=============\n"."timestamp: ".$timestamp."\n"."url: ".$url."\n"."referer: ".$referer."\n"."identity: ".$identity."\n"."sessionID: ".$sessionID."\n"."pageCount: ".$pageCount);
    }
}
