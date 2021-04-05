<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class NonDelivery implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'return', 'label' => __('Return')],
            ['value' => 'abandon', 'label' => __('Abandon')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'return'  => __('Return'),
            'abandon' => __('Abandon'),
        ];
    }
}
