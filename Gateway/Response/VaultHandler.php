<?php

namespace Apexx\CcDirect\Gateway\Response;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Payment\Model\InfoInterface;
use Apexx\CcDirect\Gateway\Config\Config;
use Magento\Payment\Model\Method\Logger;
use Apexx\Base\Helper\Data as ApexxBaseHelper;

class VaultHandler implements HandlerInterface
{

     const X_MASKED_CARD_NUMBER = 'xMaskedCardNumber';
    const xCardType = 'Credit Card';
    const xToken = 'token';
    const xExp = 'xExp';
    protected $paymentTokenFactory;
    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    protected $config;
         /**
     * @var ApexxBaseHelper
     */
    protected  $apexxBaseHelper;


    /**
     * Constructor
     *
     * @param CreditCardTokenFactory $creditCardTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Config $config,
        Logger $logger,
        ApexxBaseHelper $apexxBaseHelper
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->apexxBaseHelper = $apexxBaseHelper;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */

    public function handle(array $handlingSubject, array $response)
    {

        if (
            !isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        if ($payment->getAdditionalInformation("is_active_payment_token_enabler") == "") {
            return;
        }

         $xExp = "";
        if($payment->getAdditionalInformation("cc_exp_month") != "") {
            $xExp = sprintf('%02d%02d', $payment->getAdditionalInformation("cc_exp_month"), substr($payment->getAdditionalInformation("cc_exp_year"), -2));
        }

        // add vault payment token entity to extension attributes
        if (isset($xExp)) {
            $paymentToken = $this->getVaultPaymentToken($response, $xExp);
            if (null !== $paymentToken) {
                $extensionAttributes = $this->getExtensionAttributes($payment);
                $extensionAttributes->setVaultPaymentToken($paymentToken);
            }
        }
    }


   /**
     * Get vault payment token entity
     *
     * @param  array
     * @return PaymentTokenInterface|null
     */
    private function getVaultPaymentToken(array $response, string $xExp)
    {
        // Check token existing in gateway response
        if (isset($response['card']['token'])) {
            $token = $response['card']['token'];
            if (empty($token)) {
                return null;
            }
        } else {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
        if(isset($response['card_brand'])) {
        $cardBrandCode =  $this->apexxBaseHelper->getCcTypesList($response['card_brand']);
        }   
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($xExp));
        $paymentToken->setType($response['payment_product']);
        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $cardBrandCode,
            'maskedCC' => $response['card']['card_number'],
            'expirationDate' => $xExp
        ]));
        return $paymentToken;
    }

     /**
     * @param string $xExp
     * @return string
     */
    private function getExpirationDate(string $xExp)
    {
        $expDate = new \DateTime(
            '20' . substr($xExp, -2)
                . '-'
                . substr($xExp, 0, 2)
                . '-'
                . '01'
                . ' '
                . '00:00:00',
            new \DateTimeZone('UTC')
        );
        return $expDate->format('Y-m-d 00:00:00');
    }
    /**
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }

    /**
     *
     * @param string $type
     * @return array
     */
    private function getCreditCardType($type)
    {
        $mapper = $this->config->getCctypesMapper();
        return $mapper[$type];
    }

    /**
     * Get payment extension attributes
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }


}
