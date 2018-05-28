<?php
/**
 * Block for order actions (multiple orders action and one order action)
 */

namespace MyParcelCOM\Magento\Block\Sales;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use MyParcelCom\ApiSdk\src\Model\MyParcelClassConstants;

class OrderAction extends OrdersAction
{
    const EU_COUNTRIES = [
        'NL',
        'BE',
        'AT',
        'BG',
        'CZ',
        'CY',
        'DK',
        'EE',
        'FI',
        'FR',
        'DE',
        'GB',
        'GR',
        'HU',
        'IE',
        'IT',
        'LV',
        'LT',
        'LU',
        'PL',
        'PT',
        'RO',
        'SK',
        'SI',
        'ES',
        'SE',
        'XK',
    ];

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;

    /**
     * @param Context                     $context
     * @param array                       $data
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\UrlInterface $frontUrlModel,
        array $data = []
    ) {
        // Set order
        $this->order = $registry->registry('sales_order');
        parent::__construct($context, $frontUrlModel, $data);
    }

    /**
     * Check if Magento can create shipment
     *
     * Magento shipment contains one or more products. Magento shipments can never make more shipments than the number
     * of products.
     *
     * @return bool
     */
    public function canShip()
    {
        return $this->order->canShip();
    }

    /**
     * Get number of print positions. Always more than one
     */
    public function getNumberOfPrintPositions()
    {
        $numberOfTracks = $this->order->getTracksCollection()->count();
        return $numberOfTracks > 0 ? $numberOfTracks : 1;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCountry()
    {
        return $this->order->getShippingAddress()->getCountryId();
    }

    /**
     * Check if the address is outside the EU
     * @return bool
     */
    public function isCdCountry()
    {
        return !in_array(
            $this->getCountry(),
            self::EU_COUNTRIES
        );
    }
}
