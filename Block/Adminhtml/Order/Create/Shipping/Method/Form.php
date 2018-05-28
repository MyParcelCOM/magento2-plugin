<?php
/**
 * Copyright © MagePal LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MyParcelCOM\Magento\Block\Adminhtml\Order\Create\Shipping\Method;

use MyParcelCOM\Magento\Model\Carrier\MyParcelCarrier;

class Form extends \Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method\Form
{

    /**
     * Custom shipping rate
     *
     * @return string
     */
    public function getActiveCustomShippingRateMethod()
    {
        $rate = $this->getActiveMethodRate();
        return $rate && $rate->getCarrier() == MyParcelCarrier::CODE ? $rate->getMethod() : '';
    }

    /**
     * Custom shipping rate
     *
     * @return string
     */
    public function getActiveCustomShippingRatePrice()
    {
        $rate = $this->getActiveMethodRate();
        return $this->getActiveCustomShippingRateMethod() && $rate->getPrice() ? $rate->getPrice() * 1 : '';
    }

    /**
     * Custom shipping rate
     *
     * @return string
     */
    public function isCustomShippingRateActive()
    {
        $rate = $this->getActiveMethodRate();
        return $rate && $rate->getCarrier() == MyParcelCarrier::CODE ? true : false;
    }
}
