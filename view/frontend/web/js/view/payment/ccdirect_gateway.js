define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'ccdirect_gateway',
                component: 'Apexx_CcDirect/js/view/payment/method-renderer/ccdirect_gateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
