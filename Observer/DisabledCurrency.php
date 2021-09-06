<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session As CheckoutSession;
use Apexx\CcDirect\Helper\Data As configHelper;

/**
 * Class DisabledPaypalCurrency
 * @package Apexx\CcDirect\Observer
 */
class DisabledCurrency implements ObserverInterface
{
    /**
     * @var Session
     */
	protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var configHelper
     */
    protected $configHelper;

    /**
     * DisabledPaypalCurrency constructor.
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CartRepositoryInterface $quoteRepository
     * @param CheckoutSession $checkoutSession
     * @param configHelper $configHelper
     */
	public function __construct(
	    Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        configHelper $configHelper
    ) {
		$this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
	}

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
	public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $paymentMethod = $observer->getEvent()->getMethodInstance()->getCode();
        $result = $observer->getEvent()->getResult();

        $quoteCurrency = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
        $allowCurrency = $this->configHelper->getAllowPaymentCurrency($quoteCurrency); 

        if ($this->customerSession->isLoggedIn()) {
            if ($paymentMethod == 'ccdirect_gateway') {
                if (!empty($allowCurrency)) {
                    $result->setData('is_available', true);
                    return;
                } else {
                    $result->setData('is_available', false);
                    return;
                }
            }
        } else {
            if ($paymentMethod == 'ccdirect_gateway') {
             if (!empty($allowCurrency)) {
                    $result->setData('is_available', true);
                    return;
                } else {
                    $result->setData('is_available', false);
                    return;
                }
            }
        }
    }
    
}
