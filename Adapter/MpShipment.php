<?php

namespace MyParcelCOM\Magento\Adapter;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\ModuleList;
use MyParcelCom\ApiSdk\Resources\Address;
use MyParcelCom\ApiSdk\Resources\Customs;
use MyParcelCom\ApiSdk\Resources\Interfaces\ResourceInterface;
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
     * @param string $description
     * @param array  $items
     * @param array  $customs
     * @return Shipment
     */
    public function createShipment(?string $shopId, array $addressData, array $shipmentData, string $description, array $items = [], array $customs = [], array $tags = [])
    {
        $api = (new MyParcelComApi())->getInstance();
        $shop = empty($shopId)
            ? $api->getDefaultShop()
            : $api->getResourceById(ResourceInterface::TYPE_SHOP, $shopId);

        $addressData = array_merge($this->_defaultAddressData, $addressData);
        $shipmentData = array_merge($this->_defaultShipmentData, $shipmentData);

        $physicalProperties = (new PhysicalProperties())
            ->setWeight($shipmentData['weight']);

        $recipient = (new Address())
            ->setFirstName($addressData['first_name'])
            ->setLastName($addressData['last_name'])
            ->setStreet1($addressData['street'])
            ->setCity($addressData['city'])
            ->setPostalCode($addressData['postcode'])
            ->setCountryCode($addressData['country_code'])
            ->setEmail($addressData['email'])
            ->setPhoneNumber($addressData['phone_number']);

        /** @var ModuleList $moduleList */
        $moduleList = ObjectManager::getInstance()->get(ModuleList::class);
        $moduleInfo = $moduleList->getOne('MyParcelCOM_Magento');

        $shipment = (new Shipment())
            ->setChannel('magento_' . $moduleInfo['setup_version'])
            ->setTags($tags)
            ->setRegisterAt('now')
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
                ->setImageUrl($item['image_url'])
                ->setCurrency($item['item_value']['currency'])
                ->setItemValue($item['item_value']['amount'] * 100);

            $shipment->addItem($shipmentItem);
        }

        if ($customs) {
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
