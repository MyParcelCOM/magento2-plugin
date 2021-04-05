<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class ContentType implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'gifts', 'label' => __('Gifts')],
            ['value' => 'documents', 'label' => __('Documents')],
            ['value' => 'merchandise', 'label' => __('Merchandise')],
            ['value' => 'sample_merchandise', 'label' => __('Sample Merchandise')],
            ['value' => 'returned_merchandise', 'label' => __('Returned Merchandise')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'gifts'                => __('Gifts'),
            'documents'            => __('Documents'),
            'merchandise'          => __('Merchandise'),
            'sample_merchandise'   => __('Merchandise'),
            'returned_merchandise' => __('Returned'),
        ];
    }
}
