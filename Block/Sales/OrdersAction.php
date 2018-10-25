<?php
/**
 * Block for order actions (multiple orders action and one order action)
 */

namespace MyParcelCOM\Magento\Block\Sales;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;

class OrdersAction extends Template
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \MyParcelCOM\Magento\Helper\MyParcelConfig
     */
    private $helper;

    private $storeManager;

    /**
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->objectManager    = ObjectManager::getInstance();
        $this->storeManager     = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->helper = $this->objectManager->get('\MyParcelCOM\Magento\Helper\MyParcelConfig');
        parent::__construct($context, $data);
    }

    /**
     * Check if global API Key isset
     *
     * @return bool
     */
    public function hasApiKey()
    {
        $apiKey = $this->helper->getApiSecretKey();

        return $apiKey == '' ? 'false' : 'true';
    }


    /**
     * Get url to create and print MyParcel track
     *
     * @return string
     */
    public function getOrderAjaxUrl()
    {
        return $this->_urlBuilder->getUrl('myparcelcom/order/PrintMyParcelTrack');
    }

    /**
     * Get url to create and print MyParcel track
     *
     * @return string
     */
    public function getShipmentAjaxUrl()
    {
        return $this->_urlBuilder->getUrl('myparcelcom/shipment/PrintMyParcelTrack');
    }

    /**
     * Get url to send a mail with a return label
     *
     * @return string
     */
    public function getAjaxUrlSendReturnMail()
    {
        return $this->_urlBuilder->getUrl('myparcelcom/order/SendMyParcelReturnMail');
    }

    /**
     * Get print settings
     *
     * @return string
     */
    public function getPrintSettings()
    {
        $settings = $this->helper->getGeneralConfig('myparcel_group_print');

        return json_encode($settings);
    }

    function getCheckShipmentFileAvailabilityAjaxUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . 'rest/V1/myparcelcom/delivery/checkShipmentAvailableForPDF/';
    }
}
