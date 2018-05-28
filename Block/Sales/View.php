<?php
/**
 * Show MyParcel options in order detailpage
 */

namespace MyParcelCOM\Magento\Block\Sales;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;

class View extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \MyParcelCOM\Magento\Helper\Order
     */
    private $helper;

    private $storeManager;

    /**
     * Constructor
     */
    public function _construct() {
        $this->objectManager    = ObjectManager::getInstance();
        $this->helper           = $this->objectManager->get('\MyParcelCOM\Magento\Helper\Order');
        $this->storeManager     = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        parent::_construct();
    }

    /**
     * Collect options selected at checkout and calculate type consignment
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCheckoutOptionsHtml()
    {
        $html = false;
        $order = $this->getOrder();

        /** @var object $data Data from checkout */
        $data = $order->getData('delivery_options');
        $shippingMethod = $order->getShippingMethod();

        if ($this->helper->isPickupLocation($shippingMethod))
        {
            $deliveryOptions = json_decode($data, true);

            if ($deliveryOptions) {

                $addressData    =   $deliveryOptions['attributes']['address'];
                $street         =   $addressData['street_1'];
                $houseNumber    =   $addressData['street_number'];
                $postalCode     =   $addressData['postal_code'];
                $city           =   $addressData['city'];
                $country        =   $addressData['country_code'];
                $company        =   $addressData['company'];
                $phoneNumber    =   $addressData['phone_number'];

                $address        = '<b>' . __('address')         . '</b>'    . ': '  . $street . ' ' . $houseNumber . ', ' . $postalCode . ', ' . $city . ', ' . $country;
                $company        = '<b>' . __('company')         . '</b>'    . ': '  . $company;
                $phoneNumber    = '<b>' . __('phone_number')    . '</b>'    . ': '  . $phoneNumber;

                $html .= $company . '</br>' . $address . '<br/>' . $phoneNumber;

            } else {
                /** Old data from orders before version 1.6.0 */
                $html .= __('MyParcel options data not found');
            }

        } else {

           // TODO Implement delivery shipping method
        }

        return $html !== false ? '<br>' . $html : '';
    }

    function getCheckShipmentFileAvailabilityAjaxUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . 'rest/V1/myparcelcom/delivery/checkShipmentAvailableForPDF/';
    }
}
