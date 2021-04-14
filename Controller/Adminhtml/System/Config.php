<?php

namespace MyParcelCOM\Magento\Controller\Adminhtml\System;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use MyParcelCom\ApiSdk\Http\Exceptions\RequestException;
use Throwable;

class Config extends Action
{
    public function execute()
    {
        $clientId = $this->getRequest()->getParam('client_id', '');
        $secretKey = $this->getRequest()->getParam('secret_key', '');

        try {
            $auth = new ClientCredentials($clientId, $secretKey);
            $auth->getAuthorizationHeader(true);

            $response = [
                'status'  => 'SUCCESS',
                'message' => __('API Client is available'),
            ];
        } catch (RequestException $e) {
            $response = [
                'status'  => 'ERROR',
                'message' => __('Client id or client secret is invalid'),
            ];
        } catch (Throwable $e) {
            $response = [
                'status'  => 'ERROR',
                'message' => $e->getMessage(),
            ];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
    }
}
