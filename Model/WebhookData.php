<?php
namespace Apexx\CcDirect\Model;
class WebhookData implements \Apexx\CcDirect\Api\WebhookDataInterface
{

    protected $request;
    protected $orderFactory;
    protected $apexxBaseHelper;
    protected $orderSender;
    protected $ccHelper;

    /**
     * constructor
     *
     * @param \Magento\Framework\Webapi\Rest\Request $request
     */
    public function __construct(\Magento\Sales\Model\OrderFactory $orderFactory, \Magento\Framework\Webapi\Rest\Request $request, \Apexx\Base\Helper\Data $apexxBaseHelper, \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender, \Apexx\CcDirect\Helper\Data $ccHelper)
    {
        $this->orderFactory = $orderFactory;
        $this->request = $request;
        $this->apexxBaseHelper = $apexxBaseHelper;
        $this->orderSender = $orderSender;
        $this->ccHelper = $ccHelper;
    }

    /**
     * {@inheritdoc}
     */

    public function webhookData()
    {
        $response = $this
            ->request
            ->getBodyParams();
        if (isset($response['merchant_reference']))
        {
            $paymentStr = $this->apexxBaseHelper->encryptDecrypt(2, $response['merchant_reference']);
            $paymentMatch = strpos($paymentStr, 'hosted');
            if($paymentMatch === false){
                $str = $response['merchant_reference'];
            }else{
                $str = str_replace('hosted',"",$paymentStr);
            }
            $orderIncrementId = ltrim($str, $this->apexxBaseHelper->getStoreCode());
            $order = $this
                ->orderFactory
                ->create()
                ->loadByIncrementId($orderIncrementId);
            $payment = $order->getPayment();
            $method = $payment->getMethod();
            if (isset($response['status']))
            {
                if ($response['status'] == 'AUTHORISED')
                {
                    $payment->setAdditionalInformation('reason_code', $response['reason_code']);
                    if ($response['_id']) $payment->setAdditionalInformation('_id', $response['_id']);
                    if ($response['authorization_code']) $payment->setAdditionalInformation('authorization_code', $response['authorization_code']);
                    if ($response['merchant_reference']) $payment->setAdditionalInformation('merchant_reference', $response['merchant_reference']);
                    if ($response['amount']) $payment->setAdditionalInformation('amount', ($response['amount'] / 100));
                    if ($response['status']) $payment->setAdditionalInformation('status', $response['status']);
                    if ($response['card']['card_number']) $payment->setAdditionalInformation('card_number', $response['card']['card_number']);
                    // $payment->setTransactionType(self::REQUEST_TYPE_AUTH_ONLY);
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
                    $payment->setAmount(($response['amount'] / 100));
                    $payment->setCcApproval($response['authorization_code'])->setLastTransId($response['_id'])->setTransactionId($response['_id'])->setIsTransactionClosed(0)
                        ->setCcTransId($response['_id'])->setCcAvsStatus($response['avs_result'])->setCcCidStatus($response['cvv_result']);

                    $payment->setParentTransactionId('_id')
                        ->setIsTransactionClosed(0);
                    $payment->registerAuthorizationNotification($order->getBaseGrandTotal());
                    $order->setState('processing');
                    $orderStatus = strtolower($response['status']);
                    $order->setStatus($orderStatus);
                    if (!$order->getEmailSent()) {
                        $this->orderSender->send($order);
                    }
                    $order->addStatusToHistory($order->getStatus() , 'Authorised with webhook sucessfully');
                }
                elseif ($response['status'] == 'CAPTURED' && $this->ccHelper->getPaymentCatpureMode($order) == 'authorize_capture' && $method != 'hostedpayment_gateway')
                {
                    $payment->setAdditionalInformation('reason_code', $response['reason_code']);
                    if ($response['_id']) $payment->setAdditionalInformation('_id', $response['_id']);
                    if ($response['authorization_code']) $payment->setAdditionalInformation('authorization_code', $response['authorization_code']);
                    if ($response['merchant_reference']) $payment->setAdditionalInformation('merchant_reference', $response['merchant_reference']);
                    if ($response['amount']) $payment->setAdditionalInformation('amount', ($response['amount'] / 100));
                    if ($response['status']) $payment->setAdditionalInformation('status', $response['status']);
                    if ($response['card']['card_number']) $payment->setAdditionalInformation('card_number', $response['card']['card_number']);
                    //  $payment->setTransactionType('CAPTURED');
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
                    $payment->setAmount(($response['amount'] / 100));
                    $payment->setCcApproval($response['authorization_code']);
                    $payment->setRrno($payment->getParentTransactionId());

                    $payment->setTransactionId($response['_id'])->setCurrencyCode($order->getBaseCurrencyCode())
                        ->setParentTransactionId($response['_id'])->setShouldCloseParentTransaction(true)
                        ->setIsTransactionClosed(0)
                        ->registerCaptureNotification($order->getBaseGrandTotal());

                    $order->setStatus('processing');
                    $order->setState('processing');
                    if (!$order->getEmailSent()) {
                        $this->orderSender->send($order);
                    }
                    $order->addStatusToHistory($order->getStatus() , 'webhook Response : Captured sucessfully');
                }
                elseif ($response['status'] == 'DECLINED')
                {
                    $orderStatus = strtolower($response['status']);
                    $order->setStatus($orderStatus);
                }
                elseif ($response['status'] == 'FAILED')
                {
                    $orderStatus = strtolower($response['status']);
                    $order->setStatus($orderStatus);
                }
                else
                {
                    if (isset($response['reason_message']))
                    {
                        $order->addStatusToHistory($order->getStatus() , "webhook Response : " . $response['reason_message']);
                    }
                }

            }
            $order->save();

        }
    }
}