<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Apexx\Base\Helper\Data as ApexxBaseHelper;
use Apexx\CcDirect\Helper\Data as configHelper;
use Magento\Checkout\Model\Session As CheckoutSession;

class CaptureRequest implements BuilderInterface
{
    /**
     * @var AfterPayHelper
     */
    protected  $configHelper;
    /**
     * @var ConfigInterface
     */
     /**
     * @var ApexxBaseHelper
     */
    protected  $apexxBaseHelper;

    private $config;

       /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config,
        ApexxBaseHelper $apexxBaseHelper,
        configHelper $configHelper,
        CheckoutSession $checkoutSession
    ) {
        $this->config = $config;
        $this->apexxBaseHelper = $apexxBaseHelper;
        $this->configHelper = $configHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */

        $paymentDO = $buildSubject['payment'];

        $order = $paymentDO->getOrder();

        $address = $order->getShippingAddress();
        $total = $order->getGrandTotalAmount();
        $billing = $order->getBillingAddress();
        $amount = $buildSubject['amount']*100;
        // echo "<pre>";
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }
         if($payment->getLastTransId())
        {
        $request = [
            "transaction_id" => $payment->getParentTransactionId()
                ?: $payment->getLastTransId(),
          "amount" => $amount ,
          "capture_reference" => "Capture".$order->getOrderIncrementId()
                ];
            }
        else {
       $request = [
           // "account" => "1db380005b524103bf323f9ef63ae1cf",
            "organisation" => $this->apexxBaseHelper->getOrganizationId(),
            "currency"=> $this->checkoutSession->getQuote()->getQuoteCurrencyCode(),
            "amount"=> $amount,
            "capture_now"=> $this->configHelper->getPaymentAction(),
            "card" => [
                    "card_number" => $payment->getAdditionalInformation("cc_number"),
                    "cvv" => $payment->getAdditionalInformation("cc_cid"),
                    "expiry_month" => sprintf("%02d", $payment->getAdditionalInformation("cc_exp_month")),
                    "expiry_year" => substr($payment->getAdditionalInformation("cc_exp_year"), -2),
                    "create_token"=> $this->configHelper->getCreateToken()
                   ],
            "billing_address" => [
                    "first_name" => $billing->getFirstname(),
                    "last_name" => $billing->getLastname(),
                    "email" => $billing->getEmail(),
                    "address" => $billing->getStreetLine1().''.$billing->getStreetLine2(),
                    "city" => $billing->getCity(),
                    "state" => $billing->getRegionCode(),
                    "postal_code" => $billing->getPostcode(),
                    "country" => $billing->getCountryId()
                ],
                "customer_ip"=> $order->getRemoteIp(),
                "dynamic_descriptor" => $this->configHelper->getDynamicDescriptor(),
                "merchant_reference" => "JOURNEYBOX".$order->getOrderIncrementId(),
                "recurring_type"=> $this->configHelper->getRecurringType(),
                "user_agent"=> $this->apexxBaseHelper->getUserAgent(),
                "webhook_transaction_update" => $this->configHelper->webhookUpdateUrl(),
                "three_ds"=>[
                "three_ds_required"=> $this->configHelper->getThreeDsRequired()
                ]
            ];
        }

        return $request ;

    }
}
