<?php

namespace MyParcelCOM\Magento\Model\Sales\Base;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use MyParcelCOM\Magento\Helper\MyParcelConfig;
use MyParcelCOM\Magento\Model\Sales\MyParcelTrack;

class MyParcelOrderCollectionBase
{
    /** @var Collection */
    protected $_orders = null;

    /** @var RequestInterface */
    public $request = null;

    /** @var TrackSender */
    protected $trackSender;

    /** @var ObjectManagerInterface */
    protected $objectManager;

    /** @var ObjectManagerInterface */
    protected $myParcelTrack;

    /** @var MyParcelConfig */
    protected $configHelper;

    /**
     * @param ObjectManagerInterface $objectManagerInterface
     * @param RequestInterface       $request
     */
    public function __construct(ObjectManagerInterface $objectManagerInterface, $request = null)
    {
        $this->objectManager = $objectManagerInterface;
        $this->request = $request;
        $this->myParcelTrack = new MyParcelTrack($this->objectManager);
        $this->configHelper = $this->objectManager->get('MyParcelCOM\Magento\Helper\MyParcelConfig');
    }

    /**
     * Get all Magento orders
     *
     * @return Collection
     */
    public function getOrders()
    {
        return $this->_orders;
    }

    /**
     * This create a shipment. Observer/NewShipment() create Magento and MyParcel Track
     *
     * @param Order $order
     * @return $this
     * @throws LocalizedException
     */
    protected function createShipment(Order $order)
    {
        /**
         * @var Order\Shipment                     $shipment
         * @var \Magento\Sales\Model\Convert\Order $convertOrder
         */
        // Initialize the order shipment object
        $convertOrder = $this->objectManager->create('Magento\Sales\Model\Convert\Order');
        $shipment = $convertOrder->toShipment($order);

        // Loop through order items
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
            $transaction = $this->objectManager->create('Magento\Framework\DB\Transaction');
            $transaction->addObject($shipment)->addObject($shipment->getOrder())->save();

            // Send email
            $this->objectManager->create('Magento\Shipping\Model\ShipmentNotifier')
                ->notify($shipment);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @return array|\Magento\Sales\Model\ResourceModel\order\shipment\Collection
     */
    protected function getShipmentsCollection()
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
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    protected function shipmentHasTrack($shipment)
    {
        return $this->getTrackByShipment($shipment)->count() === 0 ? false : true;
    }

    /**
     * Get all tracks
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection
     */
    protected function getTrackByShipment($shipment)
    {
        /* @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $collection */
        $collection = $this->objectManager->create('\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection');
        $collection
            ->addAttributeToFilter('parent_id', $shipment->getId());

        return $collection;
    }

    /**
     * Create new Magento Track
     *
     * @param Order\Shipment $shipment
     * @return \Magento\Sales\Model\Order\Shipment\Track
     * @throws \Exception
     */
    protected function setNewMagentoTrack($shipment)
    {
        /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
        $track = $this->objectManager->create('Magento\Sales\Model\Order\Shipment\Track');
        $track
            ->setOrderId($shipment->getOrderId())
            ->setShipment($shipment)
            ->setCarrierCode(MyParcelTrack::CARRIER_CODE)
            ->setTitle(MyParcelTrack::TRACK_TITLE)
            ->setQty($shipment->getTotalQty())
            ->setTrackNumber(MyParcelTrack::TRACK_NUMBER_DEFAULT)
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
        /** @var Order $order */
        foreach ($this->getOrders() as $order) {
            if ($order->hasShipments()) {
                return true;
            }
        }

        return false;
    }
}
