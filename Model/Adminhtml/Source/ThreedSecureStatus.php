<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Model\Adminhtml\Source;

/**
 * Class ThreedSecureStatus
 * @package Apexx\CcDirect\Model\Adminhtml\Source
 */
class ThreedSecureStatus
{
    public function toOptionArray()
    {
        return [
                ['value' => 'y', 'label' => __('Y')],
                ['value' => 'n', 'label' => __('N')],
                ['value' => 'u', 'label' => __('U')],
                ['value' => 'a', 'label' => __('A')],
                ['value' => 'r', 'label' => __('R')],
        ];
    }
}
