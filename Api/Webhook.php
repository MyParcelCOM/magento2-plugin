<?php

namespace MyParcelCOM\Magento\Api;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track as TrackResource;
use MyParcelCOM\Magento\Helper\MyParcelConfig;
use MyParcelCOM\Magento\Model\Data;
use MyParcelCOM\Magento\Model\ResourceModel\Data as DataResource;

class Webhook implements WebhookInterface
{
    /** @var MyParcelConfig */
    private $config;

    /** @var DataResource */
    private $dataResource;

    /** @var Request */
    private $request;

    /** @var TrackResource */
    private $trackResource;

    public function __construct(
        MyParcelConfig $config,
        DataResource $dataResource,
        Request $request,
        TrackResource $trackResource
    ) {
        $this->config = $config;
        $this->dataResource = $dataResource;
        $this->request = $request;
        $this->trackResource = $trackResource;
    }

    public function status(): bool
    {
        $body = $this->request->getBodyParams();

        if (!$this->hasValidSignature($body)) {
            throw new Exception(new Phrase('Signature mismatch'), 0, 401);
        }

        $shipmentData = $body['data']['relationships']['shipment']['data'];
        $statusData = $body['data']['relationships']['status']['data'];
        $included = $body['included'];

        /** @var Data $data */
        $data = ObjectManager::getInstance()->create(Data::class);
        $this->dataResource->load($data, $shipmentData['id'], 'shipment_id');

        if ($data->getId()) {
            foreach ($included as $includeData) {
                if ($includeData['type'] === $shipmentData['type'] && $includeData['id'] === $shipmentData['id']) {
                    $shipmentData = $includeData;
                }
                if ($includeData['type'] === $statusData['type'] && $includeData['id'] === $statusData['id']) {
                    $statusData = $includeData;
                }
            }

            if (isset($shipmentData['attributes']['tracking_code'])) {
                $data->setTrackingCode($shipmentData['attributes']['tracking_code']);

                /** @var Track $data */
                $track = ObjectManager::getInstance()->create(Track::class);
                $this->trackResource->load($track, $data->getTrackId());
                $track->setTrackNumber($shipmentData['attributes']['tracking_code']);
                $this->trackResource->save($track);
            }
            if (isset($shipmentData['attributes']['tracking_url'])) {
                $data->setTrackingUrl($shipmentData['attributes']['tracking_url']);
            }

            $data->setStatusCode($statusData['attributes']['code']);
            $data->setStatusName($statusData['attributes']['name']);

            $this->dataResource->save($data);

            return true;
        }

        return false;
    }

    private function hasValidSignature(array $body): bool
    {
        $hash = hash_hmac('sha256', json_encode($body), $this->config->getWebhookSecret());
        $signature = $this->request->getHeader('X-MYPARCELCOM-SIGNATURE');

        return $hash === $signature;
    }
}
