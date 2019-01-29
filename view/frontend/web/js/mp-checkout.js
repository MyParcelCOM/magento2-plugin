/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'mage/storage',
    'myparcelcom_delivery_helper',
    'mage/translate'
], function($, storage, mpHelper, $t) {
    'use strict';

    return {
        /**
         * @param {Object} address
         * @param {Object} result
         * @return {*}
         */
        setFirstLocationByAddress: function(address, result) {
            var self = this;
            var pc = address.postcode;
            var cc = address.countryId;

            if (pc && cc && mpHelper.isCountrySupported(cc)) {

                // Show loading circle while fetching first pickup location
                mpHelper.isPickupLoading(true);

                mpAjaxCall = storage.get(
                    mpHelper.getUrlForFirstLocationByAddress(pc, cc),
                    null,
                    false
                ).done(function(response) {
                    if (Array.isArray(response)) {
                        response = response[0];

                        if (response.status === 'success') {
                            var location = response.data[0];
                            $('textarea[name="delivery_options"]').text(JSON.stringify(location));

                            var addressData = mpHelper.getPickUpSummaryAddress(location);
                            var addressHtml = '<span class="abs-add-clearfix myparcel-shipping-pickup-name">' + addressData.name + '</span><span class="abs-add-clearfix myparcel-shipping-pickup-address">' + addressData.address + '</span>';

                            $('#myparcel-shipping-pickup-closest').html(addressHtml);

                            if ($('#myparcel-shipping-carrier-name').length == 0 && response.carrier_name.length > 0) {
                                var carrierHtml = '<div id="myparcel-shipping-carrier-name" class="shipping-method-title" style="display: inline;"> / ' + response.carrier_name + '</div>';
                                $(carrierHtml).insertBefore('#myparcel-shipping-pickup-closest');
                            }

                            if ($('#myparcel-shipping-transit-time').length == 0 && response.transit_time.length > 0) {
                                var transitHtml = '<div id="myparcel-shipping-transit-time" class="shipping-method-title" style="display: inline;"> / ' + response.transit_time + '</div>';
                                $(transitHtml).insertBefore('#myparcel-shipping-pickup-closest');
                            }

                            // Hide validation error because now the pickup location is selected
                            self.setValidationMessage(false);
                        }
                    }
                }).fail(function(response) {

                }).always(function() {
                    mpHelper.isPickupLoading(false);
                });
            } else {
                // Announce that address is invalid and not supported by MyParcel
            }
        },

        setFirstDelivery: function(address, result) {
            var self = this;
            var pc = address.postcode;
            var cc = address.countryId;
            var ad = (address.street && address.street.length) > 0 ? address.street.join(',') : '';

            if (pc && cc && mpHelper.isCountrySupported(cc)) {

                // Show loading circle while fetching first delivery location
                mpHelper.isDeliveryLoading(true);

                mpAjaxCall = storage.get(
                    mpHelper.getUrlForFirstDelivery(pc, cc, ad),
                    null,
                    false
                ).done(function(response) {
                    if (Array.isArray(response)) {
                        response = response[0];

                        if (response.status === 'success') {
                            if ($('#myparcel-delivery-carrier-name').length == 0 && response.carrier_name.length > 0) {
                                var carrierHtml = '<div id="myparcel-delivery-carrier-name" class="shipping-method-title" style="display: inline;"> / ' + response.carrier_name + '</div>';
                                $(carrierHtml).insertBefore('#myparcel-shipping-delivery-closest');
                            }

                            if ($('#myparcel-delivery-transit-time').length == 0 && response.transit_time.length > 0) {
                                var transitHtml = '<div id="myparcel-delivery-transit-time" class="shipping-method-title" style="display: inline;"> / ' + response.transit_time + '</div>';
                                $(transitHtml).insertBefore('#myparcel-shipping-delivery-closest');
                            }

                            // Hide validation error because now the pickup location is selected
                            self.setValidationMessage(false);
                        }
                    }
                }).fail(function(response) {

                }).always(function() {
                    mpHelper.isDeliveryLoading(false);
                });
            } else {
                // Announce that address is invalid and not supported by MyParcel
            }
        },

        setValidationMessage: function(show) {

            var $selectedShippingMethodElem = $('.table-checkout-shipping-method tr input[type="radio"]:checked');

            if (show) {
                var errorMessageHtml = '<div class="field-error">\n' +
                    '<span data-bind="text: element.error">' + $t('checkout_validation_pickup_select_location') + '</span>\n' +
                    '</div>';

                // Display error right below the shipping method
                var $trClosest = $selectedShippingMethodElem.closest('tr');
                var $trShippingErrorElem = $trClosest.next('tr.mp-shipping-error-msg');
                var $tdShippingErrorElem = $trShippingErrorElem.find('td');

                if (!$trShippingErrorElem.length) {
                    $trClosest.after('<tr class="mp-shipping-error-msg td"><td colspan="4"></td></tr>');
                    $tdShippingErrorElem = $trClosest.next('tr.mp-shipping-error-msg').find('td');
                }

                $tdShippingErrorElem.html(
                    errorMessageHtml
                );

            } else {

                // Remove validation error when the delivery options is inputted correctly
                $trShippingErrorElem = $('tr.mp-shipping-error-msg');

                if ($trShippingErrorElem.length) {
                    $trShippingErrorElem.remove();
                }

            }
        }
    };
});