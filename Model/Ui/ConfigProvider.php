<?php
/**
 * See LICENSE for license details.
 */

namespace Apexx\CcDirect\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Apexx\CcDirect\Helper\Data as Config;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'ccdirect_gateway';
    const CC_VAULT_CODE = 'ccdirect_cc_vault';
    const recurring_type = 'recurring_type' ;
    private $config;

    public function __construct(
      Config $config,
        ResolverInterface $localeResolver
    ) {
    
        $this->config = $config;
    }


    public function getConfig()
    {
        $requestConfig = [
            'payment' => [
                self::CODE => [
                    'isActive' => 1,
                    'ccVaultCode' => self::CC_VAULT_CODE,
                    'recurring_type' => $this->config->getRecurringType()
                ]
            ]
        ];

        return $requestConfig ; 
    }
}

