/**
 * MyParcel customer address customized from ternado shipping
 */
define([
    'underscore',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/model/error-processor',
    'temandoCheckoutFieldsDefinition',
    'myparcelcom_checkout'
], function(_, resourceUrlManager, quote, storage, shippingService, rateRegistry, errorProcessor, fieldsDefinition, mp_checkout) {
    'use strict';

    return {
        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getRates: function(address) {
            var cache, cacheKey, serviceUrl, payload;

            // init extension attributes
            if (!address.extensionAttributes) {
                address.extensionAttributes = {};
            }
            if (!address.extensionAttributes.checkoutFields) {
                address.extensionAttributes.checkoutFields = {};
            }

            // add current checkout field configuration to extension attributes
            _.each(fieldsDefinition.getFields(), function(field) {
                address.extensionAttributes.checkoutFields[field.id] = {
                    attributeCode: field.id,
                    value: field.value
                };
            });

            /**
             * MyParcel custom code
             * **/
            mpSelectedCC = address.countryId;
            mpSelectedPC = address.postcode;

            shippingService.isLoading(true);
            cacheKey = address.getCacheKey().concat(JSON.stringify(address.extensionAttributes));
            cache = rateRegistry.get(cacheKey);
            serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote);
            payload = JSON.stringify({
                address: {
                    'street': address.street,
                    'city': address.city,
                    'region_id': address.regionId,
                    'region': address.region,
                    'country_id': address.countryId,
                    'postcode': address.postcode,
                    'email': address.email,
                    'customer_id': address.customerId,
                    'firstname': address.firstname,
                    'lastname': address.lastname,
                    'middlename': address.middlename,
                    'prefix': address.prefix,
                    'suffix': address.suffix,
                    'vat_id': address.vatId,
                    'company': address.company,
                    'telephone': address.telephone,
                    'fax': address.fax,
                    'custom_attributes': address.customAttributes,
                    'extension_attributes': address.extensionAttributes || {},
                    'save_in_address_book': address.saveInAddressBook
                }
            });

            if (cache) {
                shippingService.setShippingRates(cache);
                shippingService.isLoading(false);

                /**
                 * MyParcel get first pickup location by shipping address
                 * */
                mp_checkout.setFirstDelivery(address, cache);
                mp_checkout.setFirstLocationByAddress(address, cache);
            } else {
                storage.post(
                    serviceUrl, payload, false
                ).done(function(result) {
                    rateRegistry.set(cacheKey, result);
                    shippingService.setShippingRates(result);

                    /**
                     * MyParcel get first pickup location by shipping address
                     * */
                    mp_checkout.setFirstDelivery(address, cache);
                    mp_checkout.setFirstLocationByAddress(address, result);
                }).fail(function(response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                }).always(function() {
                    shippingService.isLoading(false);
                });
            }
        }
    };
});