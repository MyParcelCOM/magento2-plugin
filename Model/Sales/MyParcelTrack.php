<?php

namespace MyParcelCOM\Magento\Model\Sales;

use Magento\Sales\Model\Order\Shipment\Track;

class MyParcelTrack
{
    private $_tracks;

    /**
     * @param Track $track
     */
    public function addTrack($track, $orderId)
    {
        $this->_tracks[$orderId] = $track;
    }

    public function getTrackByOrderId($orderId)
    {
        return $this->_tracks[$orderId];
    }
}
