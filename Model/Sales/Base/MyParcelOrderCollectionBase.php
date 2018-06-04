<?php

namespace MyParcelCOM\Magento\Model\Sales\Base;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use MyParcelCOM\Magento\Model\Sales\MyParcelTrack;
use MyParcelCOM\Magento\Helper\MyParcelConfig;

class MyParcelOrderCollectionBase
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $_orders = null;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    public $request = null;

    /**
     * @var TrackSender
     */
    protected $trackSender;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $myParcelTrack;

    /**
     * @var MyParcelConfig $_configHelper
     */
    protected $configHelper;

    /**
     * CreateAndPrintMyParcelTrack constructor.
     *
     * @param ObjectManagerInterface $objectManagerInterface
     * @param \Magento\Framework\App\RequestInterface $request
     * @param null $areaList
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
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrders()
    {
        return $this->_orders;
    }

    /**
     * This create a shipment. Observer/NewShipment() create Magento and MyParcel Track
     *
     * @param Order $order
     *
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
            // Check if order item has qty to ship or is virtual
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();

            // Create shipment item with qty
            $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            // Add shipment item to shipment
            $shipment->addItem($shipmentItem);
        }

        // Register shipment
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
            throw new LocalizedException(
                __($e->getMessage())
            );
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
     *
     * @return bool
     */
    protected function shipmentHasTrack($shipment)
    {
        return $this->getTrackByShipment($shipment)->count() == 0 ? false : true;
    }

    /**
     * Get all tracks
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
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
     *
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
            ->setCarrierCode('myparcelnl')
            ->setTitle('MyParcel')
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
        /** @var $order Order */
        /** @var Order\Shipment $shipment */
        foreach ($this->getOrders() as $order) {
            if ($order->hasShipments()) {
                return true;
            }
        }

        return false;
    }
}