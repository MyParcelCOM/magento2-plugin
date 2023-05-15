<?php

namespace MyParcelCOM\Magento\Model\Config\Backend;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ObjectManager;
use MyParcelCOM\Magento\Http\MyParcelComApi;
use MyParcelCOM\Magento\Model\Config\AfterSaveConfigHelper;
use Throwable;

class Shop extends Value
{
    const PATH_WEBHOOK_ID = 'myparcel_section_general/myparcel_group_api/webhook_id';
    const PATH_WEBHOOK_SECRET = 'myparcel_section_general/myparcel_group_api/webhook_secret';

    public function afterSave(): self
    {
        /** @var WriterInterface $configWriter */
        $configWriter = ObjectManager::getInstance()->get(WriterInterface::class);

        if ($this->isValueChanged()) {
            $this->deleteWebhook();
        }

        $myParcelComApi = $this->createApiWithPostedConfig();

        $shopId = $this->getValue();
        if (!$shopId) {
            $shopId = $myParcelComApi->getInstance()->getDefaultShop()->getId();
            $this->setValue($shopId);
            $configWriter->save($this->getPath(), $shopId);
        }

        if ($this->isValueChanged()) {
            $secret = md5(uniqid(rand(), true));
            $hookId = $myParcelComApi->createWebhook($shopId, $secret);

            $configWriter->save(self::PATH_WEBHOOK_ID, $hookId);
            $configWriter->save(self::PATH_WEBHOOK_SECRET, $secret);
        }

        return parent::afterSave();
    }

    /**
     * Use a new API instance with the new config (which is validated but in a transaction that has not been saved yet).
     */
    private function createApiWithPostedConfig(): MyParcelComApi
    {
        /** @var AfterSaveConfigHelper $configHelper */
        $configHelper = ObjectManager::getInstance()->get(AfterSaveConfigHelper::class);

        $configHelper->setConfig(
            $this->getData('groups/myparcel_group_api/fields/api_client_environment/value') === '1',
            $this->getData('groups/myparcel_group_api/fields/api_client_id/value'),
            $this->getData('groups/myparcel_group_api/fields/api_client_secret_key/value')
        );

        return new MyParcelComApi($configHelper);
    }

    /**
     * Use the regular API with the old config to delete a previously created webhook.
     */
    private function deleteWebhook(): void
    {
        try {
            $api = (new MyParcelComApi())->getInstance();
            $api->doRequest('/hooks/' . $this->_config->getValue(self::PATH_WEBHOOK_ID), 'delete');
        } catch (Throwable $throwable) {
            // Assume the hook could not be found because it has already been deleted.
        }
    }
}
