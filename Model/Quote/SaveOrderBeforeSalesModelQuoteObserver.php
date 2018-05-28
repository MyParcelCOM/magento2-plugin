<?php
/**
 * Save delivery date and delivery options
 */

namespace MyParcelCOM\Magento\Model\Quote;

use Magento\Framework\Event\ObserverInterface;

class SaveOrderBeforeSalesModelQuoteObserver implements ObserverInterface
{
    const FIELD_DELIVERY_OPTIONS = 'delivery_options';
    const FIELD_TRACK_STATUS = 'track_status';

    /**
     * SaveOrderBeforeSalesModelQuoteObserver constructor.
     */
    public function __construct()
    {

    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');

        if ($quote->hasData(self::FIELD_DELIVERY_OPTIONS)) {
            $jsonDeliveryOptions = $quote->getData(self::FIELD_DELIVERY_OPTIONS);
            $order->setData(self::FIELD_DELIVERY_OPTIONS, $jsonDeliveryOptions);
        }

        return $this;
    }

}
