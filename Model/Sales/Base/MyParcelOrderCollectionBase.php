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
    /** @var Collection */
    protected $_orders = null;

    /** @var RequestInterface */
    public $request = null;

    /** @var ObjectManagerInterface */
    protected $objectManager;

    /** @var ScopeConfigInterface */
    protected $config;

    /** @var MyParcelConfig */
    protected $configHelper;

    /**
     * @param ObjectManagerInterface $objectManagerInterface
     */
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
     * @param Order $order
     * @throws LocalizedException
     */
    protected function createMagentoShipment(Order $order)
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

    /**
     * @return array
     */
    protected function getShipments()
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
     *
     * @param Shipment $shipment
     * @return bool
     */
    protected function shipmentHasTrack($shipment)
    {
        return $this->getTrackByShipment($shipment)->count() > 0;
    }

    /**
     * Get all tracks
     *
     * @param Shipment $shipment
     * @return TrackCollection
     */
    protected function getTrackByShipment($shipment)
    {
        /* @var TrackCollection $collection */
        $collection = $this->objectManager->create(TrackCollection::class);
        $collection
            ->addAttributeToFilter('parent_id', $shipment->getId());

        return $collection;
    }

    /**
     * Create new Magento Track
     *
     * @param Shipment $shipment
     * @return Track
     */
    protected function setNewMagentoTrack($shipment)
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
     *
     * @return bool
     */
    public function hasShipment()
    {
        foreach ($this->getOrders() as $order) {
            if ($order->hasShipments()) {
                return true;
            }
        }

        return false;
    }
}
