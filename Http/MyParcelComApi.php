<?php

namespace MyParcelCOM\Magento\Http;

use Magento\Framework\App\ObjectManager;
use MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use MyParcelCom\ApiSdk\MyParcelComApi as Api;
use MyParcelCOM\Magento\Helper\MyParcelConfig;

class MyParcelComApi
{
    public function __construct()
    {
        $configHelper = ObjectManager::getInstance()->get('MyParcelCOM\Magento\Helper\MyParcelConfig');

        $this->createSingletonApi($configHelper);
    }

    private function createSingletonApi(MyParcelConfig $configHelper)
    {
        $authenticator = new ClientCredentials(
            $configHelper->getApiClientId(),
            $configHelper->getApiSecretKey(),
            $configHelper->getAuthUrl()
        );

        // force token refresh (to get a token with new ACL scopes)
        $authenticator->clearCache();

        Api::createSingleton(
            $authenticator,
            $configHelper->getApiUrl()
        );
    }

    /**
     * @return Api
     */
    public function getInstance()
    {
        return Api::getSingleton();
    }
}
