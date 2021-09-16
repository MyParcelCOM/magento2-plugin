<?php

namespace MyParcelCOM\Magento\Model\Config;

use MyParcelCOM\Magento\Helper\MyParcelConfig;

class AfterSaveConfigHelper extends MyParcelConfig
{
    /** @var bool */
    private $testMode;

    /**  @var string */
    private $clientId;

    /**  @var string */
    private $clientSecret;

    public function setConfig(bool $testMode, string $clientId, string $clientSecret)
    {
        $this->testMode = $testMode;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->testMode;
    }

    /**
     * @return string
     */
    public function getApiClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getApiSecretKey()
    {
        return $this->clientSecret;
    }
}
