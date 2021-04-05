<?php
namespace MyParcelCOM\Magento\Model\Sales;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MyParcelCom\ApiSdk\LabelCombiner;
use MyParcelCom\ApiSdk\Resources\Interfaces\FileInterface;
use MyParcelCom\ApiSdk\Resources\Proxy\StatusProxy;
use MyParcelCom\ApiSdk\Resources\Shipment;
use MyParcelCOM\Magento\Adapter\MpShipment;
use MyParcelCOM\Magento\Helper\MyParcelConfig;
use MyParcelCOM\Magento\Model\Sales\Base\MyParcelOrderCollectionBase;

class MyParcelOrderCollection extends MyParcelOrderCollectionBase
{
    const ERROR_ORDER_HAS_NO_SHIPMENT = 'error_order_has_no_shipment';
    const ERROR_SHIPMENT_CREATE_FAIL = 'error_shipment_create_fail';
    const SUCCESS_SHIPMENT_CREATED = 'success_shipment_created';

    const PATH_MODEL_ORDER = '\Magento\Sales\Model\ResourceModel\Order\Collection';

    /**
     * Set Magento collection
     *
     * @param $orderCollection \Magento\Sales\Model\ResourceModel\Order\Collection
     *
     * @return $this
     */
    public function setOrderCollection($orderCollection)
    {
        $this->_orders = $orderCollection;

        return $this;
    }

    /**
     * Set existing or create new Magento Track and set API consignment to collection
     *
     * @throws LocalizedException
     */
    public function setNewMagentoShipment()
    {
        /** @var $order Order */
        /** @var Order\Shipment $shipment */
        foreach ($this->getOrders() as $order) {
            if ($order->canShip() && !$order->hasShipments()) {
                $this->createShipment($order);
            }
        }

        $this->getOrders()->save();

        $this->refreshOrdersCollection();

        return $this;
    }

    /**
     * Create new Magento Track and save order
     * @param boolean $printMode
     *
     * @return $this
     * @throws \Exception
     */
    public function setMagentoTrack($printMode = false)
    {
        /**
         * @var Order          $order
         * @var Order\Shipment $shipment
         */
        foreach ($this->getShipmentsCollection() as $shipment) {

            $forceNewTrack = intval($this->configHelper->get(MyParcelConfig::GENERAL_SHIPMENT_CREATE_NEW_ONE_EXISTS));

            if (!$this->shipmentHasTrack($shipment) || ($forceNewTrack == MyParcelConfig::OPTION_YES && !$printMode)) {
                /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
                $track = $this->setNewMagentoTrack($shipment);
            } else {
                $track = $shipment->getTracksCollection()->getLastItem();
            }

            $this->myParcelTrack->addTrack($track, $shipment->getOrderId());
        }

        $this->getOrders()->save();

        return $this;
    }

    /**
     * @param bool $printMode
     * @return $this
     * @throws \Exception
     */
    public function createShipmentConcepts($printMode = false)
    {
        $this->objectManager    = ObjectManager::getInstance();

        $orders = $this->getOrders();

        /**@var \Magento\Sales\Model\Order $order **/

        foreach ($orders as $order) {
            /**
             * If this is printPDF mode, we don't need to create new shipment
             * for an order that has already been exported
             **/
            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $this->myParcelTrack->getTrackByOrderId($order->getId());

            $shipmentId = $track->getData('myparcel_consignment_id');
            if ($printMode && !empty($shipmentId)) {
                continue;
            }

            /**@var \Magento\Sales\Model\Order\Address $shippingAddressObj * */
            $shippingAddressObj = $order->getShippingAddress();
            $streets = $shippingAddressObj->getStreet();
            $street1 = $street2 = $street3 = $street4 = '';

            if (is_array($streets)) {
                foreach ($streets as $key => $streetValue) {
                    $streetValue = trim($streetValue, ',');
                    ${'street' . ($key + 1)} = $streetValue;
                }
            }

            $addressData = [
                'street' => $street1,
                'house_number' => 0,
                'city' => $shippingAddressObj->getCity(),
                'postcode' => $shippingAddressObj->getPostcode(),
                'first_name' => $shippingAddressObj->getFirstname(),
                'last_name' => $shippingAddressObj->getLastname(),
                'country_code' => $shippingAddressObj->getCountryId(),
                'email' => $shippingAddressObj->getEmail(),
                'phone_number'  => $shippingAddressObj->getTelephone(),
            ];

            /**
             * Weight
             **/
            $shipmentWeight = $order->getWeight();
            $unitWeight = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('general/locale/weight_unit');

            switch ($unitWeight) {
                case 'lbs':
                    $weightInGrams = (int) round($shipmentWeight * 100 * 0.45359237);
                    break;

                case 'kgs':
                default:
                    $weightInGrams = (int) round($shipmentWeight * 100);
            }

            $shipmentData = [
                'weight' => $weightInGrams,
            ];

            /**
             * get current currency code
             */
            $priceCurrency = $this->objectManager->get('\Magento\Framework\Pricing\PriceCurrencyInterface');
            $currencyCode = $priceCurrency->getCurrency()->getCurrencyCode();

            /**
             * retrive default hs code and default origin country code
             */
            $myparcelExportSetting = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('myparcel_section_general/myparcel_group_setting');

            $defaultHsCode = $myparcelExportSetting['default_hs_code'];
            $defaultOriginCountryCode = $myparcelExportSetting['default_origin_coutry_code'];

            /**
             * Retrieve items options from Order database
             **/
            $items = [];
            $orderItems = $order->getItems();
            foreach ($orderItems as $orderItem){
                $product = $this->objectManager->get('Magento\Catalog\Model\Product')->load($orderItem->getProductId());
                $item = array(
                    'sku' => $product->getData('sku'),
                    'description' => $product->getData('name'),
                    'item_value' => array(
                        'amount' => ($orderItem->getPrice() > 0) ? $orderItem->getPrice() : 1,
                        'currency' => $currencyCode
                    ),
                    'quantity' => intval($orderItem->getData('qty_ordered')),
                    'hs_code' => (!empty($product->getData('hs_code'))) ? $product->getData('hs_code') : $defaultHsCode,
                    'origin_country_code' => (!empty($product->getData('origin_country_code'))) ? $product->getData('origin_country_code') : $defaultOriginCountryCode,
                    'nett_weight' => ($unitWeight == 'lbs') ? intval($orderItem->getData('weight') * 0.45359237) : intval($orderItem->getData('weight'))
                );
                $items[] = $item;
            }

            /**
             * Retrieve customs options from setting
             **/
            $customs = array(
                "content_type" => $myparcelExportSetting['content_type'],
                "invoice_number" => '#' . $order->getId(),
                "non_delivery" => $myparcelExportSetting['non_delivery'],
                "incoterm" => $myparcelExportSetting['incoterm']
            );

            /**
             * Add Description
             **/
            //Send description to MyParcel. Please name it `storename` Order #`ordernumber`

            $storeName = $order->getStoreName();
            $storeName = explode ("\n", trim($storeName));
            $storeName = $storeName[(count($storeName) - 1)];

            $orderID = $order->getIncrementId();

            $description = $storeName.' Order #'.$orderID;

            try {
                $shipment = new MpShipment($this->objectManager);
                /** @var Shipment $response **/
                $registerAt = $printMode ? 'now' : '';
                $response = $shipment->createShipment($addressData, $shipmentData, $registerAt, $description, $items, $customs);
            } catch ( \Exception $e ) {
                throw new \Exception($e->getMessage());
            }

            if (!empty($response->getId())) {
                $shipmentStatus = $response->getShipmentStatus();
                $myParcelStatus = $shipmentStatus->getStatus()->getCode();
                $myparcelShipmentId = $response->getId();

                $track
                    ->setData('myparcel_consignment_id', $myparcelShipmentId)
                    ->setData('myparcel_status', $myParcelStatus)
                    ->save();
            }
        }

        return $this;
    }

    /**
     * Update column track_status in sales_order_grid
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateGridByOrder()
    {
        if (empty($this->getOrders())) {
            throw new LocalizedException(__('MagentoOrderCollection::order array is empty'));
        }

        /**
         * @var Order $order
         */
        foreach ($this->getOrders() as $order) {
            $aHtml = $this->myParcelTrack->getHtmlForGridColumns($order->getId());

            if ($aHtml['track_status']) {
                $order->setData('track_status', $aHtml['track_status']);
            }
            if ($aHtml['track_number']) {
                $order->setData('track_number', $aHtml['track_number']);
            }
        }
        $this->getOrders()->save();

        return $this;
    }

    /**
     * Download PDF directly
     *
     * @return $this
     * @throws \Exception
     */
    public function downloadPdfOfLabels()
    {
        /**
         * @var Order $order
         */
        $mpShipment = new MpShipment($this->objectManager);
        $files    = array();

        foreach ($this->getOrders() as $order) {

            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $this->myParcelTrack->getTrackByOrderId($order->getId());
            $shipmentId = $track->getData('myparcel_consignment_id');
            /** @var Shipment $shipment **/
            $shipment = $mpShipment->getShipment($shipmentId);

            if (is_a($shipment, 'MyParcelCom\ApiSdk\Resources\Shipment')) {
                $fileProxies = $shipment->getFiles(FileInterface::DOCUMENT_TYPE_LABEL);
                $files = array_merge($files, $fileProxies);
            }
        }

        if (!count($files)) {
            throw new \Exception(__('No shipments have been registered yet'));
        }

        $pageSize = $this->request->getParam('mypa_paper_size', $this->configHelper->getGeneralConfig('myparcel_group_print/paper_type', LabelCombiner::PAGE_SIZE_A4));
        $startLocation = $this->request->getParam('mypa_position_' . strtolower($pageSize), LabelCombiner::LOCATION_TOP_LEFT);

        $labelCombiner = new LabelCombiner();
        $combinedFile = $labelCombiner->combineLabels($files, $pageSize, $startLocation);
        $fileStreamContent = $combinedFile->getBase64Data();

        $fileName = 'myparcel-label-' . date('Y-M-d H-i-s') . '.pdf';

        header("Content-Type:application/pdf");
        header("Content-Disposition:attachment;filename=\"" . $fileName . "\"");
        echo base64_decode($fileStreamContent);
        exit;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function updateTrackStatus()
    {
        /**@var \Magento\Sales\Model\Order $order **/
        foreach ($this->getOrders() as $order) {
            /** @var Order\Shipment $shipment **/
            foreach ($order->getShipmentsCollection() as $shipment) {
                $tracks = $shipment->getTracksCollection();

                /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
                foreach ($tracks as $track) {
                    $shipmentId = $track->getData('myparcel_consignment_id');
                    if (!empty($shipmentId)) {
                        try {
                            $mpShipment = new MpShipment($this->objectManager);
                            /** @var Shipment $shipmentResponse **/
                            $shipmentResponse = $mpShipment->getShipment($shipmentId);
                        } catch ( \Exception $e ) {
                            throw new \Exception($e->getMessage());
                        }

                        if (!empty($shipmentResponse->getId())) {
                            $myparcelShipmentId = $shipmentResponse->getId();
                            $barcode = $shipmentResponse->getBarcode();

                            $shipmentStatus = $shipmentResponse->getShipmentStatus();
                            $myParcelStatus = $shipmentStatus->getStatus()->getCode();

                            $trackNumber        = !empty($barcode) ? $barcode : '';

                            $track
                                ->setData('myparcel_consignment_id', $myparcelShipmentId)
                                ->setData('myparcel_status', $myParcelStatus);

                            if (($myParcelStatus == MyParcelTrack::STATUS_REGISTERED || $myParcelStatus == MyParcelTrack::STATUS_COMPLETED) && $trackNumber) {
                                $track->setData('track_number', $trackNumber);
                            } else {
                                $track->setData('track_number', MyParcelTrack::TRACK_NUMBER_DEFAULT);
                            }

                            $track->save();
                        }

                    }
                }
            }
        }

        return $this;
    }

    public function refreshOrdersCollection()
    {
        $orderIds = [];
        /**@var \Magento\Sales\Model\Order $order **/
        foreach ($this->getOrders() as $order) {
            $orderIds[] = $order->getId();
        }
        $this->getOrders()->clear();
        return $this->addOrdersToCollection($orderIds);
    }

    /**
     * @param $orderIds int[]
     * @return $this
     */
    public function addOrdersToCollection($orderIds)
    {
        /**
         * @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection
         */
        $collection = $this->objectManager->get('\Magento\Sales\Model\ResourceModel\Order\Collection');
        $collection->addAttributeToFilter('entity_id', ['in' => $orderIds]);
        $this->setOrderCollection($collection);
        return $this;
    }

    /**
     * @return $orderIncrementId string[]
     */
    public function getIncrementIds(){
        $this->objectManager    = ObjectManager::getInstance();

        $orders = $this->getOrders();
        $orderIncrementId = [];
        $orders = $this->getOrders();
        foreach ( $orders as $order){
            $orderIncrementId[] = '#' .$order->getIncrementId();
        }
        return $orderIncrementId;
    }
}
