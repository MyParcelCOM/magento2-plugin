<?php
/**
 * The delivery_settings interface
 *
 * If you want to add improvements, please create a fork in our GitHub:
 * https://github.com/MyParcelCOM
 *
 * @author      Reindert Vetter <reindert@myparcel.nl>
 * @copyright   2010-2017 MyParcel
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US  CC BY-NC-ND 3.0 NL
 * @link        https://github.com/MyParcelCOM/magento
 * @since       File available since Release v2.0.0
 */

namespace MyParcelCOM\Magento\Api;


/**
 * Get delivery settings
 */
Interface DeliveryPluginInterface
{
    /**
     * Retrieve locations data according to provided $countryCode and $postalCode
     * @param string $countryCode
     * @param string $postalCode
     *
     * @api
     * @return array All Locations
     */
    public function retrievePickupLocations($countryCode, $postalCode);

    /**
     * Retrieve first location returned from the data according to provided $countryCode and $postalCode
     * @param string $countryCode
     * @param string $postalCode
     *
     * @api
     * @return array All Locations
     */
    public function retrieveFirstPickupLocation($countryCode, $postalCode);

    /**
     * Retrieve carriers data
     * @api
     * @return array All Carriers
     */
    public function retrieveCarriers();

    /**
     * Check if shipment is ready to print label
     * @param mixed $orderIds
     * @api
     * @return boolean
     */
    public function checkShipmentAvailableForPDF($orderIds);

}