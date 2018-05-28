<?php
/**
 * Block for order actions (multiple orders action and one order action)
 */

namespace MyParcelCOM\Magento\Block\Sales;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;

class CheckoutDelivery extends Template
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \MyParcelCOM\Magento\Helper\MyParcelConfig
     */
    private $helper;

    /**
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->objectManager = ObjectManager::getInstance();
        $this->helper = $this->objectManager->get('\MyParcelCOM\Magento\Helper\MyParcelConfig');
        parent::__construct($context, $data);
    }

    /**
     * Get Google Map API Key
     *
     * @return string
     */
    public function getGoogleMapApiKey()
    {
        return $this->helper->getGeneralConfig('myparcel_group_google/googlemap_api_key', '');
    }

    /**
     * Retrieve the api ajax url that would return the first location defined by shipping address
     *
     * @return string
     */
    public function getFirstLocationAjaxUrl()
    {
        return '/myparcelcom/delivery/retrieveFirstPickupLocation/';
    }

    /**
     * Retrieve the api ajax url that would return all locations based on countryCode and postalCode
     *
     * @return string
     */
    function getLocationsAjaxUrl()
    {
        return '/rest/V1/myparcelcom/delivery/retrievePickupLocations/';
    }

    /**
     * Retrieve the api ajax url that would return all carriers
     *
     * @return string
     */
    function getCarriersAjaxUrl()
    {
        return '/rest/V1/myparcelcom/delivery/retrieveCarriers/';
    }
}
