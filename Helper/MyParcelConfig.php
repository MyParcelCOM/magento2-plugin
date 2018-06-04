<?php

namespace MyParcelCOM\Magento\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\ScopeInterface;

class MyParcelConfig extends AbstractHelper
{
    const GENERAL_PATH                              = 'myparcel_section_general/';
    const CHECKOUT_PATH                             = 'myparcel_section_checkout/';

    const GENERAL_API_CLIENT_ID_PATH                =  self::GENERAL_PATH . 'myparcel_group_api/api_client_id';
    const GENERAL_API_CLIENT_SECRET_PATH            =  self::GENERAL_PATH . 'myparcel_group_api/api_client_secret_key';
    const GENERAL_SHIPMENT_CREATE_NEW_ONE_EXISTS    =  self::GENERAL_PATH . 'myparcel_group_shipment/create_new_if_one_exists';

    const OPTION_YES    = 1;
    const OPTION_NO     = 0;

    /**
     * Get MyParcel configuration value by configuration path
     * @param string $configPath SECTION_ID/GROUP_ID/FIELD_ID
     * @param string $scope
     * @return mixed
    **/
    function get($configPath, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($configPath, $scope);
    }

    function getApiClientId()
    {
        return $this->scopeConfig->getValue(self::GENERAL_API_CLIENT_ID_PATH, ScopeInterface::SCOPE_STORE);
    }

    function getApiSecretKey()
    {
        return $this->scopeConfig->getValue(self::GENERAL_API_CLIENT_SECRET_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get config value from General tab
     * @param string $key group_id/field_id
     * @param string $scope
     * @return mixed
    **/
    function getGeneralConfig($key, $defaultValue = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        $configValue = $this->scopeConfig->getValue(self::GENERAL_PATH . $key, $scope);

        if ($defaultValue && empty($configValue)) {
            return $defaultValue;
        }

        return $configValue;
    }

    function isAllowedCountry($cc)
    {
        $allowSpecificCountry =  boolval($this->scopeConfig->getValue('carriers/myparcelpickup/sallowspecific', ScopeInterface::SCOPE_STORE));

        if ($allowSpecificCountry) {
            $allowedCountriesInString = $this->scopeConfig->getValue('carriers/myparcelpickup/specificcountry', ScopeInterface::SCOPE_STORE);
            $allowedCountries = explode(',', $allowedCountriesInString);
            if (is_array($allowedCountries) && in_array($cc, $allowedCountries)) {
                return true;
            }
        }

        return false;
    }
}