<?php

namespace MyParcelCOM\Magento\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Incoterm implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'DAP', 'label' => __('DAP (Delivered At Place)')],
            ['value' => 'DDP', 'label' => __('DDP (Delivered Duty Paid)')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'DAP' => __('DAP (Delivered At Place)'),
            'DDP' => __('DDP (Delivered Duty Paid)'),
        ];
    }
}
