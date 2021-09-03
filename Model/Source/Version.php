<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\ModuleList;

class Version extends Value
{
    public function afterLoad()
    {
        /** @var ModuleList $moduleList */
        $moduleList = ObjectManager::getInstance()->get(ModuleList::class);
        $moduleInfo = $moduleList->getOne('MyParcelCOM_Magento');

        $this->setValue($moduleInfo['setup_version']);

        return parent::afterLoad();
    }
}
