<?php

namespace MyParcelCOM\Magento\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Data extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('myparcelcom_data', 'id');
    }
}
