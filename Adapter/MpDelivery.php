<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcelCom\ApiSdk\MyParcelComApi;

class MpDelivery extends MpAdapter
{
    function getLocations($countryCode, $postalCode)
    {
        // Get the Pickup Dropoff Locations through the sdk.
        $api = MyParcelComApi::getSingleton();
        try {
            $locations = $api->getPickUpDropOffLocations($countryCode, $postalCode);

            // Merge all the locations to a single array.
            $allLocations = array_reduce($locations, function (array $combinedLocations, $carrierLocations) {
                // If the locations for a specific carriers is `null`, it means there was an error retrieving them.
                if ($carrierLocations === null) {
                    return $combinedLocations;
                }
                /** @var \MyParcelCom\ApiSdk\Collection\CollectionInterface $carrierLocations */
                return array_merge($combinedLocations, $carrierLocations->get());
            }, []);

            /** @var \MyParcelCom\ApiSdk\Resources\PickUpDropOffLocation $location **/
            $location = $allLocations[0];

            return $allLocations;
        } catch (\Throwable $t) {
           return [];
        }
    }
}