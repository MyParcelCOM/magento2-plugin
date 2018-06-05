var config = {
    paths: {
        myparcelCustomerAddressRateProcessor: 'MyParcelCOM_Magento/js/model/shipping-rate-processor-customer-address',
        myparcelNewAddressRateProcessor: 'MyParcelCOM_Magento/js/model/shipping-rate-processor-new-address',
        temandoCustomerAddressRateProcessor: 'MyParcelCOM_Magento/js/model/temando/customer-address',
        temandoNewAddressRateProcessor: 'MyParcelCOM_Magento/js/model/temando/new-address'
    },
    map: {
        '*': {
            'myparcelcom_delivery': 'MyParcelCOM_Magento/js/mp-delivery',
            'myparcelcom_delivery_app': 'MyParcelCOM_Magento/js/app',
            'myparcelcom_url_helper': 'MyParcelCOM_Magento/js/mp-url-helper',
            'myparcelcom_delivery_helper': 'MyParcelCOM_Magento/js/mp-delivery-helper',
            'myparcelcom_checkout': 'MyParcelCOM_Magento/js/mp-checkout',
            'Magento_Checkout/js/model/shipping-save-processor/default' : 'MyParcelCOM_Magento/js/model/shipping-save-processor-default',
            'Magento_Checkout/js/model/shipping-rate-processor/new-address' : 'myparcelNewAddressRateProcessor',
            'Magento_Checkout/js/model/shipping-rate-processor/customer-address' : 'myparcelCustomerAddressRateProcessor'
        }
        /*'Magento_Checkout/js/model/shipping-rate-service': {
            'Magento_Checkout/js/model/shipping-rate-processor/customer-address' : 'temandoCustomerAddressRateProcessor',
            'Magento_Checkout/js/model/shipping-rate-processor/new-address' : 'temandoNewAddressRateProcessor'
        }*/
    }
};
