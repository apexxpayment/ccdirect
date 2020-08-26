<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Gateway\Request;
//use Magento\Payment\Gateway\ConfigInterface;
use Apexx\CcDirect\Helper\Data as configHelper;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Apexx\Base\Helper\Data as ApexxBaseHelper;
use Magento\Checkout\Model\Session As CheckoutSession;

class VaultAuthorizeRequest implements BuilderInterface
{

     /**
     * @var CcDirectHelper
     */
    protected  $configHelper;

    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var ApexxBaseHelper
     */
    protected  $apexxBaseHelper;
        /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
     /**
     * AuthorizationRequest constructor.
     * @param ConfigInterface $config
     * @param configHelper $configHelper
     * @param Order $order
     */

    public function __construct(
       // ConfigInterface $config,
        configHelper $configHelper,
        ApexxBaseHelper $apexxBaseHelper,
        CheckoutSession $checkoutSession
    ) {
      //  $this->config = $config;
        $this->configHelper = $configHelper;
        $this->apexxBaseHelper = $apexxBaseHelper;
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

        /** @var PaymentDataObjectInterface $payment */
        /** @var PaymentDataObjectInterface $payment */

        $paymentDO = $buildSubject['payment'];
        $amount = $buildSubject['amount']*100;
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $billing = $order->getBillingAddress();
        $address = $order->getShippingAddress();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        $request = [
           // "account" =>  "1db380005b524103bf323f9ef63ae1cf",
            "organisation" => $this->apexxBaseHelper->getOrganizationId(),
            "currency"=> $this->checkoutSession->getQuote()->getQuoteCurrencyCode(),
            "amount"=> $amount,
            "capture_now"=> $this->configHelper->getPaymentAction(),
                "card" => [
                    "token" => $paymentToken->getGatewayToken(),
                    "cvv" => $payment->getAdditionalInformation("vault_cvv")
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

            return $request ;
    }
}
