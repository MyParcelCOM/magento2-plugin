<?php

namespace MyParcelCOM\Magento\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ContentType implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'merchandise', 'label' => __('Merchandise')],
            ['value' => 'sample_merchandise', 'label' => __('Sample Merchandise')],
            ['value' => 'returned_merchandise', 'label' => __('Returned Merchandise')],
            ['value' => 'gifts', 'label' => __('Gifts')],
            ['value' => 'documents', 'label' => __('Documents')],
        ];
    }

    public function toArray(): array
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
