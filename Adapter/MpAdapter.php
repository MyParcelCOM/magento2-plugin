<?php
namespace MyParcelCOM\Magento\Adapter;

use MyParcelCOM\Magento\Helper\MyParcelConfig;
use \MyParcelCom\ApiSdk\MyParcelComApi;
use \MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use Magento\Framework\ObjectManagerInterface;

abstract class MpAdapter
{
    protected $_objectManager;
    /**
     * @var MyParcelConfig $_configHelper
     */
    protected $_configHelper;

    function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
        $this->_configHelper = $this->_objectManager->get('MyParcelCOM\Magento\Helper\MyParcelConfig');

        $this->singletonApi();
    }

    function singletonApi()
    {
        /**@var MyParcelConfig $this->_configHelper **/

        // force token refresh (to get a token with new ACL scopes)
        $authenticator = new \MyParcelCom\ApiSdk\Authentication\ClientCredentials($this->_configHelper->getApiClientId(),  $this->_configHelper->getApiSecretKey(), 'https://sandbox-auth.myparcel.com');
        $authenticator->getAuthorizationHeader(true);
        // force cache refresh (to get a new list of services)
        $cache = new \Symfony\Component\Cache\Simple\FilesystemCache('myparcelcom');
        $cache->prune();

        MyParcelComApi::createSingleton(
            new ClientCredentials(
                $this->_configHelper->getApiClientId(),
                $this->_configHelper->getApiSecretKey(),
                'https://sandbox-auth.myparcel.com'
            ),
            'https://sandbox-api.myparcel.com'
        );
    }
}