<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Plugin\Method;

use Magento\Payment\Model\Method\Adapter;
use Magento\Checkout\Model\Session;
use Apexx\CcDirect\Helper\Data as configHelper;

/**
 * Class ApexxAdapter
 * @package Apexx\CcDirect\Plugin\Method
 */
class ApexxAdapter
{

     /**
     * @var CcDirectHelper
     */
    protected  $configHelper;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * ApexxAdapter constructor.
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession,
        configHelper $configHelper
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
    }

    /**
     * @param Adapter $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetConfigPaymentAction(Adapter $subject, $result)
    {
        $threeDs = $this->configHelper->getThreeDsRequired();
        $paymentMethod = $this->checkoutSession->getQuote()->getPayment()->getMethodInstance()->getCode();

        if ($paymentMethod == 'ccdirect_gateway') {
            if ($result == 'authorize_capture' && $threeDs == 'true') {
                return $result = 'authorize';
            }
        }
        return $result;
    }
}


