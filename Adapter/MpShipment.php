<?php

namespace MyParcelCOM\Magento\Adapter;

use MyParcelCom\ApiSdk\Resources\Address;
use MyParcelCom\ApiSdk\Resources\Interfaces\PhysicalPropertiesInterface;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCom\ApiSdk\MyParcelComApi;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\NullLogger;

class MpShipment extends MPAdapter
{
    private $logger;

    private $_defaultAddressData = [
        'street'        => '',
        'house_number'  => '',
        'city'          => '',
        'postcode'      => '',
        'first_name'    => '',
        'last_name'     => '',
        'country_code'  => '',
        'email'         => ''
    ];

    private $_defaultShipmentData = [
        'weight'        => '',
    ];

    /**
     * MpShipment constructor.
     * @param ObjectManagerInterface $objectManager
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    function __construct(ObjectManagerInterface $objectManager, \Psr\Log\LoggerInterface $logger= null)
    {
        $this->logger = $logger ?: new NullLogger();

        parent::__construct();
    }

    /**
     * Prepare the necessary data and create shipment
     * @param array $addressData
     * @param array $shipmentData
     * @param string $registerAt
     * @return object response of the api
    **/
    function createShipment($addressData, $shipmentData, $registerAt = '')
    {
        /**
         * Get instance of MyParcelCOM API
        **/
        $api = MyParcelComApi::getSingleton();

        $mpCarrier  = new MpCarrier();
        $mpShop     = new MpShop();

        $addressData = array_merge($this->_defaultAddressData, $addressData);
        $shipmentData = array_merge($this->_defaultShipmentData, $shipmentData);

        // Define the recipient address.
        $recipient = new Address();

        $recipient
            ->setStreet1($addressData['street'])
            ->setCity($addressData['city'])
            ->setPostalCode($addressData['postcode'])
            ->setFirstName($addressData['first_name'])
            ->setLastName($addressData['last_name'])
            ->setCountryCode($addressData['country_code'])
            ->setEmail($addressData['email']);

        // Define the weight.
        $shipment = new Shipment();

        $shipment->setRecipientAddress($recipient);

        if (!empty($shipmentData['weight'])) {
            $shipmentData['weight'] = 2;
            $shipment->setWeight($shipmentData['weight'], PhysicalPropertiesInterface::WEIGHT_GRAM);
        }

        /**
         * SET PICKUP ADDRESS
         * Setup pickup location data
        **/
        if (!empty($shipmentData['pickup'])) {

            $pickupLocationCode = $shipmentData['pickup']['location_code'];
            $pickupAddressData  = $shipmentData['pickup']['address_data'];

            // Define the recipient address.
            $pickupAddress = new Address();
            $pickupAddress
                ->setStreet1(       $pickupAddressData['street'])
                ->setStreetNumber(  $pickupAddressData['house_number'])
                ->setCity(          $pickupAddressData['city'])
                ->setPostalCode(    $pickupAddressData['postcode'])
                ->setCountryCode(   $pickupAddressData['country_code'])
                ->setCompany(       $pickupAddressData['company'])
                ->setPhoneNumber(   $pickupAddressData['phone_number']);

            $shipment->setPickupLocationCode($pickupLocationCode);
            $shipment->setPickupLocationAddress($pickupAddress);

            /**
             * Set Contract Carrier for shipment
             **/
           /* $carrierId = $shipmentData['pickup']['carrier_id'];
            if (!empty($carrierId)) {
                $serviceContract = $mpCarrier->getServiceContract($shipment, $carrierId);
                if (!empty($serviceContract)) {
                   $shipment->setServiceContract($serviceContract);
                }
            }*/
        }

        /**
         * //TODO Setup delivery location data
         **/

        /**
         * SET SERVICE CONTRACT
         * If service contract is not set, set it!
        **/
        $serviceContract = $shipment->getServiceContract();
        if (empty($serviceContract)) {
            $serviceContract = $mpCarrier->getServiceContract($shipment);
            if (!empty($serviceContract)) {
                $shipment->setServiceContract($serviceContract);
            }
        }

        /**
         * SET SENDER ADDRESS
         * In some cases, sender address is required
         **/
        $shop = $mpShop->getDefaultShop();
        $senderAddress = $shop->getSenderAddress();
        $shipment->setSenderAddress($senderAddress);

        /**
         * Set Register At value for shipment
        **/
        if (!empty($registerAt)) {
            $shipment->setRegisterAt($registerAt);
        }

        // Create the shipment
        $response = $api->createShipment($shipment);
        //$this->logger->error(print_r($response, true));

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
     * @param string $when
     * @return mixed
     **/
    function setRegisterAt($shipment, $when = 'now')
    {
        $api = MyParcelComApi::getSingleton();
        $shipment->setRegisterAt($when);
        return $api->updateShipment($shipment);
    }
}