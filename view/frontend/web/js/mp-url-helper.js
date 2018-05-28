/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
        'jquery',
    ], function ($) {
        'use strict';

        return {
            addUrlParams : function(url, params) {

                var paramStr = $.param(params);
                return url + '?' + paramStr;
            }
        }

    }
);
