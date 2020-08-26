<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Model\Adminhtml\Source;

/**
 * Class ThreedMode
 * @package Apexx\CcDirect\Model\Adminhtml\Source
 */
class ThreedMode
{
    public function toOptionArray()
    {
        return [
                    ['value' => 'sca', 'label' => __('sca (sca)')],
                    ['value' => 'frictionless', 'label' => __('frictionless (frictionless)')],
        ];
    }
}
