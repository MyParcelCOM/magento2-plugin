/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
        'jquery',
        'Magento_Checkout/js/model/url-builder',
        'myparcelcom_url_helper',
        'mageUtils'
    ], function ($, urlBuilder, mpUrlHelper, utils) {
        'use strict';

        return {
            getUrlForFirstLocationByAddress : function (pc, cc) {

                var url = mp_url_get_first_location;
                var urlParams = {
                    'postalCode' : pc,
                    'countryCode': cc
                };
                url = urlBuilder.createUrl(url, {});
                url = mpUrlHelper.addUrlParams(url, urlParams);
                return url;
            },

            getUrlForCheckShipmentFileAvailability : function (orderIds) {
                var url = mp_url_check_file_availability;

                var urlParams = {
                    'orderIds[]' : orderIds
                };

                url = urlBuilder.createUrl(url, {});
                url = mpUrlHelper.addUrlParams(url, urlParams);
                return url;
            },

            getPickUpSummaryAddress : function(pickupLocation) {

                var pickupAddress   =   pickupLocation.attributes.address;
                var street1         =   pickupAddress.street_1;
                var streetNumber    =   pickupAddress.street_number;
                var postalCode      =   pickupAddress.postal_code;
                var city            =   pickupAddress.city;
                var locationName    =   pickupAddress.company;

                var street = streetNumber ? (street1 + ' ' + streetNumber) : street1;
                
                return {
					name: locationName,
					address: street + ', ' + postalCode + ', ' + city
				};
            },

            isPickupLoading : function(show) {
                if (show) {
                    setTimeout(function () {
                        $('#myparcel-shipping-pickup-closest').addClass('mp-loader');
                    }, 200);
                } else {
                    $('#myparcel-shipping-pickup-closest').removeClass('mp-loader');
                }
            },

            isCountrySupported : function(cc) {

                if (cc === 'NL' || cc ==='GB') {
                    return true;
                }
                return false;
            }
        }
    }
);