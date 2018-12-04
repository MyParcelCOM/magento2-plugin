<?php
namespace MyParcelCOM\Magento\Model\Checkout;

use function MongoDB\BSON\toJSON;
use MyParcelCOM\Magento\Adapter\MpCarrier;
use MyParcelCOM\Magento\Adapter\MpDelivery;
use MyParcelCOM\Magento\Adapter\MpService;
use MyParcelCOM\Magento\Adapter\MpShipment;
use MyParcelCOM\Magento\Api\DeliveryPluginInterface;
use MyParcelCom\ApiSdk\Resources\Interfaces\FileInterface;
use MyParcelCom\ApiSdk\Resources\Shipment;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\ObjectManager;
use MyParcelCOM\Magento\Model\Sales\MyParcelOrderCollection;

/**
 * Class DeliveryPlugin
 * @package MyParcelCOM\Magento\Model\Checkout
 */
class DeliveryPlugin implements DeliveryPluginInterface
{
    private $objectManager;
    private $extensibleDataObjectConverter;
    private $frontUrlModel;
    /**
     * DeliveryPlugin constructor.
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(ExtensibleDataObjectConverter $extensibleDataObjectConverter)
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->frontUrlModel = $this->objectManager->get('Magento\Framework\UrlInterface');
    }

    function retrievePickupLocations($countryCode, $postalCode)
    {
        $delivery = new MpDelivery();
        $locations = $delivery->getLocations($countryCode, $postalCode);

        return [['data' => $locations]];
    }

    function retrieveFirstDelivery($countryCode, $postalCode)
    {
        $delivery = new MpDelivery();
        $locations = $delivery->getLocations($countryCode, $postalCode, '');

        if (empty($locations)) {
            return ['status' => 'success_empty'];
        }

        $location = current($locations);
		
		$extraData = $this->retrieveLocationData($location);

        return [['status' => 'success', 'data' => [$location], 'carrier_name' => $extraData['carrier_name'], 'transit_time' => $extraData['transit_time'],]];
    }

    function retrieveFirstPickupLocation($countryCode, $postalCode)
    {
        $delivery = new MpDelivery();
        $locations = $delivery->getLocations($countryCode, $postalCode);

        if (empty($locations)) {
            return ['status' => 'success_empty'];
        }

        $location = current($locations);
		
		$extraData = $this->retrieveLocationData($location);

        return [['status' => 'success', 'data' => [$location], 'carrier_name' => $extraData['carrier_name'], 'transit_time' => $extraData['transit_time'],]];
    }

    function retrieveCarriers()
    {
        $carriers = $this->getCarriers();

        return [['data' => $carriers]];
    }
	
	function retrieveLocationData($pickup)
	{		
		$data = array(
			'carrier_name' => '',
			'transit_time' => '',
			'transit_time_arg' => array(),
		);
		
		// carrier name
		$carriers = $this->getCarriers();
		$carrierService = false;
		
		if (count($carriers) > 0) {
			foreach ($carriers as $carrier) {
				if ($carrier->getId() == $pickup->getCarrier()->getId()) {
					$carrierService = $carrier;
					$data['carrier_name'] = $carrier->getName();
					break;
				}
			}
		}else{
			$data['carrier_name'] = $pickup->getCarrier()->getName();	
		}
		
		// transit time
		$address = $pickup->getAddress();
		$shipment = new Shipment();
		$shipment->setRecipientAddress($address)->setWeight(500);
		
		$service = new MpService();
		$services = $service->getService($shipment);
		
		if (count($services) > 0) {
			foreach ($services as $service) {
				$transMin = $service->getTransitTimeMin();
				$transMax = $service->getTransitTimeMax();
				
				if ($transMin > 0)
					$data['transit_time_arg'][] = $transMin;
				
				if ($transMax > 0)
					$data['transit_time_arg'][] = $transMax;
				
				break;
			}
			
			if (count($data['transit_time_arg']) > 0) {
				$data['transit_time'] = implode(' - ', $data['transit_time_arg']).' '.__('days');
			}
		} 
		
		return $data;
	}
	
	function getCarriers()
	{
		try {
			$carrier = new MpCarrier();
			
			return $carrier->getCarriers();
        } catch (\Exception $e) {
            return [];
        }
	}	

    /**
     * @param mixed $orderIds
     * @return array|bool
     * @throws \Exception
     */
    public function checkShipmentAvailableForPDF($orderIds)
    {
        $orderCollection = new MyParcelOrderCollection(
            $this->objectManager,
            []
        );

        $orderCollection
            ->addOrdersToCollection($orderIds)
            ->setNewMagentoShipment()
            ->setMagentoTrack(true)
            ->createShipmentConcepts(true)
            ->updateGridByOrder();

        try {
            if ($result = $orderCollection->isAllOrderShipmentsAvailableToPrint()) {
                return [['status' => 'success', 'ready' => true]];
            }else{
                return [['status' => 'failure', 'data' => $result]];
            }
        } catch (\Exception $e) {
            return [['status' => 'failure']];
        }

        return [['status' => 'success', 'ready' => false]];
    }
}