<?php

namespace MyParcelCOM\Magento\Model\ResourceModel\Data;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MyParcelCOM\Magento\Model\Data;
use MyParcelCOM\Magento\Model\ResourceModel\Data as DataResource;

class Collection extends AbstractCollection
{
    public function _construct(): void
    {
        $this->_init(Data::class, DataResource::class);
    }
}
