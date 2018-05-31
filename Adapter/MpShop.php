<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcelCom\ApiSdk\MyParcelComApi;

class MpShop extends MpAdapter
{
    function getShops()
    {
        $api = MyParcelComApi::getSingleton();
        $shops = $api->getShops();

        return $shops;
    }

    function getDefaultShop()
    {
        $api = MyParcelComApi::getSingleton();
        $shop = $api->getDefaultShop();

        return $shop;
    }
}