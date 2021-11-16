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
    public function isTestMode()
    {
        return $this->getGeneralConfig('myparcel_group_api/api_client_environment') === '1';
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
     * @return string
     */
    public function getWebhookId()
    {
        return $this->getGeneralConfig('myparcel_group_api/webhook_id');
    }

    /**
     * @return string
     */
    public function getWebhookSecret()
    {
        return $this->getGeneralConfig('myparcel_group_api/webhook_secret');
    }

    /**
     * @return string
     */
    public function getShopId()
    {
        return $this->getGeneralConfig('myparcel_group_setting/shop_id');
    }

    /**
     * @param string $key group_id/field_id
     * @return string|array
     */
    private function getGeneralConfig(string $key)
    {
        $configValue = $this->scopeConfig->getValue('myparcel_section_general/' . $key, ScopeInterface::SCOPE_STORE);

        return ($configValue === null) ? null : $configValue;
    }
}
