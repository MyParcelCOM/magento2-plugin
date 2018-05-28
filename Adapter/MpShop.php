<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcel\MyParcelGlobal\Adapter\MpAdapter;
use MyParcelCom\ApiSdk\MyParcelComApi;

class MpShop extends MpAdapter
{
    function __construct()
    {
        parent::__construct();
    }

    function getShops()
    {
        $api = MyParcelComApi::getSingleton();
        $shops = $api->getShops();

        return $shops;
    }
}