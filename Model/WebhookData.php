<?php
namespace Apexx\CcDirect\Model;
class WebhookData implements \Apexx\CcDirect\Api\WebhookDataInterface
{

    protected $request;
    protected $orderFactory;

    /**
     * constructor
     *
     * @param \Magento\Framework\Webapi\Rest\Request $request
     */
    public function __construct(\Magento\Sales\Model\OrderFactory $orderFactory, \Magento\Framework\Webapi\Rest\Request $request)
    {
        $this->orderFactory = $orderFactory;
        $this->request = $request;
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
            $str = $response['merchant_reference'];
            $orderIncrementId = ltrim($str, "JOURNEYBOX");
            $order = $this
                ->orderFactory
                ->create()
                ->loadByIncrementId($orderIncrementId);
            $payment = $order->getPayment();
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
                    $payment->setAmount(($response['amount'] / 100));
                    $payment->setCcApproval($response['authorization_code'])->setLastTransId($response['_id'])->setTransactionId($response['_id'])->setIsTransactionClosed(0)
                        ->setCcTransId($response['_id'])->setCcAvsStatus($response['avs_result'])->setCcCidStatus($response['cvv_result']);

                    $payment->setParentTransactionId('_id')
                        ->setIsTransactionClosed(0);
                    $payment->registerAuthorizationNotification($order->getBaseGrandTotal());
                    $order->setState('processing');
                    $orderStatus = strtolower($response['status']);
                    $order->setStatus($orderStatus);
                    $order->addStatusToHistory($order->getStatus() , 'Authorised with webhook sucessfully');
                }
                elseif ($response['status'] == 'CAPTURED')
                {
                    $payment->setAdditionalInformation('reason_code', $response['reason_code']);
                    if ($response['_id']) $payment->setAdditionalInformation('_id', $response['_id']);
                    if ($response['authorization_code']) $payment->setAdditionalInformation('authorization_code', $response['authorization_code']);
                    if ($response['merchant_reference']) $payment->setAdditionalInformation('merchant_reference', $response['merchant_reference']);
                    if ($response['amount']) $payment->setAdditionalInformation('amount', ($response['amount'] / 100));
                    if ($response['status']) $payment->setAdditionalInformation('status', $response['status']);
                    if ($response['card']['card_number']) $payment->setAdditionalInformation('card_number', $response['card']['card_number']);
                    //  $payment->setTransactionType('CAPTURED');
                    $payment->setAmount(($response['amount'] / 100));
                    $payment->setCcApproval($response['authorization_code']);
                    $payment->setRrno($payment->getParentTransactionId());

                    $payment->setTransactionId($response['_id'])->setCurrencyCode($order->getBaseCurrencyCode())
                        ->setParentTransactionId($response['_id'])->setShouldCloseParentTransaction(true)
                        ->setIsTransactionClosed(0)
                        ->registerCaptureNotification($order->getBaseGrandTotal());

                    $order->setStatus('processing');
                    $order->setState('processing');
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

