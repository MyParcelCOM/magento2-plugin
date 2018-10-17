<?php
namespace MyParcelCOM\Magento\Model\Sales;

use Magento\Framework\EntityManager\Operation\Delete;
use MyParcelCOM\Magento\Helper\MyParcelConfig;
use MyParcelCOM\Magento\Model\Sales\Base\MyParcelOrderCollectionBase;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use MyParcelCOM\Magento\Adapter\MpShipment;
use MyParcelCom\ApiSdk\Resources\Proxy\StatusProxy;
use MyParcelCom\ApiSdk\Resources\Shipment;
use PHPUnit\Exception;
use MyParcelCom\ApiSdk\Resources\Interfaces\FileInterface;
use MyParcelCom\ApiSdk\LabelCombiner;
use Magento\Framework\App\ObjectManager;

class MyParcelOrderCollection extends MyParcelOrderCollectionBase 
{
    const ERROR_ORDER_HAS_NO_SHIPMENT = 'error_order_has_no_shipment';
    const ERROR_SHIPMENT_CREATE_FAIL = 'error_shipment_create_fail';
    const SUCCESS_SHIPMENT_CREATED = 'success_shipment_created';

    const PATH_MODEL_ORDER = '\Magento\Sales\Model\ResourceModel\Order\Collection';

    /**
     * @var \MyParcelCOM\Magento\Helper\Order
     */
    protected $helper;

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
        $this->helper           = $this->objectManager->get('\MyParcelCOM\Magento\Helper\Order');

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
                'region_code'   => $shippingAddressObj->getRegionCode(),
                'phone_number'  => $shippingAddressObj->getTelephone(),
                'region_code' => 'ENG',
            ];

            /**
             * Weight
             **/
			$shipmentWeight = $order->getWeight();
            $shipmentData = [
                'weight'    => ($shipmentWeight > 1) ? $shipmentWeight : 1,
            ];

            /**
             * Retrieve delivery options from Order database
             **/
            $data = $order->getData('delivery_options');
            $shippingMethod = $order->getShippingMethod();

            /**
             * Add Pickup information into $shipmentData
             * @var object $data Data from checkout
             **/
            if ($this->helper->isPickupLocation($shippingMethod)) {
                $deliveryOptions = json_decode($data, true);

                if ($deliveryOptions) {

                    $pickupAddressAttribute  =   $deliveryOptions['attributes']['address'];
                    $street         =   $pickupAddressAttribute['street_1'];
                    $houseNumber    =   !empty($pickupAddressAttribute['street_number']) ? intval($pickupAddressAttribute['street_number']) : 0;
                    $postalCode     =   $pickupAddressAttribute['postal_code'];
                    $city           =   $pickupAddressAttribute['city'];
                    $country        =   $pickupAddressAttribute['country_code'];
                    $company        =   $pickupAddressAttribute['company'];
                    $phoneNumber    =   !empty($pickupAddressAttribute['phone_number']) ? $pickupAddressAttribute['phone_number'] : $addressData['phone_number'];

                    $pickupAddressData = [
                        'street'        => $street,
                        'house_number'  => $houseNumber,
                        'city'          => $city,
                        'postcode'      => $postalCode,
                        'country_code'  => $country,
                        'phone_number'  => $phoneNumber,
                        'company'       => $company,
						'region_code' => 'ENG',
                    ];

                    $pickupLocationCode = $deliveryOptions['attributes']['code'];

                    /**
                     * Get carrier id from the payload
                    **/
                    $carrier    = $deliveryOptions['relationships']['carrier']['data'];
                    $carrierId  = null;
                    if (!empty($carrier)) {
                       $carrierId = $carrier['id'];
                    }

                    $shipmentData['pickup_location'] = [
                        'address'      => $pickupAddressData,
                        'location_code'     => $pickupLocationCode,
                        'carrier_id'        => $carrierId
                    ];
                }
            }

            /**
             * Add Description
             * @var object $data Data from checkout
             **/
			//Send description to MyParcel. Please name it `storename` Order #`ordernumber`
			
			$storeName = $order->getStoreName();
			$storeName = explode ("\n", trim($storeName));
			$storeName = $storeName[(count($storeName) - 1)];
			
			$orderID = $order->getID();
			
			$description = $storeName.' Order #'.$orderID;
			 
            /**
             * Add Pickup information into $shipmentData
             * @var object $data Data from checkout
             **/
            //TODO add delivery data into $shipmentData

            try {
                $shipment = new MpShipment($this->objectManager);
                /** @var Shipment $response **/
                $registerAt = $printMode ? 'now' : '';
                $response = $shipment->createShipment($addressData, $shipmentData, $registerAt, $description);

            } catch ( \Exception $e ) {
                throw new \Exception($e->getMessage());
            }

            if (!empty($response->getId())) {
                /** @var StatusProxy $statusProxy **/
                $shipmentStatus = $response->getShipmentStatus();
                $statusProxy  = $shipmentStatus->getStatus();
                $myParcelStatus = $statusProxy->getCode();
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

                            /** @var StatusProxy $statusProxy **/
                            $shipmentStatus = $shipmentResponse->getShipmentStatus();
                            $statusProxy  = $shipmentStatus->getStatus();
                            $myParcelStatus = $statusProxy->getCode();

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

    /**
     * When print PDF for order(s), we need to check if all shipments contain the file
     * Since we cannot predict how much time it will take the carrier to answer, you might have to poll multiple times.
     * Then if all shipments contain file to print then we can print PDF
     * @return boolean
     * @throws \Exception
     */
    public function isAllOrderShipmentsAvailableToPrint()
    {
        $mpShipment = new MpShipment($this->objectManager);

        foreach ($this->getOrders() as $order) {

            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $this->myParcelTrack->getTrackByOrderId($order->getId());

            $shipmentId = $track->getData('myparcel_consignment_id');
            /** @var Shipment $shipment **/
            $shipment = $mpShipment->getShipment($shipmentId);

            if (is_a($shipment, 'MyParcelCom\ApiSdk\Resources\Shipment')) {
                $fileProxies = $shipment->getFiles(FileInterface::DOCUMENT_TYPE_LABEL);
                if (!empty($fileProxies)) {
                  return true;
                } else {
                    // Try to setRegisterAt to the shipment to change its status to registered/completed
                    if (empty($shipment->getRegisterAt())) {
                        $mpShipment->setRegisterAt($shipment, 'now');
                    }
                }
            } else {

                // Try to create shipment if it does not exist
                $this
                    ->createShipmentConcepts()
                    ->updateGridByOrder();
            }
        }

        return false;
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
}