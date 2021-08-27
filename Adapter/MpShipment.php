<?php

namespace MyParcelCOM\Magento\Adapter;

use Magento\Framework\App\ObjectManager;
use MyParcelCom\ApiSdk\Resources\Address;
use MyParcelCom\ApiSdk\Resources\Customs;
use MyParcelCom\ApiSdk\Resources\Interfaces\PhysicalPropertiesInterface;
use MyParcelCom\ApiSdk\Resources\Interfaces\ShipmentInterface;
use MyParcelCom\ApiSdk\Resources\PhysicalProperties;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCom\ApiSdk\Resources\ShipmentItem;
use MyParcelCOM\Magento\Http\MyParcelComApi;

class MpShipment
{
    private $_defaultAddressData = [
        'first_name'   => '',
        'last_name'    => '',
        'street'       => '',
        'city'         => '',
        'postcode'     => '',
        'country_code' => '',
        'email'        => '',
        'phone_number' => '',
    ];

    private $_defaultShipmentData = [
        'weight' => 1000,
    ];

    /**
     * @param array  $addressData
     * @param array  $shipmentData
     * @param string $registerAt
     * @return ShipmentInterface
     **/
    public function createShipment($addressData, $shipmentData, $registerAt = '', $description = null, $items = [], $customs = null)
    {
        $api = (new MyParcelComApi())->getInstance();
        $shop = $api->getDefaultShop();

        $addressData = array_merge($this->_defaultAddressData, $addressData);
        $shipmentData = array_merge($this->_defaultShipmentData, $shipmentData);

        $physicalProperties = (new PhysicalProperties())
            ->setWeight($shipmentData['weight'], PhysicalPropertiesInterface::WEIGHT_GRAM);

        $recipient = (new Address())
            ->setFirstName($addressData['first_name'])
            ->setLastName($addressData['last_name'])
            ->setStreet1($addressData['street'])
            ->setCity($addressData['city'])
            ->setPostalCode($addressData['postcode'])
            ->setCountryCode($addressData['country_code'])
            ->setEmail($addressData['email'])
            ->setPhoneNumber($addressData['phone_number']);

        $moduleList = ObjectManager::getInstance()->get('Magento\Framework\Module\ModuleList');
        $moduleInfo = $moduleList->getOne('MyParcelCOM_Magento');

        $shipment = (new Shipment())
            ->setChannel('magento_' . $moduleInfo['setup_version'])
            ->setShop($shop)
            ->setRecipientAddress($recipient)
            ->setSenderAddress($shop->getSenderAddress())
            ->setReturnAddress($shop->getReturnAddress())
            ->setPhysicalProperties($physicalProperties)
            ->setDescription($description);

        foreach ($items as $item) {
            $shipmentItem = (new ShipmentItem())
                ->setSku($item['sku'])
                ->setDescription($item['description'])
                ->setQuantity($item['quantity'])
                ->setHsCode($item['hs_code'])
                ->setOriginCountryCode($item['origin_country_code'])
                ->setCurrency($item['item_value']['currency'])
                ->setItemValue($item['item_value']['amount'] * 100);

            $shipment->addItem($shipmentItem);
        }

        if (!empty($customs)) {
            $shipmentCustoms = (new Customs())
                ->setContentType($customs['content_type'])
                ->setInvoiceNumber($customs['invoice_number'])
                ->setIncoterm($customs['incoterm'])
                ->setNonDelivery($customs['non_delivery']);

            $shipment->setCustoms($shipmentCustoms);
        }

        return $api->createShipment($shipment);
    }
}
