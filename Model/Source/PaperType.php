<?php
/**
 * Get paper types for MyParcel system settings
 *
 */

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class PaperType implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'A4', 'label' => __('A4')], ['value' => 'A6', 'label' => __('A6')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['A4' => __('A4'), 'A6' => __('A6')];
    }
}
