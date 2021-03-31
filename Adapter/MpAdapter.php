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
        $this->_sanbox_authAPI  = 'https://sandbox-auth.myparcel.com';
        $this->_sanbox_urlAPI   = 'https://sandbox-api.myparcel.com';
        $this->_prod_authAPI   	= 'https://auth.myparcel.com';
        $this->_prod_urlAPI    	= 'https://api.myparcel.com';

        $this->singletonApi();
    }

    function singletonApi()
    {
        /**@var MyParcelConfig $this->_configHelper **/
		$environment = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('myparcel_section_general/myparcel_group_api/api_client_environment');
        $urlAPI = ($environment == 0) ? $this->_prod_urlAPI : $this->_sanbox_urlAPI;
        $authAPI = ($environment == 0) ? $this->_prod_authAPI : $this->_sanbox_authAPI;

        // env
        $urlAPI = getenv('MP_API_URL') ? : $urlAPI;
        $authAPI = getenv('MP_AUTH_URL') ? : $authAPI;


        // force token refresh (to get a token with new ACL scopes)
        $authenticator = new \MyParcelCom\ApiSdk\Authentication\ClientCredentials($this->_configHelper->getApiClientId(),  $this->_configHelper->getApiSecretKey(), $authAPI);
        $authenticator->getAuthorizationHeader(true);

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
