<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Incoterm implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'DAP', 'label' => __('DAP')],
            ['value' => 'DDP', 'label' => __('DDP')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'DAP' => __('DAP'),
            'DDP' => __('DDP'),
        ];
    }
}
