<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\App\Config\Value;

class Version extends Value
{
    public function afterLoad()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $moduleInfo = $objectManager->get('Magento\Framework\Module\ModuleList')->getOne('MyParcelCOM_Magento');

        $this->setValue($moduleInfo['setup_version']);

        return parent::afterLoad();
    }
}
