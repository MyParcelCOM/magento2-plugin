<?php

namespace MyParcelCOM\Magento\Model\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ObjectManager;
use MyParcelCom\ApiSdk\Http\Exceptions\RequestException;
use MyParcelCOM\Magento\Http\MyParcelComApi;

class Webhook extends Value
{
    const PATH_WEBHOOK_ID = 'myparcel_section_general/myparcel_group_api/webhook_id';
    const PATH_WEBHOOK_SECRET = 'myparcel_section_general/myparcel_group_api/webhook_secret';

    public function afterSave()
    {
        if ($this->isValueChanged()) {
            if ($this->getValue() === '1') {
                $this->createWebhook();
            } else {
                $hookId = $this->getHookId();
                $this->deleteWebhook($hookId);
            }
        }

        return parent::afterSave();
    }

    private function createWebhook()
    {
        /** @var \Magento\Framework\Url $urlHelper */
        $urlHelper = ObjectManager::getInstance()->get('Magento\Framework\Url');
        $url = $urlHelper->getBaseUrl() . 'rest/V1/myparcelcom/webhook/status';

        $api = (new MyParcelComApi())->getInstance();
        $shopId = $api->getDefaultShop()->getId();
        $secret = $this->getTestMode() . '_' . md5(uniqid(rand(), true));

        $body = [
            'data' => [
                'type'          => 'hooks',
                'attributes'    => [
                    'name'    => 'New shipment status update',
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

        $response = $api->doRequest('/hooks', 'post', $body);
        $json = json_decode($response->getBody(), true);

        $this->saveHookId($json['data']['id']);
        $this->saveHookSecret($secret);
    }

    /**
     * @param string $hookId
     */
    private function deleteWebhook(string $hookId)
    {
        try {
            $api = (new MyParcelComApi())->getInstance();
            $api->doRequest('/hooks/' . $hookId, 'delete');
        } catch (RequestException $exception) {
            // Assume the hook could not be found because it has already been deleted.
        }

        $this->saveHookId(null);
        $this->saveHookSecret(null);
    }

    private function getHookId()
    {
        return (string) $this->_config->getValue(
            self::PATH_WEBHOOK_ID,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    private function getTestMode()
    {
        return (string) $this->_config->getValue(
            'myparcel_section_general/myparcel_group_api/api_client_environment',
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * @param string|null $hookId
     */
    private function saveHookId(?string $hookId)
    {
        /** @var WriterInterface $configWriter */
        $configWriter = ObjectManager::getInstance()->get(WriterInterface::class);
        $configWriter->save(
            self::PATH_WEBHOOK_ID,
            $hookId,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * @param string|null $hookSecret
     */
    private function saveHookSecret(?string $hookSecret)
    {
        /** @var WriterInterface $configWriter */
        $configWriter = ObjectManager::getInstance()->get(WriterInterface::class);
        $configWriter->save(
            self::PATH_WEBHOOK_SECRET,
            $hookSecret,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }
}
