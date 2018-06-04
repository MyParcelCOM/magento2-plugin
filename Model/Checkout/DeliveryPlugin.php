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

        return [['status' => 'success', 'data' => [$location]]];
    }

    function retrieveCarriers()
    {
        $carrier = new MpCarrier();
        $carriers = $carrier->getCarriers();

        return [['data' => $carriers]];
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