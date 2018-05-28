<?php

namespace MyParcelCOM\Magento\Adapter\MpService;

use MyParcel\MyParcelGlobal\Adapter\MpAdapter;
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