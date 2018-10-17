define(
    [
        'jquery',
        'myparcelcom_delivery_helper',
        'myparcelcom_delivery',
        'MyParcelCOM_Magento/js/model/mp-shipping-validator',
        'myparcelcom_checkout'
    ],function ($, mpHelper, myparcelcomDelivery, mpValidator, mp_checkout) {
        'use strict';

        return function CheckoutDelivery(
            options,
            element
        ) {
            var mp_settings = {
                // Define the google_maps_key property, but don't set it.
                // We will set it from localStorage later.
                google_maps_key: options.api_key,
                // The callback we will use when a delivery was chosen.
                onSuccessCallback: function (pickupLocation) {
                    console.log('Pickup location chosen!', pickupLocation)

                    $('textarea[name="delivery_options"]').text(JSON.stringify(pickupLocation.originalData));
                    // This is the pickupLocation as it was passed in as part of the array of locations.
                    // You could use it here in the JavaScript code or pass it to your backend.

                    var addressData = mpHelper.getPickUpSummaryAddress(pickupLocation.originalData);
					var addressHtml = '<span class="abs-add-clearfix myparcel-shipping-pickup-name">' + addressData.name + '</span><span class="abs-add-clearfix myparcel-shipping-pickup-address">' + addressData.address + '</span>';

                    $('#myparcel-shipping-pickup-closest').html(addressHtml);
                },
                // The callback we will use when the delivery popup is closed without choosing a location.
                onCancelCallback: function () {
                    console.log('Delivery popup closed.')
                },
                // The callback we will use when the delivery popup asks us for
                // pickup locations to show.
                retrievePickupLocationsCallback: function (countryCode, postalCode) {
                    // Do a fetch request to our example app backend to request the locations.
                    // Give the fetch promise back to the delivery plugin.
                    return fetch(options.url_mp_get_locations + '?countryCode=' + countryCode + '&postalCode=' + postalCode)
                        .then(function (responseObject) {
                            // Let fetch know the response should be handled as JSON.
                            return responseObject.json()
                        })
                        .then(function (response) {
                            // Only return the array of locations in the data property of the JSON response.
                            if (response.length) {
                                return response[0].data
                            }
                            return [];
                        })
                },
                // The callback we will use when the delivery popup asks us for carriers.
                retrieveCarriersCallback: function () {
                    // Do a fetch request to our example app backend to request the carriers.
                    // Give the fetch promise back to the delivery plugin.
                    return fetch(options.url_mp_get_carriers)
                        .then(function (responseObject) {
                            // Let fetch know the response should be handled as JSON.
                            return responseObject.json()
                        })
                        .then(function (response) {
                            // Only return the array of carriers in the data property of the JSON response.
                            if (response.length) {
                                return response[0].data
                            }
                            return [];
                        })
                }
            };

            $( document ).ready(function() {
                $(document).on('click', '.myparcel-shipping-link', function() {

                    // Cancel pickup ajax first if there is ajax currently running
                    if (mpAjaxCall) {
                        mpAjaxCall.abort();
                        mpHelper.isPickupLoading(false);
                    }

                    const initialLocation = {
                        countryCode: mpSelectedCC ? mpSelectedCC : 'GB',
                        postalCode: mpSelectedPC ? mpSelectedPC : 'SE1 7GL'
                    };

                    // Open the delivery popup on the #delivery-window element and pass the settings.
                    window.myparcelcom.openDeliveryWindow('#delivery-window', initialLocation, mp_settings)
                });

                $(document).on('click', '#shipping-method-buttons-container .button.continue', function() {

                    var $selectedShippingMethodElem = $('.table-checkout-shipping-method tr input[type="radio"]:checked');

                    if ($selectedShippingMethodElem.val() === 'myparcelpickup_myparcelpickup' && !$.trim($("textarea[name=\"delivery_options\"]").val())) {
                        mp_checkout.setValidationMessage(true);
                        return false;
                    }

                    mp_checkout.setValidationMessage(false);
                    return true;
                });
            });
        };
    }
);