/**
 * See LICENSE for license details.
 */
define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Ui/js/model/messageList'
], function ($,VaultComponent,redirectOnSuccessAction,messageList) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Apexx_CcDirect/payment/vault-form'
        },
         isCvvEnabled: function () {
           if(window.checkoutConfig.payment.ccdirect_gateway.recurring_type === 'first')
           {
            console.log("inCondition");
           return true ;
            }

        },
         getCode: function () {
                return 'ccdirect_cc_vault';
            },
        getData: function () {
                var data = {
                    method: this.getCode()
                };

                data['additional_data'] = {};
                data['additional_data']['public_hash'] = this.getToken();
                data['additional_data']['vault_cvv'] = $('input[data-apexx="vault_cvv"]').val();
                data['additional_data']['customer_id']= window.checkoutConfig.customerData.id;

                return data;
            },
           
            placeOrder: function (data, event) {
                    var self = this;
                    var vault_cvv =  $('input[data-apexx="vault_cvv"]').val();

                    if (event) {
                        event.preventDefault();
                    }

                   if ( vault_cvv !== '') {
                    this.isPlaceOrderActionAllowed(false);
                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                            function () {
                                self.afterPlaceOrder();

                                if (self.redirectAfterPlaceOrder) {
                                    redirectOnSuccessAction.execute();
                                }
                            }
                        );
                     return true;
                }
                else
                {
                     self.showError("Please enter CVV number in this field.");
                     return false;
                }
            },

             /**
             * Show error message
             * @param {String} errorMessage
             */
            showError: function (errorMessage) {
                messageList.addErrorMessage({
                    message: errorMessage
                });
            },
        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        /**
         * Get public hash
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        }
      

    });
});


