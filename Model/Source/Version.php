<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\App\Config\Value;

class Version extends Value
{
    public function afterLoad()
    {
        $version = $this->_resource->getDbVersion('MyParcelCOM_Magento');

        $this->setValue($version);
    }
}
