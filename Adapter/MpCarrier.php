<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcelCom\ApiSdk\MyParcelComApi;
use MyParcelCom\ApiSdk\Resources\Carrier;
use MyParcelCom\ApiSdk\Resources\Service;
use MyParcelCom\ApiSdk\Resources\ServiceContract;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCom\ApiSdk\Shipments\ServiceMatcher;

class MpCarrier extends MpAdapter
{
    function getCarriers()
    {
        $api = MyParcelComApi::getSingleton();
        $carriers = $api->getCarriers()->get();

        return $carriers;
    }

    /**
     * Get the service contract suitable for the shipment
     * @param Shipment $shipment
     * @param string $carrierId
     * @return ServiceContract
     *
     * @throws Exception
     * @throws \Exception
     */
    function getServiceContract($shipment, $carrierId = null)
    {
        $api = MyParcelComApi::getSingleton();

        if ($carrierId) {
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
        } else {
            /** @var Service $service;**/

            try {
                $services = $api->getServices($shipment);
            } catch (\Exception $e) {
                return null;
            } catch (\Throwable  $e) {
                return null;
            }

            if (!empty($services)) {
                foreach ($services as $key => $service) {
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