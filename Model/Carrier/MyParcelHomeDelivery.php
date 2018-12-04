<?php
namespace MyParcelCOM\Magento\Model\Carrier;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Helper\Carrier;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use MyParcelCOM\Magento\Helper\CustomShippingHelper;
use Psr\Log\LoggerInterface;

class MyParcelHomeDelivery extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    const CODE = 'myparcelhomedelivery';
	const CARRIERTITLE = 'Home delivery';

    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = self::CODE;
    /**
     *
     * @var MethodFactory
     */
    protected $_rateMethodFactory;
    /**
     * Carrier helper
     *
     * @var Carrier
     */
    protected $_carrierHelper;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_rateFactory;
    /**
     * @var State
     */
    protected $_state;
    /**
     * @var \MyParcelCOM\Magento\Helper\Data
     */
    protected $_helper;


    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateFactory
     * @param Carrier $carrierHelper
     * @param MethodFactory $rateMethodFactory
     * @param State $state
     * @param CustomShippingHelper $helper
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateFactory,
        Carrier $carrierHelper,
        MethodFactory $rateMethodFactory,
        \Magento\Framework\App\State $state,
        CustomShippingHelper $helper,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->_scopeConfig = $scopeConfig;
        $this->_rateErrorFactory = $rateErrorFactory;
        $this->_logger = $logger;
        $this->_rateFactory = $rateFactory;
        $this->_carrierHelper = $carrierHelper;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_state = $state;
        $this->_helper = $helper;
    }


    /**
     * get allowed methods
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateFactory->create();

        $shippingMethod = $this->_helper->getShippingType($this->_code);

        if ($shippingMethod) {
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $rate */
            $rate = $this->_rateMethodFactory->create();
            $rate->setCarrier($this->_code);
            $rate->setCarrierTitle(self::CARRIERTITLE);
            $rate->setMethod($shippingMethod['code']);
            $rate->setMethodTitle($shippingMethod['title']);
            $rate->setCost($shippingMethod['price']);
            $rate->setPrice($shippingMethod['price']);

            $result->append($rate);
        }
        return $result;
    }
}