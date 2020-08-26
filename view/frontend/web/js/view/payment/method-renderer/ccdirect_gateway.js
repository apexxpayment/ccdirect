define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Ui/js/model/messageList',
        'Magento_Vault/js/view/payment/vault-enabler',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $, additionalValidators,fullScreenLoader,urlBuilder,storage,modal,placeOrderAction, redirectOnSuccessAction,messageList,VaultEnabler) {
        'use strict';

        return Component.extend({

             initialize: function () {
                this._super();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                return this;
            },

            defaults: {
                redirectAfterPlaceOrder: true,
                template: 'Apexx_CcDirect/payment/form',
            },

            /** Returns send check to info */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            getCode: function() {
                return 'ccdirect_gateway';
            },


             getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'cc_cid': this.creditCardVerificationNumber()
                    }
                };
                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
               this.vaultEnabler.visitAdditionalData(data);
                return data;
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    fullScreenLoader.startLoader();
                    self.isPlaceOrderActionAllowed(false);

                    self.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                              //  alert("fbfgbfgb");
                                fullScreenLoader.stopLoader();
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function (orderId) {
                          // alert("ooooooo");
                            self.afterPlaceOrder();
                            self.getOrderPaymentStatus(orderId).done(function (responseJSON) {
                                 var response = JSON.parse(responseJSON);
                                if (response.three_ds_required) {
                                self.validateThreeDS2OrPlaceOrder(responseJSON, orderId)
                                }
                                else
                                {
                                 if (self.redirectAfterPlaceOrder) {
                                    redirectOnSuccessAction.execute();
                                    }
                                }
                            });
                           
                        }
                    );
                }
                return false;
            },

            getOrderPaymentStatus: function (orderId) {
                var serviceUrl = urlBuilder.createUrl('/apexx/orders/:orderId/payment-status', {
                    orderId: orderId
                });

                return storage.get(serviceUrl);
            },

            validateThreeDS2OrPlaceOrder: function (responseJSON, orderId) {
                var self = this;
                var response = JSON.parse(responseJSON);
                if (response.three_ds_required) {
                    var threeDSecureForm = '<form name="redirectForm" id="redirectForm" action="'+response.acsURL+'" method="POST">';
                    threeDSecureForm += '<input type="hidden" name="PaReq" id="PaReq" value="'+response.paReq+'">';
                    threeDSecureForm += '<input type="hidden" name="TermUrl" id="TermUrl" value="'+BASE_URL+'ccdirect/process/validate3d">';
                    threeDSecureForm += '<input type="hidden" name="MD" id="MD" value="'+response.psp_3d_id+'">';
                    threeDSecureForm += '<input type="submit" style ="display:none" name="submit3DsDirect" id="submit3DsDirect" value="">';
                    threeDSecureForm += '</form>';


                    console.log(threeDSecureForm);


                    var threeDForm = threeDSecureForm;


                    $('#threeDS2Wrapper').append(threeDSecureForm);

                    fullScreenLoader.stopLoader();

                    $(document).ready(function(){
                        $("#redirectForm").submit();
                        console.log("fvfvfdv");
                    });

                }
            },

            renderThreeDS2Component: function (response, orderId) {
                var self = this;
            },
            /**
             * @returns {Bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
            }
        });
    }
);
