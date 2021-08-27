<?php

namespace MyParcelCOM\Magento\Model\Message;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Notification\MessageInterface;
use MyParcelCOM\Magento\Helper\MyParcelConfig;

class Notification implements MessageInterface
{
    private UrlInterface $backendUrl;

    public function __construct(UrlInterface $backendUrl)
    {
        $this->backendUrl = $backendUrl;
    }

    public function getIdentity()
    {
        return 'myparcelcom_webhook';
    }

    public function isDisplayed()
    {
        /** @var MyParcelConfig $configHelper */
        $configHelper = ObjectManager::getInstance()->get('MyParcelCOM\Magento\Helper\MyParcelConfig');

        return $configHelper->getWebhookActive() === null;
    }

    public function getText()
    {
        $url = $this->backendUrl->getUrl('adminhtml/system_config/edit/section/myparcel_section_general');

        return 'Your MyParcel.com webhook is not configured. <a href="' . $url . '">Please update and save your configuration</a>.';
    }

    public function getSeverity()
    {
        return self::SEVERITY_NOTICE;
    }
}
