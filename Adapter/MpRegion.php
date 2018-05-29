<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcelCom\ApiSdk\MyParcelComApi;

class MpRegion extends MpAdapter
{
    function __construct()
    {
        parent::__construct();
    }

    function getRegions($countryCode, $regionCode)
    {
        $api = MyParcelComApi::getSingleton();
        $api->getRegions($countryCode, $regionCode);
    }
}