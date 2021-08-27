<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Mode implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Production')],
            ['value' => '1', 'label' => __('Sandbox (test data)')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            '0' => __('Production'),
            '1' => __('Sandbox (test data)'),
        ];
    }
}
