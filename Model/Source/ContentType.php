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
            ['value' => 'merchandise', 'label' => __('Merchandise')],
            ['value' => 'sample_merchandise', 'label' => __('Sample Merchandise')],
            ['value' => 'returned_merchandise', 'label' => __('Returned Merchandise')],
            ['value' => 'gifts', 'label' => __('Gifts')],
            ['value' => 'documents', 'label' => __('Documents')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'merchandise'          => __('Merchandise'),
            'sample_merchandise'   => __('Sample Merchandise'),
            'returned_merchandise' => __('Returned Merchandise'),
            'gifts'                => __('Gifts'),
            'documents'            => __('Documents'),
        ];
    }
}
