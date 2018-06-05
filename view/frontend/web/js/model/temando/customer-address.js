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
], function (_, resourceUrlManager, quote, storage, shippingService, rateRegistry, errorProcessor, fieldsDefinition, mp_checkout) {
    'use strict';

    return {
        /**
         * @param {Object} address
         */
        getRates: function (address) {
            var cache,
                cacheKey;

            if (!address.extensionAttributes) {
                address.extensionAttributes = {};
            }
            if (!address.extensionAttributes.checkoutFields) {
                address.extensionAttributes.checkoutFields = {};
            }

            _.each(fieldsDefinition.getFields(), function (field) {
                address.extensionAttributes.checkoutFields[field.id] = {
                    attributeCode: field.id,
                    value: field.value
                };
            });

            cacheKey = address.getCacheKey().concat(JSON.stringify(address.extensionAttributes));

            shippingService.isLoading(true);
            cache = rateRegistry.get(cacheKey);

            /**
             * MyParcel custom code
             * **/
            mpSelectedCC = address.countryId;
            mpSelectedPC = address.postcode;


            if (cache) {
                shippingService.setShippingRates(cache);
                shippingService.isLoading(false);

                /**
                 * MyParcel get first pickup location by shipping address
                 * */
                mp_checkout.setFirstLocationByAddress(address, cache);
            } else {
                storage.post(
                    resourceUrlManager.getUrlForEstimationShippingMethodsByAddressId(quote),
                    JSON.stringify({
                        addressId: address.customerAddressId,
                        extensionAttributes: address.extensionAttributes || {},
                    }),
                    false
                ).done(function (result) {
                    rateRegistry.set(cacheKey, result);
                    shippingService.setShippingRates(result);

                    /**
                     * MyParcel get first pickup location by shipping address
                     * */
                    mp_checkout.setFirstLocationByAddress(address, result);
                }).fail(function (response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                }).always(function () {
                    shippingService.isLoading(false);
                }
                );
            }
        }
    };
});
