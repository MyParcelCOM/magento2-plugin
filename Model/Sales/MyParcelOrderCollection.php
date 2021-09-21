<?php

namespace MyParcelCOM\Magento\Model\Sales;

use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use MyParcelCOM\Magento\Adapter\MpShipment;
use MyParcelCOM\Magento\Model\Data;
use MyParcelCOM\Magento\Model\ResourceModel\Data as DataResource;
use MyParcelCOM\Magento\Model\Sales\Base\MyParcelOrderCollectionBase;

class MyParcelOrderCollection extends MyParcelOrderCollectionBase
{
    /** @var Track[] */
    private $_tracks = [];

    /**
     * @param $orderCollection Collection
     * @return $this
     */
    public function setOrderCollection($orderCollection)
    {
        $this->_orders = $orderCollection;

        return $this;
    }

    /**
     * Set existing or create new Magento Track and set API consignment to collection
     *
     * @throws LocalizedException
     */
    public function createMagentoShipments()
    {
        foreach ($this->getOrders() as $order) {
            if ($order->canShip() && !$order->hasShipments()) {
                $this->createMagentoShipment($order);
            }
        }

        $this->getOrders()->save();

        $this->refreshOrdersCollection();

        return $this;
    }

    /**
     * Create new Magento Track and save order
     * @return $this
     */
    public function setMagentoTrack()
    {
        /** @var Order\Shipment $shipment */
        foreach ($this->getShipments() as $shipment) {
            if (!$this->shipmentHasTrack($shipment)) {
                $track = $this->setNewMagentoTrack($shipment);
            } else {
                $track = $shipment->getTracksCollection()->getLastItem();
            }

            $this->_tracks[$shipment->getOrderId()] = $track;
        }

        $this->getOrders()->save();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function createMyParcelShipments()
    {
        /** @var DataResource $dataResource */
        $dataResource = ObjectManager::getInstance()->create(DataResource::class);

        $orders = $this->getOrders();

        foreach ($orders as $order) {
            $track = $this->_tracks[$order->getId()];

            /** @var Data $data */
            $data = ObjectManager::getInstance()->create(Data::class);
            $dataResource->load($data, $order->getId(), 'order_id');

            if ($data->getId()) {
                continue;
            }

            $shippingAddressObj = $order->getShippingAddress();
            $streets = $shippingAddressObj->getStreet();
            $street1 = $street2 = $street3 = $street4 = '';

            if (is_array($streets)) {
                foreach ($streets as $key => $streetValue) {
                    $streetValue = trim($streetValue, ',');
                    ${'street' . ($key + 1)} = $streetValue;
                }
            }

            $addressData = [
                'street'       => $street1,
                'city'         => $shippingAddressObj->getCity(),
                'postcode'     => $shippingAddressObj->getPostcode(),
                'first_name'   => $shippingAddressObj->getFirstname(),
                'last_name'    => $shippingAddressObj->getLastname(),
                'country_code' => $shippingAddressObj->getCountryId(),
                'email'        => $shippingAddressObj->getEmail(),
                'phone_number' => $shippingAddressObj->getTelephone(),
            ];

            $shipmentWeight = $order->getWeight();
            $unitWeight = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('general/locale/weight_unit');

            switch ($unitWeight) {
                case 'lbs':
                    $weightInGrams = (int) round($shipmentWeight * 1000 * 0.45359237);
                    break;

                case 'kgs':
                default:
                    $weightInGrams = (int) round($shipmentWeight * 1000);
            }

            $shipmentData = [
                'weight' => $weightInGrams,
            ];

            /**
             * get current currency code
             */
            $priceCurrency = $this->objectManager->get('\Magento\Framework\Pricing\PriceCurrencyInterface');
            $currencyCode = $priceCurrency->getCurrency()->getCurrencyCode();

            /**
             * retrive default hs code and default origin country code
             */
            $myparcelExportSetting = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('myparcel_section_general/myparcel_group_setting');

            $defaultHsCode = $myparcelExportSetting['default_hs_code'];
            $defaultOriginCountryCode = $myparcelExportSetting['default_origin_coutry_code'];

            $items = [];
            $orderItems = $order->getItems();
            foreach ($orderItems as $orderItem) {
                /** @var Product $product */
                $product = $this->objectManager->get('Magento\Catalog\Model\Product')->load($orderItem->getProductId());

                if ($product->getTypeId() !== Type::TYPE_SIMPLE) {
                    continue;
                }

                /** @var Image $imageHelper */
                $imageHelper = $this->objectManager->get('\Magento\Catalog\Helper\Image');
                $imageUrl = $imageHelper->init($product, 'product_listing_thumbnail_preview')
                    ->setImageFile($product->getImage())
                    ->getUrl();

                $item = [
                    'sku'                 => $product->getData('sku'),
                    'description'         => $product->getData('name'),
                    'item_value'          => [
                        'amount'   => ($orderItem->getPrice() > 0) ? $orderItem->getPrice() : 1,
                        'currency' => $currencyCode,
                    ],
                    'quantity'            => intval($orderItem->getData('qty_ordered')),
                    'hs_code'             => (!empty($product->getData('hs_code'))) ? $product->getData('hs_code') : $defaultHsCode,
                    'origin_country_code' => (!empty($product->getData('origin_country_code'))) ? $product->getData('origin_country_code') : $defaultOriginCountryCode,
                    'nett_weight'         => ($unitWeight == 'lbs') ? intval($orderItem->getData('weight') * 0.45359237) : intval($orderItem->getData('weight')),
                    'image_url'           => $imageUrl,
                ];
                $items[] = $item;
            }

            $customs = [
                'content_type'   => $myparcelExportSetting['content_type'],
                'invoice_number' => '#' . $order->getIncrementId(),
                'non_delivery'   => $myparcelExportSetting['non_delivery'],
                'incoterm'       => $myparcelExportSetting['incoterm'],
            ];

            try {
                $shipmentBuilder = new MpShipment();

                $shipment = $shipmentBuilder->createShipment($myparcelExportSetting['shop_id'], $addressData, $shipmentData, 'Order #' . $order->getIncrementId(), $items, $customs, [
                    $order->getStore()->getName(),
                    $order->getShippingDescription(),
                ]);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }

            if (!empty($shipment->getId())) {
                $data->setOrderId($order->getId());
                $data->setTrackId($track->getId());
                $data->setShipmentId($shipment->getId());
                $status = $shipment->getShipmentStatus()->getStatus();
                $data->setStatusCode($status->getCode());
                $data->setStatusName($status->getName());

                $dataResource->save($data);
            }
        }

        return $this;
    }

    public function refreshOrdersCollection()
    {
        $orderIds = [];

        foreach ($this->getOrders() as $order) {
            $orderIds[] = $order->getId();
        }
        $this->getOrders()->clear();

        return $this->addOrdersToCollection($orderIds);
    }

    /**
     * @param $orderIds int[]
     * @return $this
     */
    public function addOrdersToCollection($orderIds)
    {
        /** @var Collection $collection */
        $collection = $this->objectManager->get(Collection::class);
        $collection->addAttributeToFilter('entity_id', ['in' => $orderIds]);
        $this->setOrderCollection($collection);
        return $this;
    }

    /**
     * @return string[]
     */
    public function getIncrementIds()
    {
        $orderIncrementId = [];

        foreach ($this->getOrders() as $order) {
            $orderIncrementId[] = '#' . $order->getIncrementId();
        }

        return $orderIncrementId;
    }
}
