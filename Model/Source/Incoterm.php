<?php
/**
 * Get paper types for MyParcel system settings
 *
 */

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Incoterm implements ArrayInterface
{
    /**
     * Options getter
     *
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
     * Get options in "key-value" format
     *
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
