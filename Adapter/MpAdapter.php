<?php

namespace MyParcelCOM\Magento\Adapter;

use Magento\Framework\App\ObjectManager;
use MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use MyParcelCom\ApiSdk\MyParcelComApi;
use MyParcelCOM\Magento\Helper\MyParcelConfig;

abstract class MpAdapter
{
    /** @var ObjectManager */
    protected $_objectManager;

    /** @var MyParcelConfig */
    protected $_configHelper;

    public function __construct()
    {
        $this->_objectManager = ObjectManager::getInstance();
        $this->_configHelper = $this->_objectManager->get('MyParcelCOM\Magento\Helper\MyParcelConfig');

        $this->createSingletonApi();
    }

    private function createSingletonApi()
    {
        $authenticator = new ClientCredentials(
            $this->_configHelper->getApiClientId(),
            $this->_configHelper->getApiSecretKey(),
            $this->_configHelper->getAuthUrl()
        );

        // force token refresh (to get a token with new ACL scopes)
        $authenticator->clearCache();

        MyParcelComApi::createSingleton(
            $authenticator,
            $this->_configHelper->getApiUrl()
        );
    }

    /**
     * @return MyParcelComApi
     */
    public function getApi()
    {
        return MyParcelComApi::getSingleton();
    }
}
