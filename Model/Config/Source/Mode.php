<?php

namespace MyParcelCOM\Magento\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Mode implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '0', 'label' => __('Production')],
            ['value' => '1', 'label' => __('Sandbox (test data)')],
        ];
    }

    public function toArray(): array
    {
        return [
            '0' => __('Production'),
            '1' => __('Sandbox (test data)'),
        ];
    }
}
