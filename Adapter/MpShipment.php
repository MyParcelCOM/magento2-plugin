<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcelCom\ApiSdk\MyParcelComApi;
use MyParcelCom\ApiSdk\Resources\Address;
use MyParcelCom\ApiSdk\Resources\Customs;
use MyParcelCom\ApiSdk\Resources\Interfaces\PhysicalPropertiesInterface;
use MyParcelCom\ApiSdk\Resources\PhysicalProperties;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCom\ApiSdk\Resources\ShipmentItem;

class MpShipment extends MpAdapter
{
    private $_defaultAddressData = [
        'street'       => '',
        'house_number' => '',
        'city'         => '',
        'postcode'     => '',
        'first_name'   => '',
        'last_name'    => '',
        'country_code' => '',
        'email'        => '',
    ];

    private $_defaultShipmentData = [
        'weight' => '0.1',
    ];

    /**
     * Prepare the necessary data and create shipment
     * @param array  $addressData
     * @param array  $shipmentData
     * @param string $registerAt
     * @return object response of the api
     **/
    function createShipment($addressData, $shipmentData, $registerAt = '', $description = '', $items = '', $customs = '')
    {
        $api = MyParcelComApi::getSingleton();

        $mpCarrier = new MpCarrier();
        $mpShop = new MpShop();

        $addressData = array_merge($this->_defaultAddressData, $addressData);
        $shipmentData = array_merge($this->_defaultShipmentData, $shipmentData);

        $recipient = new Address();

        $recipient
            ->setStreet1($addressData['street'])
            ->setCity($addressData['city'])
            ->setPostalCode($addressData['postcode'])
            ->setFirstName($addressData['first_name'])
            ->setLastName($addressData['last_name'])
            ->setCountryCode($addressData['country_code'])
            ->setEmail($addressData['email'])
            ->setPhoneNumber($addressData['phone_number']);

        $shipment = new Shipment();

        $shipment->setRecipientAddress($recipient);

        if (!empty($shipmentData['weight'])) {
            $physicalProperties = (new PhysicalProperties())
                ->setWeight($shipmentData['weight'], PhysicalPropertiesInterface::WEIGHT_GRAM);
            $shipment->setPhysicalProperties($physicalProperties);
        }

        /**
         * SET SENDER ADDRESS
         * In some cases, sender address is required
         **/
        $shop = $mpShop->getDefaultShop();
        $senderAddress = $shop->getSenderAddress();
        $shipment->setSenderAddress($senderAddress);

        /**
         * SET RETURN ADDRESS
         * In some cases, return  address is required
         **/
        $returnAddress = $shop->getReturnAddress();
        $shipment->setReturnAddress($returnAddress);

        /**
         * Set Register At value for shipment
         **/
        if (!empty($registerAt)) {
            $shipment->setRegisterAt($registerAt);
        }

        /**
         * set relationship shop for shipment
         */
        $shipment->setShop($shop);

        /**
         * Set Description At value for shipment
         **/
        if (!empty($description)) {
            $shipment->setDescription($description);
        }

        /**
         * Set Items value for shipment.
         */
        if (!empty($items)) {
            $shipmentItems = [];
            foreach ($items as $item) {
                $shipmentItem = new ShipmentItem();
                $shipmentItem->setSku($item['sku']);
                $shipmentItem->setDescription($item['description']);
                $shipmentItem->setQuantity($item['quantity']);
                $shipmentItem->setHsCode($item['hs_code']);
                $shipmentItem->setOriginCountryCode($item['origin_country_code']);
                $shipmentItem->setCurrency($item['item_value']['currency']);
                $shipmentItem->setItemValue($item['item_value']['amount']);

                $shipmentItems[] = $shipmentItem;
            }
            $shipment->setItems($shipmentItems);
        }

        /**
         * Set customs value for shipment
         */
        if (!empty($customs)) {
            $shipmentCustoms = new Customs();
            $shipmentCustoms->setContentType($customs['content_type']);
            $shipmentCustoms->setInvoiceNumber($customs['invoice_number']);
            $shipmentCustoms->setIncoterm($customs['incoterm']);
            $shipmentCustoms->setNonDelivery($customs['non_delivery']);

            $shipment->setCustoms($shipmentCustoms);
        }

        $response = $api->createShipment($shipment);

        return $response;
    }

    function getShipment($shipmentId)
    {
        $api = MyParcelComApi::getSingleton();
        $shipment = $api->getShipment($shipmentId);

        return $shipment;
    }

    function getFiles($shipmentId)
    {
        $api = MyParcelComApi::getSingleton();
        $shipment = $api->getShipment($shipmentId);
        $files = $shipment->getFiles();

        return $files;
    }

    function getStatus($shipmentId)
    {
        // Get the current status of the shipment.
        $api = MyParcelComApi::getSingleton();
        $shipment = $api->getShipment($shipmentId);
        $status = $shipment->getStatus();

        return $status;
    }

    /**
     * @param Shipment $shipment
     * @param string   $when
     * @return mixed
     **/
    function setRegisterAt($shipment, $when = 'now')
    {
        $api = MyParcelComApi::getSingleton();
        $shipment->setRegisterAt($when);
        return $api->updateShipment($shipment);
    }
}
