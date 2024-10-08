<?php

namespace MyParcelCOM\Magento\Model\Sales;

use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\ModuleList;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use MyParcelCom\ApiSdk\Http\Exceptions\RequestException;
use MyParcelCom\ApiSdk\Resources\Address;
use MyParcelCom\ApiSdk\Resources\Customs;
use MyParcelCom\ApiSdk\Resources\Interfaces\ResourceInterface;
use MyParcelCom\ApiSdk\Resources\PhysicalProperties;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCom\ApiSdk\Resources\ShipmentItem;
use MyParcelCOM\Magento\Http\MyParcelComApi;
use MyParcelCOM\Magento\Model\Data;
use MyParcelCOM\Magento\Model\ResourceModel\Data as DataResource;
use MyParcelCOM\Magento\Model\Sales\Base\MyParcelOrderCollectionBase;

class MyParcelOrderCollection extends MyParcelOrderCollectionBase
{
    /** @var Track[] */
    private array $_tracks = [];

    public function setOrderCollection(Collection $orderCollection): self
    {
        $this->_orders = $orderCollection;

        return $this;
    }

    /**
     * Set existing or create new Magento Track and set API consignment to collection
     *
     * @throws LocalizedException
     */
    public function createMagentoShipments(): self
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
     */
    public function setMagentoTrack(): self
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

    private function weightInGrams(?float $weight): int
    {
        return match ($this->config->getValue('general/locale/weight_unit')) {
            'lbs' => (int) ceil($weight * 1000 * 0.45359237),
            'kgs' => (int) ceil($weight * 1000),
            default => (int) ceil($weight * 1000),
        };
    }

    private function amountInCents(?float $amount): int
    {
        return (int) ceil($amount * 100);
    }

    /**
     * @throws Exception
     */
    public function createMyParcelShipments(): self
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

            $shippingAddress = $order->getShippingAddress();
            $recipient = (new Address())
                ->setStreet1($shippingAddress->getStreetLine(1))
                ->setStreet2($shippingAddress->getStreetLine(2))
                ->setPostalCode($shippingAddress->getPostcode())
                ->setCity($shippingAddress->getCity())
                ->setStateCode($shippingAddress->getRegionCode())
                ->setCountryCode($shippingAddress->getCountryId())
                ->setFirstName($shippingAddress->getFirstname())
                ->setLastName($shippingAddress->getLastname())
                ->setCompany($shippingAddress->getCompany())
                ->setEmail($shippingAddress->getEmail())
                ->setPhoneNumber($shippingAddress->getTelephone());

            $myparcelExportSetting = $this->config->getValue('myparcel_section_general/myparcel_group_setting');

            $items = [];
            foreach ($order->getItems() as $orderItem) {
                /** @var Product $product */
                $product = $this->objectManager->get(Product::class)->load($orderItem->getProductId());

                if ($product->getTypeId() !== Type::TYPE_SIMPLE) {
                    continue;
                }

                /** @var Image $imageHelper */
                $imageHelper = $this->objectManager->get(Image::class);
                $imageUrl = $imageHelper->init($product, 'product_listing_thumbnail_preview')
                    ->setImageFile($product->getImage())
                    ->getUrl();

                $items[] = (new ShipmentItem())
                    ->setSku($product->getSku())
                    ->setDescription($product->getName())
                    ->setImageUrl($imageUrl)
                    ->setItemValue($this->amountInCents($orderItem->getPrice()))
                    ->setCurrency($order->getOrderCurrencyCode())
                    ->setQuantity((int) $orderItem->getQtyOrdered())
                    ->setHsCode(!empty($product->getData('hs_code'))
                        ? $product->getData('hs_code')
                        : ($myparcelExportSetting['default_hs_code'] ?: null)
                    )
                    ->setItemWeight($this->weightInGrams($orderItem->getWeight()))
                    ->setOriginCountryCode(!empty($product->getData('country_of_manufacture'))
                        ? $product->getData('country_of_manufacture')
                        : ($myparcelExportSetting['default_origin_country_code'] ?: null)
                    )
                    ->setVatPercentage((int) $orderItem->getTaxPercent());
            }

            $customs = (new Customs())
                ->setContentType($myparcelExportSetting['content_type'])
                ->setInvoiceNumber('#' . $order->getIncrementId())
                ->setNonDelivery($myparcelExportSetting['non_delivery'])
                ->setIncoterm($myparcelExportSetting['incoterm'])
                ->setShippingValueAmount($this->amountInCents($order->getShippingAmount()))
                ->setShippingValueCurrency($order->getOrderCurrencyCode());

            try {
                $api = (new MyParcelComApi())->getInstance();

                $shop = empty($myparcelExportSetting['shop_id'])
                    ? $api->getDefaultShop()
                    : $api->getResourceById(ResourceInterface::TYPE_SHOP, $myparcelExportSetting['shop_id']);

                /** @var ModuleList $moduleList */
                $moduleList = ObjectManager::getInstance()->get(ModuleList::class);
                $moduleInfo = $moduleList->getOne('MyParcelCOM_Magento');

                $shipment = (new Shipment())
                    ->setShop($shop)
                    ->setSenderAddress($shop->getSenderAddress())
                    ->setReturnAddress($shop->getReturnAddress())
                    ->setRecipientAddress($recipient)
                    ->setDescription('Order #' . $order->getIncrementId())
                    ->setCustomerReference('#' . $order->getIncrementId())
                    ->setChannel('magento_' . $moduleInfo['setup_version'])
                    ->setPhysicalProperties((new PhysicalProperties())
                        ->setWeight($this->weightInGrams($order->getWeight()))
                    )
                    ->setItems($items)
                    ->setCustoms($customs)
                    ->setTotalValueAmount($this->amountInCents($order->getSubtotal()))
                    ->setTotalValueCurrency($order->getOrderCurrencyCode())
                    ->setTags([
                        $order->getStore()->getName(),
                        $order->getShippingDescription(),
                    ])
                    ->setRegisterAt('now');

                $createdShipment = $api->createShipment($shipment);
            } catch (RequestException $exception) {
                $response = json_decode((string) $exception->getResponse()?->getBody(), true);

                if (isset($response['errors'])) {
                    $errorMessages = [];

                    foreach ($response['errors'] as $error) {
                        if (isset($error['meta']['json_schema_errors'])) {
                            foreach ($error['meta']['json_schema_errors'] as $schemaError) {
                                if (in_array($schemaError['message'], [
                                    'Failed to match all schemas',
                                    'Failed to match exactly one schema',
                                ])) {
                                    continue;
                                }
                                $errorMessages[] = implode(' ', [
                                    str_replace('data.attributes.', '', $schemaError['property']),
                                    '-',
                                    $schemaError['message']
                                ]);
                            }
                        } else if (isset($error['detail'])) {
                            $errorMessages[] = $error['detail'];
                        } else if (isset($error['title'])) {
                            $errorMessages[] = $error['title'];
                        }
                    }

                    throw new Exception(implode(' || ', $errorMessages));
                }

                throw new Exception($exception->getMessage());
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }

            if (!empty($createdShipment->getId())) {
                $data->setOrderId($order->getId());
                $data->setTrackId($track->getId());
                $data->setShipmentId($createdShipment->getId());
                $status = $createdShipment->getShipmentStatus()->getStatus();
                $data->setStatusCode($status->getCode());
                $data->setStatusName($status->getName());

                $dataResource->save($data);
            }
        }

        return $this;
    }

    public function refreshOrdersCollection(): self
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
     */
    public function addOrdersToCollection(array $orderIds): self
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
    public function getIncrementIds(): array
    {
        $orderIncrementId = [];

        foreach ($this->getOrders() as $order) {
            $orderIncrementId[] = '#' . $order->getIncrementId();
        }

        return $orderIncrementId;
    }
}
