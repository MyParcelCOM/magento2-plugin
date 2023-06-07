<?php

namespace MyParcelCOM\Magento\Model\Message;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Notification\MessageInterface;
use MyParcelCOM\Magento\Helper\MyParcelConfig;

class Notification implements MessageInterface
{
    private UrlInterface $backendUrl;
    private MyParcelConfig $configHelper;

    public function __construct(
        UrlInterface $backendUrl,
        MyParcelConfig $configHelper
    ) {
        $this->backendUrl = $backendUrl;
        $this->configHelper = $configHelper;
    }

    public function getIdentity(): string
    {
        return 'myparcelcom_webhook';
    }

    public function isDisplayed(): bool
    {
        return $this->configHelper->getShopId() === null;
    }

    public function getText(): string
    {
        $url = $this->backendUrl->getUrl('adminhtml/system_config/edit/section/myparcel_section_general');

        return 'Your MyParcel.com plugin has been updated and needs to be reconfigured. <a href="' . $url . '">Please check and save your configuration</a>.';
    }

    public function getSeverity(): int
    {
        return self::SEVERITY_NOTICE;
    }
}
