<?php

namespace MyParcelCOM\Magento\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class MyParcelConfig extends AbstractHelper
{
    const PRODUCTION_API_URL = 'https://api.myparcel.com';
    const PRODUCTION_AUTH_URL = 'https://auth.myparcel.com';
    const SANDBOX_API_URL = 'https://api.sandbox.myparcel.com';
    const SANDBOX_AUTH_URL = 'https://auth.sandbox.myparcel.com';

    public function isTestMode(): bool
    {
        return $this->getGeneralConfig('myparcel_group_api/api_client_environment') === '1';
    }

    public function getApiClientId(): ?string
    {
        return $this->getGeneralConfig('myparcel_group_api/api_client_id');
    }

    public function getApiSecretKey(): ?string
    {
        return $this->getGeneralConfig('myparcel_group_api/api_client_secret_key');
    }

    public function getApiUrl(): string
    {
        return $this->isTestMode() ? self::SANDBOX_API_URL : self::PRODUCTION_API_URL;
    }

    public function getAuthUrl(): string
    {
        return $this->isTestMode() ? self::SANDBOX_AUTH_URL : self::PRODUCTION_AUTH_URL;
    }

    public function getWebhookId(): ?string
    {
        return $this->getGeneralConfig('myparcel_group_api/webhook_id');
    }

    public function getWebhookSecret(): ?string
    {
        return $this->getGeneralConfig('myparcel_group_api/webhook_secret');
    }

    public function getShopId(): ?string
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
