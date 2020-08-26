<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CaptureHandler implements HandlerInterface
{
    const TXN_ID = '_id';

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        /** @var $payment \Magento\Sales\Model\Order\Payment */
      //  $payment->setTransactionId($response['_id']);

         if(isset($response['status']))
        {
            if($response['status'] == 'CAPTURED')
            {
              if ($payment->getLastTransId() == '') {
                if(isset($response['reason_code'])){
                $payment->setAdditionalInformation('reason_code', $response['reason_code']);
                }
                if(isset($response['_id']))
                {
                $payment->setAdditionalInformation('_id', $response['_id']);
                }
                if(isset($response['authorization_code']))
                {
                $payment->setAdditionalInformation('authorization_code', $response['authorization_code']);
                }
                if(isset($response['merchant_reference']))
                {
                $payment->setAdditionalInformation('merchant_reference', $response['merchant_reference']);
                }
                if(isset($response['status']))
                {
                $payment->setAdditionalInformation('status', $response['status']);
                }
                if(isset($response['amount']))
                {
                $payment->setAdditionalInformation('amount', ($response['amount']/100));
                }
                if(isset($response['card']['card_number']))
                {
                $payment->setAdditionalInformation('CardNumber', $response['card']['card_number']);
                }
                if(isset($response['card']['expiry_month'])){
                $payment->setAdditionalInformation('expiry_month', $response['card']['expiry_month']);
                }
                if(isset($response['card']['expiry_year'])){
                $payment->setAdditionalInformation('expiry_year', $response['card']['expiry_year']);
                }
                }
          $payment->setTransactionId($response[self::TXN_ID]);
          $payment->setIsTransactionClosed(false);
          $payment->setTransactionAdditionalInfo('raw_details_info',$response);
            }
        }
          /** @var $payment \Magento\Sales\Model\Order\Payment */
    }
}
