<?php

namespace MyParcelCOM\Magento\Model\Config;

use MyParcelCOM\Magento\Helper\MyParcelConfig;

class AfterSaveConfigHelper extends MyParcelConfig
{
    private bool $testMode;
    private string $clientId;
    private string $clientSecret;

    public function setConfig(bool $testMode, string $clientId, string $clientSecret): void
    {
        $this->testMode = $testMode;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function getApiClientId(): string
    {
        return $this->clientId;
    }

    public function getApiSecretKey(): string
    {
        return $this->clientSecret;
    }
}
