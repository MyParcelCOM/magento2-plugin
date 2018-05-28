<?php
/**
 * Update MyParcel data
 */

namespace MyParcelCOM\Magento\Cron;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order;
use MyParcelCOM\Magento\Model\Sales\MyParcelOrderCollection;
use MyParcelCOM\Magento\Model\Sales\MyParcelTrack;

class UpdateStatus
{
    const PATH_MODEL_ORDER_TRACK = '\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * UpdateStatus constructor.
     *
     * @param \Magento\Framework\App\AreaList $areaList
     *
     */
    public function __construct(\Magento\Framework\App\AreaList $areaList = null)
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->orderCollection = new MyParcelOrderCollection($this->objectManager, null);
    }

    /**
     * Run the cron job
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function execute()
    {
        $this->setOrdersToUpdate();
        $this->orderCollection
            ->updateTrackStatus()
            ->updateGridByOrder();

        return $this;
    }

    /**
     * Get all order to update the data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function setOrdersToUpdate()
    {
        $this->addOrdersToCollection(
            $this->getOrderIdFromTrackToUpdate()
        );

        return $this;
    }

    /**
     * Get all ids from orders that need to be updated
     *
     * @return array
     */
    private function getOrderIdFromTrackToUpdate()
    {
        /**
         * @var                                                                    $magentoTrack Order\Shipment\Track
         * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $trackCollection
         */
        $trackCollection = $this->objectManager->get(self::PATH_MODEL_ORDER_TRACK);
        $trackCollection
            ->addFieldToSelect('order_id')
            ->addAttributeToFilter('myparcel_status', ['shipment_concept', 'shipment_registered', 'shipment_at_carrier', 'shipment_at_courier', 'shipment_at_sorting', 'shipment_delivered', 'shipment_completed'])
            ->addAttributeToFilter('myparcel_consignment_id', ['notnull' => true])
            ->addAttributeToFilter(ShipmentTrackInterface::CARRIER_CODE, MyParcelTrack::CARRIER_CODE)
            ->setPageSize(300)
            ->setOrder('order_id', 'DESC');

        return array_unique(array_column($trackCollection->getData(), 'order_id'));
    }

    /**
     * Get collection from order ids
     *
     * @param $orderIds int[]
     */
    private function addOrdersToCollection($orderIds)
    {
        /**
         * @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection
         */
        $now = new \DateTime('now -28 day');
        $collection = $this->objectManager->get(MyParcelOrderCollection::PATH_MODEL_ORDER);
        $collection
            ->addAttributeToFilter('entity_id', ['in' => $orderIds])
            ->addFieldToFilter('created_at', ['gteq' => $now->format('Y-m-d H:i:s')]);
        $this->orderCollection->setOrderCollection($collection);
    }
}
