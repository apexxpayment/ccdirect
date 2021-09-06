<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Encryption\EncryptorInterface ;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Framework\Serialize\Serializer\Json as SerializeJson;
use \Magento\Framework\HTTP\Adapter\CurlFactory;
use \Magento\Framework\HTTP\Header as HttpHeader;
use \Magento\Sales\Model\OrderRepository;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use \Psr\Log\LoggerInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class Data
 * @package Apexx\CcDirect\Helper
 */
class Data extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_CONFIG_PATH_CCDIRECTPAYMENT  = 'payment/ccdirect_gateway';
    const XML_PATH_PAYMENT_CCDIRECT   = 'payment/apexx_section/apexxpayment/ccdirect_gateway';
    const XML_PATH_PAYMENT_ACTION     = '/payment_action';
    const XML_PATH_DYNAMIC_DESCRIPTOR = '/dynamic_descriptor';
    const XML_PATH_3DS_REQ            = '/three_d_status';
    const XML_PATH_CAPTURE_MODE       = '/capture_mode';
    const XML_PATH_PAYMENT_MODES      = '/payment_modes';
    const XML_PATH_WEBHOOK_UPDATE_URL = '/webhook_transaction_update';
    const XML_PATH_RECURRING_TYPE     = '/recurring_type';
    const XML_PATH_CREATE_TOKEN        = '/create_token'; 
    const XML_PATH_ALLOW_CURRENCY = '/allow' ;
    const XML_PATH_PAYMENT = 'payment/';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var SerializeJson
     */
    protected $serializeJson;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var HttpHeader
     */
    protected $httpHeader;
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchBuilder;


    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Data constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param JsonFactory $resultJsonFactory
     * @param SerializeJson $serializeJson
     * @param CurlFactory $curlFactory
     * @param HttpHeader $httpHeader
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        JsonFactory $resultJsonFactory,
        SerializeJson $serializeJson,
        curlFactory $curlFactory,
        HttpHeader $httpHeader,
        OrderRepository $orderRepository,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder $searchBuilder,
        FilterBuilder $filterBuilder,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor ;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializeJson = $serializeJson;
        $this->curlFactory = $curlFactory;
        $this->httpHeader = $httpHeader;
        $this->orderRepository  = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->searchBuilder = $searchBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->logger = $logger;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getConfigPathValue($key)
    {
        return $this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_CCDIRECTPAYMENT . $key,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get config value at the specified key
     *
     * @param string $key
     * @return mixed
     */
    public function getConfigValue($key)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_CCDIRECT . $key,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getHostedPaymentAction()
    {
        $hostPaymentAction = $this->getConfigPathValue(self::XML_PATH_PAYMENT_ACTION);
        if ($hostPaymentAction == 'authorize') {
            return 'false';
        } else {
            return 'true';
        }
    }

    /**
     * @return mixed
     */
    public function getDynamicDescriptor()
    {
        return $this->getConfigValue(self::XML_PATH_DYNAMIC_DESCRIPTOR);
    }


    /**
     * @param null $storeId
     * @return bool
     */
    public function getThreeDsRequired($storeId = null)
    {
        $three_ds_required = "false";
        $three_ds_required =  $this->getConfigValue(self::XML_PATH_3DS_REQ);
        if($three_ds_required == 1) {
            $three_ds_required = "true";
        } else {
            $three_ds_required = "false";
        }

        return $three_ds_required;
    }
     /**
     * @return mixed
     */
    public function getCreateToken()
    {
        $create_token = $this->getConfigValue(self::XML_PATH_CREATE_TOKEN);
         if($create_token == 1) {
            $create_token = "true";
        } else {
            $create_token = "false";
        }

        return $create_token;
    }

     /**
     * @return string
     */
    public function getPaymentAction()
    {
        $paymentAction = $this->getConfigPathValue(self::XML_PATH_PAYMENT_ACTION);

        if ($paymentAction == 'authorize') {
            return 'false';
        } else {
            return 'true';
        }
    }
    /**
     * @return mixed
     */
    public function getCaptureMode()
    {
        return $this->getConfigValue(self::XML_PATH_CAPTURE_MODE);
    }

    /**
    * @ return string
    */
    public function webhookUpdateUrl()
    {
        return $this->getConfigValue(self::XML_PATH_WEBHOOK_UPDATE_URL);
    }

    /**
     * @return mixed
     */
    public function getRecurringType()
    {
        return $this->getConfigValue(self::XML_PATH_RECURRING_TYPE);
    }

    public function getAllowPaymentCurrency($currency) {

        $allowCurrencyList = $this->getConfigValue(self::XML_PATH_ALLOW_CURRENCY);
        if (!empty($allowCurrencyList)) {
            $currencyList = explode(",", $allowCurrencyList);
            if (!empty($currencyList)) {
                $currencyInfo = [];
                foreach ($currencyList as $key => $value) {
                    if ($value == $currency) {
                        $currencyInfo['currency_code'] = $value;
                    }
                }
                return $currencyInfo;
            }
        }
    }

    /**
     * @param OrderInterface $order
     * @return mixed
     */
    public function getPaymentCatpureMode(OrderInterface $order)
    {
        $payment = $order->getPayment();
        $method = $payment->getMethod();

        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT . $method . self::XML_PATH_PAYMENT_ACTION,
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
    }
}
