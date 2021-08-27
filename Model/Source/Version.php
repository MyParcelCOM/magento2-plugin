<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ObjectManager;

class Version extends Value
{
    public function afterLoad()
    {
        $objectManager = ObjectManager::getInstance();
        $moduleInfo = $objectManager->get('Magento\Framework\Module\ModuleList')->getOne('MyParcelCOM_Magento');

        $this->setValue($moduleInfo['setup_version']);

        return parent::afterLoad();
    }
}
