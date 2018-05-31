<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcelCom\ApiSdk\MyParcelComApi;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCom\ApiSdk\Resources\Carrier;

class MpService extends MpAdapter
{
    function getService(Shipment $shipment = null)
    {
        $api = MyParcelComApi::getSingleton();
        if ($shipment) {
            // Get all services that can handle the shipment.
            $services = $api->getServices($shipment);
        } else {
            // Get all services.
            $services = $api->getServices();
        }

        return $services;
    }

    function getServicesForCarrier(Carrier $carrier)
    {
        $api = MyParcelComApi::getSingleton();
        $services = $api->getServicesForCarrier($carrier);

        return $services;
    }
}