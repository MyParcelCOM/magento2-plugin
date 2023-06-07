<?php

namespace MyParcelCOM\Magento\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Incoterm implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'DAP', 'label' => __('DAP (Delivered At Place)')],
            ['value' => 'DDP', 'label' => __('DDP (Delivered Duty Paid)')],
        ];
    }

    public function toArray(): array
    {
        return [
            'DAP' => __('DAP (Delivered At Place)'),
            'DDP' => __('DDP (Delivered Duty Paid)'),
        ];
    }
}
