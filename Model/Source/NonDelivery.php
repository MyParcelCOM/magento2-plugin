<?php
/**
 * Get paper types for MyParcel system settings
 *
 */

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class NonDelivery implements ArrayInterface
{
    /**
     * Options getter
     *
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
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'return' => __('Return'),
            'abandon' => __('Abandon'),
        ];
    }
}
