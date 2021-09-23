<?php

namespace MyParcelCOM\Magento\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Phrase;
use Magento\Framework\Validator\Exception;
use MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use MyParcelCOM\Magento\Helper\MyParcelConfig;
use Throwable;

class Client extends Value
{
    public function validateBeforeSave()
    {
        parent::validateBeforeSave();

        try {
            $auth = new ClientCredentials(
                $this->_data['fieldset_data']['api_client_id'],
                $this->_data['fieldset_data']['api_client_secret_key'],
                ($this->_data['fieldset_data']['api_client_environment'] === '1')
                    ? MyParcelConfig::SANDBOX_AUTH_URL
                    : MyParcelConfig::PRODUCTION_AUTH_URL
            );
            $auth->getAuthorizationHeader(true);
        } catch (Throwable $throwable) {
            throw new Exception(
                new Phrase($throwable->getMessage())
            );
        }

        return $this;
    }
}