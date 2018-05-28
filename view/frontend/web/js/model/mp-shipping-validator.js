define([
    'jquery',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Ui/js/lib/validation/validator',
    'myparcelcom_checkout'
], function ($, Abstract, validator, mp_checkout) {
    'use strict';

    return Abstract.extend({

        validate: function () {
            var value   = this.value(),
                result  = validator(this.validation, value, this.validationParams),
                message = result.message,
                isValid = result.passed;

            this.error(message);
            this.bubble('error', message);

            var $selectedShippingMethodElem = $('.table-checkout-shipping-method tr input[type="radio"]:checked');

            if ($selectedShippingMethodElem.val() === 'myparcelpickup_myparcelpickup' && !$.trim($("textarea[name=\"delivery_options\"]").val())) {

                isValid = false;
                this.source.set('params.invalid', true);
                mp_checkout.setValidationMessage(true);

            } else {

                mp_checkout.setValidationMessage(false);

            }

            return {
                valid: isValid,
                target: this
            };
        }
    });
});