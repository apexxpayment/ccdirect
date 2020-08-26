<?php
/**
 * See LICENSE for license details.
 */
namespace Apexx\CcDirect\Gateway\Config;

/**
 * Class Config
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'payment_ccdirect_gateway_active';
    const KEY_CC_TYPES = ['VI', 'MC', 'AE', 'DI', 'JCB', 'MI', 'DN', 'CUP'];
    const METHOD_CODE = 'ccdirect_gateway';
    const KEY_CC_TYPES_CCDIRECT_MAPPER = 'cctypes_ccdirect_mapper';

    public function isActive()
    {
        return (bool) $this->getValue('payment_ccdirect_gateway_active');
    }

    /**
     * Retrieve mapper between Magento and Braintree card types
     *
     * @return array
     */
    public function getCcTypesMapper()
    {
        $result = json_decode(
            $this->getValue(self::KEY_CC_TYPES_CCDIRECT_MAPPER),
            true
        );
        return is_array($result) ? $result : [];
    }
}
