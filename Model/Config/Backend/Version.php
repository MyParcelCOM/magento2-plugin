<?php

namespace MyParcelCOM\Magento\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\ModuleList;

class Version extends Value
{
    public function afterLoad(): self
    {
        /** @var ModuleList $moduleList */
        $moduleList = ObjectManager::getInstance()->get(ModuleList::class);
        $moduleInfo = $moduleList->getOne('MyParcelCOM_Magento');

        $this->setValue($moduleInfo['setup_version']);

        return parent::afterLoad();
    }
}
