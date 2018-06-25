<?php

namespace MyParcelCOM\Magento\Controller\Adminhtml\System;

use \Exception;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\App\Action\Action;
use \Magento\Framework\Controller\ResultFactory;
use \MyParcelCom\ApiSdk\Authentication\ClientCredentials;

class Config extends Action
{
    /**
     * Config constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $client_id = $this->getRequest()->getParam('client_id', false);
        $secret_key = $this->getRequest()->getParam('secret_key', false);

        try{
            $auth = new ClientCredentials($client_id, $secret_key);
            $auth->clearCache();
            $auth_header = $auth->getAuthorizationHeader();
            

            if(isset($auth_header['Authorization']) && $auth_header['Authorization'] !== ''){
                $response = array(
                    'status' => 'SUCCESS',
                    'message' => 'API Client is available',
                );
            }else{
                $response = array(
                    'status' => 'ERROR',
                    'message' => 'Client id or client secret is invalid',
                );
            }
        }catch(Exception $e){
            $response = array(
                'status' => 'ERROR',
                'message' => $e->getMessage(),
            );
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($response);
        return $resultJson;
    }
}

