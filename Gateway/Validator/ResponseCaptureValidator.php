<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Command\CommandException;

class ResponseCaptureValidator extends AbstractValidator
{
    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
     public function validate(array $validationSubject)
    {

        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }
        $paymentDataObjectInterface = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($validationSubject);
        $payment = $paymentDataObjectInterface->getPayment();
        $payment->setAdditionalInformation('3dActive', false);
        $response = $validationSubject['response'];
        if(isset($response['card']['card_number'])){
            $payment->setCcNumberEnc($response['card']['card_number']);
            $last4 = substr($response['card']['card_number'], -4);
            $payment->setCcLast4($last4);
            $firstSix = substr($response['card']['card_number'],0,6);
            $payment->setBin($firstSix);
        }
        if(isset($response['card']['expiry_month'])){
            $payment->setCcExpMonth($response['card']['expiry_month']);
        }
        if(isset($response['card']['expiry_year'])){
            $payment->setCcExpYear($response['card']['expiry_year']);
        }
        if(isset($response['cvv_result'])){
            $payment->setCvvResponse($response['cvv_result']);
        }
        if(isset($response['avs_result'])){
            $payment->setAvsResponse($response['avs_result']);
        }
        if (isset($response['status']))
        {
                if ($response['status'] == 'CAPTURED') {
                    return $this->createResult(
                        true,
                        []
                    );
                }
                elseif ($response['status'] == 'DECLINED') {
                throw new CommandException(__($response['reason_message']));
                        return $this->createResult(
                        false,
                        [__($response['reason_message'])]
                    );

                }
                elseif ($response['status'] == 'FAILED') {
                throw new CommandException(__($response['reason_message']));
                            return $this->createResult(
                            false,
                            [__($response['reason_message'])]
                        );
                }
                else {
                    if (isset($response['reason_message'])) {
                throw new CommandException(__($response['reason_message']));
                        return $this->createResult(
                            false,
                            [__($response['reason_message'])]
                        );
                    } else {
                        return $this->createResult(
                            false,
                            [__('Gateway rejected the transaction.')]
                        );
                    }
                }
        }

        elseif (isset($response['three_ds']) && $response['three_ds']['three_ds_required'] == true) 
        {
            $payment->setAdditionalInformation('3dActive', true);
            $redirectUrl = null;
            $paymentData = null;
            $threedresponse = $response['three_ds'];

            if (!empty($threedresponse['acsURL'])) {
                $redirectUrl = $threedresponse['acsURL'];
            }
            if(isset($response['_id'])){
            $payment->setAdditionalInformation('_id', $response['_id']);
            }
            if(isset($threedresponse['three_ds_required'])){
            $payment->setAdditionalInformation('three_ds_required', $threedresponse['three_ds_required']);
            }
            if(isset($threedresponse['acsURL'])){
            $payment->setAdditionalInformation('acsURL', $threedresponse['acsURL']);
            }
            if(isset($threedresponse['paReq'])){
            $payment->setAdditionalInformation('paReq', $threedresponse['paReq']);
            }
            if(isset($threedresponse['three_ds_enrollment'])){
            $payment->setAdditionalInformation('three_ds_enrollment', $threedresponse['three_ds_enrollment']);
            }
           if(isset($threedresponse['acq_id'])){
                $payment->setAdditionalInformation('acq_id', $threedresponse['acq_id']);
            }
            if(isset($threedresponse['psp_3d_id'])){
                $payment->setAdditionalInformation('psp_3d_id', $threedresponse['psp_3d_id']);
            }
            if(isset($threedresponse['acsURL_http_method'])){
            $payment->setAdditionalInformation('acsURL_http_method', $threedresponse['acsURL_http_method']);
            }
            if(isset($redirectUrl)){
            $payment->setAdditionalInformation('redirectUrl', $redirectUrl);
            }
            return $this->createResult(true,[]);
        }
         else
            {   
                if(isset($response['message']))
                {
                throw new CommandException(__($response['message']));
                return $this->createResult(
                            false,
                            [__($response['message'])]
                        );
                }
                else
                {
                    return $this->createResult(
                            false,
                            [__('Gateway rejected the transaction.')]
                        );
                }
            }
        return $this->createResult($isValid, $errorMessages);
    }
}
