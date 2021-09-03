<?php

namespace MyParcelCOM\Magento\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * @method int getOrderId($index = null)
 * @method self setOrderId($value = null)
 * @method string|null getShipmentId($index = null)
 * @method self setTrackId($value = null)
 * @method string|null getTrackId($index = null)
 * @method self setShipmentId($value = null)
 * @method string|null getStatusCode($index = null)
 * @method self setStatusCode($value = null)
 * @method string|null getStatusName($index = null)
 * @method self setStatusName($value = null)
 * @method string|null getTrackingCode($index = null)
 * @method self setTrackingCode($value = null)
 * @method string|null getTrackingUrl($index = null)
 * @method self setTrackingUrl($value = null)
 */
class Data extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\Data::class);
    }
}
