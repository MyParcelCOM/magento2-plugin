<?php

namespace MyParcelCOM\Magento\Http;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Url;
use MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use MyParcelCom\ApiSdk\MyParcelComApi as Api;
use MyParcelCOM\Magento\Helper\MyParcelConfig;

class MyParcelComApi
{
    public function __construct(MyParcelConfig $configHelper = null)
    {
        if (!$configHelper) {
            $configHelper = ObjectManager::getInstance()->get(MyParcelConfig::class);
        }

        $this->createSingletonApi($configHelper);
    }

    private function createSingletonApi(MyParcelConfig $configHelper)
    {
        $authenticator = new ClientCredentials(
            (string) $configHelper->getApiClientId(),
            (string) $configHelper->getApiSecretKey(),
            $configHelper->getAuthUrl()
        );

        // force token refresh (to get a token with new ACL scopes)
        $authenticator->clearCache();

        Api::createSingleton(
            $authenticator,
            $configHelper->getApiUrl()
        );
    }

    public function getInstance(): Api
    {
        return Api::getSingleton();
    }

    public function createWebhook(string $shopId, string $secret): string
    {
        /** @var Url $urlHelper */
        $urlHelper = ObjectManager::getInstance()->get('Magento\Framework\Url');
        $url = $urlHelper->getBaseUrl() . 'rest/V1/myparcelcom/webhook/status';

        $body = [
            'data' => [
                'type'          => 'hooks',
                'attributes'    => [
                    'name'    => 'Magento shipment status update',
                    'order'   => 100,
                    'active'  => true,
                    'trigger' => [
                        'resource_type'   => 'shipment-statuses',
                        'resource_action' => 'create',
                    ],
                    'action'  => [
                        'action_type' => 'send-resource',
                        'values'      => [
                            [
                                'url'      => $url,
                                'secret'   => $secret,
                                'includes' => [
                                    'status',
                                    'shipment',
                                ],
                            ],
                        ],
                    ],
                ],
                'relationships' => [
                    'owner' => [
                        'data' => [
                            'type' => 'shops',
                            'id'   => $shopId,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->getInstance()->doRequest('/hooks', 'post', $body);
        $json = json_decode($response->getBody(), true);

        return $json['data']['id'];
    }
}
