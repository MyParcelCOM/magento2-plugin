<?php
/**
 * Get current version for MyParcel system settings
 *
 */

namespace MyParcelCOM\Magento\Model\Source;

use \Magento\Framework\App\Config\Value;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\Module\ResourceInterface;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;

class Version extends Value
{
    protected $moduleResource;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ResourceInterface $moduleResource,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->moduleResource = $moduleResource;

        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }
    
    public function afterLoad()
    {
        $version = $this->moduleResource->getDbVersion('MyParcelCOM_Magento');
        
        $this->setValue($version);
    }
}