<?php
namespace MyParcelCOM\Magento\Model\Checkout;

use function MongoDB\BSON\toJSON;
use MyParcelCOM\Magento\Adapter\MpCarrier;
use MyParcelCOM\Magento\Adapter\MpDelivery;
use MyParcelCOM\Magento\Adapter\MpShipment;
use MyParcelCOM\Magento\Api\DeliveryPluginInterface;
use MyParcelCom\ApiSdk\Resources\Interfaces\FileInterface;
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

    function retrieveFirstPickupLocation($countryCode, $postalCode)
    {
        $delivery = new MpDelivery();
        $locations = $delivery->getLocations($countryCode, $postalCode);

        if (empty($locations)) {
            return ['status' => 'success_empty'];
        }

        $location = current($locations);
		
		$extraData = $this->retrievetPickupLocationData($location);

        return [['status' => 'success', 'data' => [$location], 'carrier_name' => $extraData['carrier_name'], 'transit_time_max' => $extraData['transit_time_max'], 'transit_time_min' => $extraData['transit_time_min']]];
    }

    function retrieveCarriers()
    {
        $carriers = $this->getCarriers();

        return [['data' => $carriers]];
    }
	
	function retrievetPickupLocationData($pickup)
	{		
		$data = array(
			'carrier_name' => '',
			'transit_time_max' => '',
			'transit_time_min' => '',
		);
		
		// carrier name
		$carriers = $this->getCarriers();
		if (count($carriers) > 0) {
			foreach ($carriers as $carrier) {
				if ($carrier->getId() == $pickup->getCarrier()->getId()) {
					$data['carrier_name'] = $carrier->getName();
					break;
				}
			}
		}else{
			$data['carrier_name'] = $pickup->getCarrier()->getName();	
		}
		
		// $data['transit_time_max'] = $pickup->getTransitTimeMax();
		// $data['transit_time_min'] = $pickup->getTransitTimeMin();
		
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
            if ($orderCollection->isAllOrderShipmentsAvailableToPrint()) {
                return [['status' => 'success', 'ready' => true]];
            }
        } catch (\Exception $e) {
            return [['status' => 'failure']];
        }

        return [['status' => 'success', 'ready' => false]];
    }
}