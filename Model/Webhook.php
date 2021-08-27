<?php

namespace MyParcelCOM\Magento\Model;

use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request;
use MyParcelCOM\Magento\Api\WebhookInterface;
use MyParcelCOM\Magento\Helper\MyParcelConfig;

class Webhook implements WebhookInterface
{
    private $config;
    private $request;

    public function __construct(
        Request $request,
        MyParcelConfig $config
    ) {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function status()
    {
        $body = $this->request->getBodyParams();

        $this->verifySecret($body);

        $shipmentData = $body['data']['relationships']['shipment']['data'];
        $statusData = $body['data']['relationships']['status']['data'];
        $included = $body['included'];

        foreach ($included as $include) {
            $includeData = $include['data'];

            if ($includeData['type'] === $shipmentData['type'] && $includeData['id'] === $shipmentData['id']) {
                $shipmentData = $includeData;
            }
            if ($includeData['type'] === $statusData['type'] && $includeData['id'] === $statusData['id']) {
                $statusData = $includeData;
            }
        }

        return [
            $shipmentData,
            $statusData,
        ];
    }

    /**
     * @param array $body
     * @throws Exception
     */
    private function verifySecret($body)
    {
        $signature = $this->request->getHeader('X-MYPARCELCOM-SIGNATURE');

        if (hash_hmac('sha256', json_encode($body), $this->config->getWebhookSecret()) !== $signature) {
            throw new Exception(new Phrase('Signature mismatch'), 0, 401);
        }
    }
}
