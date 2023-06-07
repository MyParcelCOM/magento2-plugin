<?php

namespace MyParcelCOM\Magento\Model\Sales\Base;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackCollection;
use Magento\Shipping\Model\ShipmentNotifier;
use MyParcelCOM\Magento\Helper\MyParcelConfig;

class MyParcelOrderCollectionBase
{
    protected ?Collection $_orders = null;
    protected ObjectManagerInterface $objectManager;
    protected ScopeConfigInterface $config;
    protected MyParcelConfig $configHelper;

    public function __construct(ObjectManagerInterface $objectManagerInterface)
    {
        $this->objectManager = $objectManagerInterface;

        $this->config = $this->objectManager->get(ScopeConfigInterface::class);
        $this->configHelper = $this->objectManager->get(MyParcelConfig::class);
    }

    /**
     * @return Collection|Order[]
     */
    public function getOrders()
    {
        return $this->_orders;
    }

    /**
     * @throws LocalizedException
     */
    protected function createMagentoShipment(Order $order): void
    {
        /** @var ConvertOrder $convertOrder */
        $convertOrder = $this->objectManager->create(ConvertOrder::class);
        $shipment = $convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();
            $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            $shipment->addItem($shipmentItem);
        }

        $shipment->register();
        $shipment->getOrder()->setStatus(Order::STATE_PROCESSING);

        try {
            // Save created shipment and order
            /** @var Transaction $transaction */
            $transaction = $this->objectManager->create(Transaction::class);
            $transaction->addObject($shipment)->addObject($shipment->getOrder())->save();

            // Send email
            /** @var ShipmentNotifier $shipmentNotifier */
            $shipmentNotifier = $this->objectManager->create(ShipmentNotifier::class);
            $shipmentNotifier->notify($shipment);
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    protected function getShipments(): array
    {
        if ($this->_orders == null) {
            return [];
        }

        $shipments = [];
        foreach ($this->getOrders() as $order) {
            foreach ($order->getShipmentsCollection() as $shipment) {
                $shipments[] = $shipment;
            }
        }

        return $shipments;
    }

    /**
     * Check if track already exists
     */
    protected function shipmentHasTrack(Shipment $shipment): bool
    {
        return $this->getTrackByShipment($shipment)->count() > 0;
    }

    /**
     * Get all tracks
     */
    protected function getTrackByShipment(Shipment $shipment): TrackCollection
    {
        /* @var TrackCollection $collection */
        $collection = $this->objectManager->create(TrackCollection::class);
        $collection
            ->addAttributeToFilter('parent_id', $shipment->getId());

        return $collection;
    }

    /**
     * Create new Magento Track
     */
    protected function setNewMagentoTrack(Shipment $shipment): Track
    {
        /** @var Track $track */
        $track = $this->objectManager->create(Track::class);
        $track
            ->setOrderId($shipment->getOrderId())
            ->setShipment($shipment)
            ->setCarrierCode('myparcelcom')
            ->setTitle('MyParcel.com')
            ->setQty($shipment->getTotalQty())
            ->setTrackNumber('-')
            ->save();

        return $track;
    }

    /**
     * Check if there is 1 shipment in all orders
     */
    public function hasShipment(): bool
    {
        foreach ($this->getOrders() as $order) {
            if ($order->hasShipments()) {
                return true;
            }
        }

        return false;
    }
}
