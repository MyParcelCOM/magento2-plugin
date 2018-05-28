<?php
namespace MyParcelCOM\Magento\Controller\CodeTest;

use Braintree\Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use MyParcelCOM\Magento\Adapter\MpDelivery;
use MyParcelCOM\Magento\Adapter\MpShipment;
use MyParcelCOM\Magento\Helper\MyParcelConfig;
use MyParcelCom\ApiSdk\MyParcelComApi;
use MyParcelCom\ApiSdk\Resources\Proxy\FileStreamProxy;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCom\ApiSdk\Resources\Address;
use \MyParcelCom\ApiSdk\Authentication\ClientCredentials;
use MyParcelCom\ApiSdk\Resources\Proxy\FileProxy;
use MyParcelCom\ApiSdk\Resources\Interfaces\FileInterface;
use MyParcelCom\ApiSdk\LabelCombiner;
use \Magento\Framework\App\AreaList;
use Magento\Framework\App\ObjectManager;
use MyParcelCOM\Magento\Model\Sales\MyParcelOrderCollection;

class Index extends Action {

    protected $resultPageFactory;
    protected $helper;
    private $orderCollection;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_objectManager = $context->getObjectManager();
        $this->helper = $this->_objectManager->get('\MyParcelCOM\Magento\Helper\MyParcelConfig');

        $this->orderCollection = new MyParcelOrderCollection(
            $context->getObjectManager(),
            $this->getRequest()
        );
        parent::__construct($context);
    }

    /**
     * @param $orderId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exportOrder($orderId)
    {
        $this->addOrdersToCollection([$orderId]);

        $this->orderCollection
            ->setNewMagentoShipment();

        if (!$this->orderCollection->hasShipment()) {
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_ORDER_HAS_NO_SHIPMENT));
            return $this;
        }

        try {
            $this->orderCollection
                ->setMagentoTrack()
                ->createShipmentConcepts()
                ->updateGridByOrder();

            $this->orderCollection
                ->downloadPdfOfLabels();

        } catch (\Throwable  $e) {
            var_dump($e->getMessage());die;
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_SHIPMENT_CREATE_FAIL . ': ' . $e->getMessage()));
            return $this;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_SHIPMENT_CREATE_FAIL . ': ' . $e->getMessage()));
        }
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /**
         * Set registerAt
        **/
        /*$shipmentId = '63bfa1e3-9130-47a0-9e4c-483c5fc2bdb9';
        $mpShipment = new MpShipment($this->_objectManager);
        $shipment = $mpShipment->getShipment($shipmentId);
        $result = $mpShipment->setRegisterAt($shipment, 'now');*/


        /**
         * Print PDF - test service contract
        **/
        $orderCollection = new MyParcelOrderCollection(
            $this->_objectManager,
            $this->getRequest()
        );

        $orderIds = [15];
        $orderCollection->addOrdersToCollection($orderIds);

        try {
            $orderCollection
                ->setMagentoTrack()
                ->createShipmentConcepts(true)
                ->updateGridByOrder()
                ->downloadPdfOfLabels();

        } catch (\Throwable  $e) {
            var_dump($e->getMessage());
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
        die('---');
        //$this->exportOrder(15);die;
        //$cf = $this->helper->getGeneralConfig('myparcel_group_google/googlemap_api_key', '');
        //var_dump($cf);die;
        //$delivery = new MpDelivery();
        //$locations = $delivery->getLocations('NL', '2131BC');
        //var_dump($locations);
        //die('done');
           //$updateStatus = new \MyParcelCOM\Magento\Cron\UpdateStatus();
           //$updateStatus->execute();
           //exit;
        // force token refresh (to get a token with new ACL scopes)
        $authenticator = new \MyParcelCom\ApiSdk\Authentication\ClientCredentials('f20ab545-93df-492e-824c-6ae4e5e236fb',  'F1C7hVQPLZMO2toWHrwYAwDNPQq104CvxOPkD8QISNkPSDXVSXk3lxZIBsRx9Odq', 'https://sandbox-auth.myparcel.com');
        $authenticator->getAuthorizationHeader(true);
        // force cache refresh (to get a new list of services)
        $cache = new \Symfony\Component\Cache\Simple\FilesystemCache('myparcelcom');
        $cache->prune();

        MyParcelComApi::createSingleton(
            new ClientCredentials(
                'f20ab545-93df-492e-824c-6ae4e5e236fb',
                'F1C7hVQPLZMO2toWHrwYAwDNPQq104CvxOPkD8QISNkPSDXVSXk3lxZIBsRx9Odq',
                'https://sandbox-auth.myparcel.com'
            ),
            'https://sandbox-api.myparcel.com'
        );

        $shipment = new MpShipment($this->_objectManager);
        $shipmentA = $shipment->getShipment('553a661c-4cfb-4a92-b05f-74b7c5306b78');
        $shipmentB = $shipment->getShipment('c136767a-5923-4ec6-b4c5-4330ba0521f3');

        $fileProxiesA = $shipmentA->getFiles(FileInterface::DOCUMENT_TYPE_LABEL);
        $fileProxiesB = $shipmentB->getFiles(FileInterface::DOCUMENT_TYPE_LABEL);

        $fileProxies = array_merge($fileProxiesA, $fileProxiesB);

        $files = [];

        /** @var FileProxy $fileProxy **/
        foreach ($fileProxies as $fileProxy) {

            /**var Array{["mime_type"]=> string(9) "image/png" ["extension"]=> string(3) "png"} $format*/
            $format     = current($fileProxy->getFormats());
            $mimeType   = $format['mime_type'];
            $ext        = $format['extension'];

            if ($mimeType != 'application/pdf') {
                continue;
            }

            $files[] = $fileProxy;

            /** @var FileStreamProxy $fileStreamProxy **/
            /*$fileStreamProxy    = $fileProxy->getStream();
            $fileStreamProxy->rewind();
            $fileStreamContent  = $fileStreamProxy->getContents();
            $filePath           = $fileProxy->getTemporaryFilePath();

            header("Content-Type:application/pdf");
            header("Content-Disposition:attachment;filename='downloaded.pdf'");
            echo $fileStreamContent;
            exit;*/
        }

        $labelCombiner = new LabelCombiner();
        $combinedFile = $labelCombiner->combineLabels($fileProxies);

        $fileStreamContent = $combinedFile->getBase64Data();

        //$fileStreamProxy->rewind();
        //$fileStreamContent  = $fileStreamProxy->getContents();
        header("Content-Type:application/pdf");
        header("Content-Disposition:attachment;filename='downloaded.pdf'");
        echo base64_decode($fileStreamContent);
        exit;

        // $base64 is an empty string
        // $fileStreamContent is an empty string

        die();
        /*$recipient = new Address();
        $recipient->setStreet1('Street name')
            ->setCity('City name')
            ->setPostalCode('Postal code')
            ->setFirstName('First name')
            ->setLastName('Last name')
            ->setCountryCode('NL')
            ->setEmail('email@example.com');

        $shipment = new Shipment();
        $shipment
            ->setRecipientAddress($recipient) // make sure the country code is NL, to get available services
            ->setWeight(0, Shipment::WEIGHT_GRAM);

        $api = MyParcelComApi::getSingleton();
        $response = $api->createShipment($shipment);
        var_dump($response->getId());die('123');*/
    }

    /**
     * @param $orderIds int[]
     */
    private function addOrdersToCollection($orderIds)
    {
        /**
         * @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection
         */
        $collection = $this->_objectManager->get('\Magento\Sales\Model\ResourceModel\Order\Collection');
        $collection->addAttributeToFilter('entity_id', ['in' => $orderIds]);
        $this->orderCollection->setOrderCollection($collection);
    }
}
