/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
        'jquery',
        'myparcelcom_url_helper',
        'mageUtils'
    ], function ($, mpUrlHelper, utils) {
        'use strict';

        return {
            getUrlForCheckShipmentFileAvailability : function (orderIds) {
                var url = mp_url_check_file_availability;

                var urlParams = {
                    'orderIds[]' : orderIds
                };

                url = mpUrlHelper.addUrlParams(url, urlParams);
                return url;
            },

            getPickUpSummaryAddress : function(pickupLocation) {

                var pickupAddress   =   pickupLocation.attributes.address;
                var street1         =   pickupAddress.street_1;
                var streetNumber    =   pickupAddress.street_number;
                var postalCode      =   pickupAddress.postal_code;
                var city            =   pickupAddress.city;

                return street1 + ' ' + streetNumber + ', ' + postalCode + ', ' + city;
            },
        }
    }
);
