<?php
namespace MyParcelCOM\Magento\Model\Checkout;


class LayoutProcessor
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddressFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory $agreementCollectionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->checkoutSession = $checkoutSession;
        $this->customerAddressFactory = $customerAddressFactory;
    }
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array  $jsLayout
    ) {
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['before-shipping-method-form']['children'] =
            array_merge_recursive(
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['before-shipping-method-form']['children'],
                [
                    'delivery_options' => [
                        //'component' => 'Magento_Ui/js/form/element/abstract',
                        'component'  => 'MyParcelCOM_Magento/js/model/mp-shipping-validator',
                        'config' => [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/textarea',
                            'cols' => 15,
                            'rows' => 5,
                            'options' => [],
                            'id' => 'delivery-options',
                        ],
                        'dataScope' => 'shippingAddress.delivery_options',
                        'label' => 'Delivery Options',
                        'provider' => 'checkoutProvider',
                        'visible' => false,
                        'validation' => [

                        ],
                        'sortOrder' => 200,
                        'id' => 'delivery-options',
                    ],
                ]
            );

        return $jsLayout;
    }


}