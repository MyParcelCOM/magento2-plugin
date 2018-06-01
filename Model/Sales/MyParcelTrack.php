<?php

namespace MyParcelCOM\Magento\Model\Sales;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;

class MyParcelTrack
{
    const TRACK_TITLE = 'MyParcel';
    const CARRIER_CODE = 'myparcelnl';

    // Track Statuses
    const STATUS_CONCEPT    = 'shipment_concept';
    const STATUS_REGISTERED = 'shipment_registered';
    const STATUS_AT_CARRIER = 'shipment_at_carrier';
    const STATUS_AT_COURIER = 'shipment_at_courier';
    const STATUS_AT_SORTING = 'shipment_at_sorting';
    const STATUS_DELIVERED  = 'shipment_delivered';
    const STATUS_COMPLETED  = 'shipment_completed';
    const STATUS_FAILED     = 'shipment_failed';
    const STATUS_INACTIVE   = 'shipment_inactive';

    const TRACK_NUMBER_DEFAULT = 'Concept';
    private $_tracks;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct($objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment\Track $track
     */
    public function addTrack($track, $orderId)
    {
        $this->_tracks[$orderId] = $track;
    }

    public function getTrackByOrderId($orderId)
    {
        return $this->_tracks[$orderId];
    }

    /**
     * Update sales_order table
     *
     * @param $orderId
     * @return array
     */
    public function getHtmlForGridColumns($orderId)
    {
        $tracks = $this->getTracksCollectionByOrderId($orderId);

        $data = ['track_status' => [], 'track_number' => []];
        $columnHtml = ['track_status' => '', 'track_number' => ''];

        /**
         * @var Order\Shipment       $shipment
         * @var Order\Shipment\Track $track
         */
        foreach ($tracks as $track) {
            // Set all Track data in array
            if ($track['myparcel_status'] !== null) {
                $data['track_status'][] = __('status_' . $track['myparcel_status']);
            } else {
                $data['track_status'][] = __('status_' . MyParcelTrack::STATUS_CONCEPT);
            }

            if ($track['track_number']) {
                $data['track_number'][] = $track['track_number'];
            }
        }

        // Create html
        if ($data['track_status']) {
            $columnHtml['track_status'] = implode('<br>', $data['track_status']);
        }
        if ($data['track_number']) {
            $columnHtml['track_number'] = implode('<br>', $data['track_number']);
        }

        return $columnHtml;
    }

    /**
     * @param $orderId
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getTracksCollectionByOrderId($orderId)
    {
        $connection = $this->objectManager->create('\Magento\Framework\App\ResourceConnection');
        $conn = $connection->getConnection();
        $select = $conn->select()
            ->from(
                ['main_table' => 'sales_shipment_track']
            )
            ->where('main_table.order_id=?', $orderId);
        $tracks = $conn->fetchAll($select);

        return $tracks;
    }
}