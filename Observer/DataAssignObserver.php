<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect 
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;

use Magento\Payment\Model\InfoInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    const CardNum = 'cc_number';
    const CVV = 'cc_cid';
    const cc_exp_month = 'cc_exp_month';
    const cc_exp_year = 'cc_exp_year';
    const vault_cvv = 'vault_cvv' ;

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::CardNum,
        self::CVV,
        self::cc_exp_month,
        self::cc_exp_year,
        self::vault_cvv
    ];

    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}

