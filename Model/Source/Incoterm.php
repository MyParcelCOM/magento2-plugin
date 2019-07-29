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
            ['value' => 'DDP', 'label' => __('DDP')],
            ['value' => 'DAP', 'label' => __('DAP')],
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
            'DDP' => __('DDP'),
            'DAP' => __('DAP'),
        ];
    }
}
