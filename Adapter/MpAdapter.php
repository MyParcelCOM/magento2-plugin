<?php
namespace MyParcelCOM\Magento\Adapter;

use Magento\Framework\App\ObjectManager;
use MyParcelCOM\Magento\Helper\MyParcelConfig;
use \MyParcelCom\ApiSdk\MyParcelComApi;
use \MyParcelCom\ApiSdk\Authentication\ClientCredentials;

abstract class MpAdapter
{
    protected $_objectManager;
    /**
     * @var MyParcelConfig $_configHelper
     */
    protected $_configHelper;
	protected $_authAPI;

    function __construct()
    {
        $this->_objectManager   = ObjectManager::getInstance();
        $this->_configHelper    = $this->_objectManager->get('MyParcelCOM\Magento\Helper\MyParcelConfig');
		$this->_authAPI           = 'https://staging-auth.myparcel.com'; // https://sandbox-auth.myparcel.com

        $this->singletonApi();
    }

    function singletonApi()
    {
        /**@var MyParcelConfig $this->_configHelper **/

        // force token refresh (to get a token with new ACL scopes)
        $authenticator = new \MyParcelCom\ApiSdk\Authentication\ClientCredentials($this->_configHelper->getApiClientId(),  $this->_configHelper->getApiSecretKey(), $this->_authAPI);
        $authenticator->getAuthorizationHeader(true);
        // force cache refresh (to get a new list of services)
        $cache = new \Symfony\Component\Cache\Simple\FilesystemCache('myparcelcom');
        $cache->prune();

        MyParcelComApi::createSingleton(
            new ClientCredentials(
                $this->_configHelper->getApiClientId(),
                $this->_configHelper->getApiSecretKey(),
                $this->_authAPI
            ),
            $this->_authAPI
        );
    }
}