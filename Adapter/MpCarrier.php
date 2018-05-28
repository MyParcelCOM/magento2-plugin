<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcel\MyParcelGlobal\Adapter\MpAdapter;
use MyParcelCom\ApiSdk\MyParcelComApi;
use MyParcelCom\ApiSdk\Resources\Carrier;
use MyParcelCom\ApiSdk\Shipments\ServiceMatcher;

class MpCarrier extends MpAdapter
{
    function __construct()
    {
        parent::__construct();
    }

    function getCarriers()
    {
        $api = MyParcelComApi::getSingleton();
        $carriers = $api->getCarriers()->get();

        return $carriers;
    }

    function getServiceContract($shipment, $carrierId)
    {
        $api = MyParcelComApi::getSingleton();

        $carrier = new Carrier();
        $carrier->setId($carrierId);

        $services = $api->getServicesForCarrier($carrier);

        if (!empty($services)) {
            $serviceMatcher = new ServiceMatcher();

            foreach ($services as $service) {
                if ($serviceMatcher->matches($shipment, $service)) {
                    $contracts = $service->getServiceContracts();
                    if (!empty($contracts)) {
                        $contract = $contracts[0];
                        return $contract;
                    }
                }
            }
        }

        return null;
    }
}