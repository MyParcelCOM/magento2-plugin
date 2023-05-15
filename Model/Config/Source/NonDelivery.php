<?php

namespace MyParcelCOM\Magento\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class NonDelivery implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'return', 'label' => __('Return')],
            ['value' => 'abandon', 'label' => __('Abandon')],
        ];
    }

    public function toArray(): array
    {
        return [
            'return'  => __('Return'),
            'abandon' => __('Abandon'),
        ];
    }
}
