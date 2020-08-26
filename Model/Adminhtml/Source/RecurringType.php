<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Model\Adminhtml\Source;
use Magento\Framework\Option\ArrayInterface;

class RecurringType implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'oneclick',
                'label' => __('One-click'),
            ],
            [
                'value' => 'first',
                'label' => __('First'),
            ],
            [
                'value' => 'recurring',
                'label' => __('Recurring'),
            ],

        ];
    }
}