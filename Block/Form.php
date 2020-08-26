<?php
/**
 * See LICENSE for license details.
 */
namespace Apexx\CcDirect\Block;


use Magento\Backend\Model\Session\Quote;
use Apexx\CcDirect\Gateway\Config\Config as GatewayConfig;
use Apexx\CcDirect\Model\Ui\ConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form\Cc;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config;
use Magento\Vault\Model\VaultPaymentInterface;
/**
 * Class Form
 */
class Form extends Cc
{


    protected $gatewayConfig;


    /**
     * @param Context $context
     * @param Config $paymentConfig
     * @param GatewayConfig $gatewayConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $paymentConfig,
        GatewayConfig $gatewayConfig,

        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $data);
        $this->gatewayConfig = $gatewayConfig;
    }

}
