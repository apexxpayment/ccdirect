<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Model\Adminhtml\Source;

/**
 * Class PaymentMode
 * @package Apexx\CcDirect\Model\Adminhtml\Source
 */
class PaymentMode
{
    public function toOptionArray()
    {
        return [
                    ['value' => 'TEST', 'label' => __('Test')],
                    ['value' => 'LIVE', 'label' => __('Live')],
        ];
    }
}
