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
		$this->_authAPI         = 'https://sandbox-auth.myparcel.com';
		$this->_urlAPI          = 'https://sandbox-api.myparcel.com';
		$this->_stage_authAPI   = 'https://staging-auth.myparcel.com';
		$this->_stage_urlAPI    = 'https://staging-api.myparcel.com';

        $this->singletonApi();
    }

    function singletonApi()
    {
        /**@var MyParcelConfig $this->_configHelper **/
		$environment = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('myparcel_section_general/myparcel_group_api/api_client_environment');
		$urlAPI = ($environment == 0) ? $this->_urlAPI : $this->_stage_urlAPI;
		$authAPI = ($environment == 0) ? $this->_authAPI : $this->_stage_authAPI;
		
		// env
		$urlAPI = getenv('MP_API_URL') ? : $this->_urlAPI;
		$authAPI = getenv('MP_AUTH_URL') ? : $this->_authAPI;
		

        // force token refresh (to get a token with new ACL scopes)
        $authenticator = new \MyParcelCom\ApiSdk\Authentication\ClientCredentials($this->_configHelper->getApiClientId(),  $this->_configHelper->getApiSecretKey(), $authAPI);
        $authenticator->getAuthorizationHeader(true);
        // force cache refresh (to get a new list of services)
        $cache = new \Symfony\Component\Cache\Simple\FilesystemCache('myparcelcom');
        $cache->prune();

        MyParcelComApi::createSingleton(
            new ClientCredentials(
                $this->_configHelper->getApiClientId(),
                $this->_configHelper->getApiSecretKey(),
                $authAPI
            ),
            $urlAPI
        );
    }
}