<?php

namespace MyParcelCOM\Magento\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class MyParcelConfig extends AbstractHelper
{
    const PRODUCTION_API_URL = 'https://api.myparcel.com';
    const PRODUCTION_AUTH_URL = 'https://auth.myparcel.com';
    const SANDBOX_API_URL = 'https://sandbox-api.myparcel.com';
    const SANDBOX_AUTH_URL = 'https://sandbox-auth.myparcel.com';

    /**
     * @return bool
     */
    private function isTestMode()
    {
        return $this->getGeneralConfig('myparcel_group_api/api_client_environment') === '1';
    }

    /**
     * @return bool
     */
    public function getWebhookActive()
    {
        return $this->getGeneralConfig('myparcel_group_api/webhook_active');
    }

    /**
     * @return bool
     */
    public function getWebhookSecret()
    {
        return $this->getGeneralConfig('myparcel_group_api/webhook_secret');
    }

    /**
     * @return string
     */
    public function getApiClientId()
    {
        return $this->getGeneralConfig('myparcel_group_api/api_client_id');
    }

    /**
     * @return string
     */
    public function getApiSecretKey()
    {
        return $this->getGeneralConfig('myparcel_group_api/api_client_secret_key');
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->isTestMode() ? self::SANDBOX_API_URL : self::PRODUCTION_API_URL;
    }

    /**
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->isTestMode() ? self::SANDBOX_AUTH_URL : self::PRODUCTION_AUTH_URL;
    }

    /**
     * @param string $key group_id/field_id
     * @param string $scope
     * @return string|array
     **/
    public function getGeneralConfig($key, $defaultValue = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        $configValue = $this->scopeConfig->getValue('myparcel_section_general/' . $key, $scope);

        return ($configValue === null) ? $defaultValue : $configValue;
    }
}
