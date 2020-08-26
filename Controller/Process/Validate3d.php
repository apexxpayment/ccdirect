<?php
namespace Apexx\CcDirect\Controller\Process;

use \Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Apexx\Base\Helper\Logger\Logger as CustomLogger;
use Apexx\Base\Helper\Data as ApexxBaseHelper;


/**
 * Class Response
 * @package Apexx\CcDirect\Controller\Process
 */
class Validate3d extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Builder
     */
    private $transactionBuilder;

    /**
     * @var CustomLogger
     */
    protected $customLogger;
        /**
     * @var ApexxBaseHelper
     */
    protected  $apexxBaseHelper;

      const REQUEST_TYPE_AUTH_ONLY    = 'AUTH';
    const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURED';

    /**
     * Response constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param Http $request
     * @param ManagerInterface $messageManager
     * @param UrlInterface $urlInterface
     * @param Session $checkoutSession
     * @param OrderRepository $orderRepository
     * @param OrderInterface $order
     * @param OrderFactory $orderFactory
     * @param LoggerInterface $logger
     * @param Builder $transactionBuilder
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        RedirectFactory $resultRedirectFactory,
        Http $request,
        ManagerInterface $messageManager,
        UrlInterface $urlInterface,
        Session $checkoutSession,
        OrderRepository $orderRepository,
        OrderInterface $order,
        OrderFactory $orderFactory,
        LoggerInterface $logger,
        Builder $transactionBuilder,
         ApexxBaseHelper $apexxBaseHelper,
        CustomLogger $customLogger
    )
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->urlInterface = $urlInterface;
        $this->orderRepository = $orderRepository;
        $this->order           = $order;
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->transactionBuilder = $transactionBuilder;
        $this->customLogger = $customLogger;
        $this->apexxBaseHelper = $apexxBaseHelper;

    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {

      try {
       $data=$this->request->getParams();
      $requestMd =$this->request->getParam("MD");
      $paRes=$this->request->getParam("PaRes");
      $order = $this->checkoutSession->getLastRealOrder();
      $payment = $order->getPayment();
        $md = $order->getPayment()->getAdditionalInformation('md');
      $_id = $order->getPayment()->getAdditionalInformation('_id');
       $resultRedirect = $this->resultRedirectFactory->create();
      $requestData = [
        "_id"=> $_id,
        "paRes"=> $paRes
      ];

      $url = $this->apexxBaseHelper->getApiEndpoint().'payment/3ds/authenticate';
        $requestData = json_encode($requestData);
        $curlResponse = $this->apexxBaseHelper->getCustomCurl($url, $requestData);
        $resultObject = json_decode($curlResponse);
        $response = json_decode(json_encode($resultObject), True);

      
            $this->customLogger->debug('3DS Success Response:', $response);

            if($response['status'] == 'AUTHORISED'){

             if(isset($response['_id']))
                {
                $transactionId = $response['_id'];
                }
                 if(isset($response['status']))
                {
               $status=$response['status'];
                }
                if(isset($response['amount']))
                {
               $amount = $response['amount'];
                $total = ($amount / 100);
                }
                if(isset($response['card']['expiry_month']))
                {
               $expiry_month = $response['card']['expiry_month'];
                }
                if(isset($response['card']['expiry_year']))
                {
               $expiry_year = $response['card']['expiry_year'];
                }
                if(isset($response['card']['card_number']))
                {
                $card_number = $response['card']['card_number'];
                }
                if(isset($response['card']['token']))
                {
               $card_token = $response['card']['token'];
                }
                if(isset($response['authorization_code']))
                {
                $authorization_code = $response['authorization_code'];
                }
                 if(isset($response['card_brand']))
                {
                $card_brand = $response['card_brand'];
                }

                $payment->setLastTransId($transactionId);
                $payment->setParentTransactionId(null);
                $payment->setCcType($card_brand);
                $payment->setCcExpMonth($expiry_month);
                $payment->setCcExpYear($expiry_year);
                $payment->setCcNumberEnc($card_number);
                $payment->setCcLast4(substr($card_number, -4));
                $payment->setCcApproval($authorization_code);
                // Set Response into sales_order_payment table
                if (isset($response['reason_code'])) {
                    $payment->setAdditionalInformation('reason_code', $response['reason_code']);
                }
                if (isset($response['_id'])) {
                    $payment->setAdditionalInformation('_id', $response['_id']);
                }
                if (isset($response['authorization_code'])) {
                    $payment->setAdditionalInformation('authorization_code', $response['authorization_code']);
                }
                if (isset($response['merchant_reference'])) {
                    $payment->setAdditionalInformation('merchant_reference', $response['merchant_reference']);
                }
                if (isset($response['amount'])) {
                    $payment->setAdditionalInformation('amount', $total);
                }
                if (isset($response['status'])) {
                    $payment->setAdditionalInformation('status', $response['status']);
                }
                if (isset($card_number)) {
                    $payment->setAdditionalInformation('card_number', $card_number);
                }
                if (isset($expiry_month)) {
                    $payment->setAdditionalInformation('expiry_month', $expiry_month);
                }
                if (isset($expiry_year)) {
                    $payment->setAdditionalInformation('expiry_year', $expiry_year);
                }

                $transaction = $this->transactionBuilder->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($transactionId)
                    ->addAdditionalInformation('raw_details_info', $response)
                    ->setFailSafe(true)
                    ->build('authorization');
                $transaction->setIsClosed(false);

                $payment->addTransactionCommentsToOrder($transaction, __('Authorized amount of %1.', $order->getBaseCurrency()->formatTxt($total)));

                $order->setState('processing');
                $orderStatus = strtolower($response['status']);
                $order->setStatus($orderStatus);
                $payment->save();
                $order->save();
                $transaction->save();

                 $resultRedirect->setPath('checkout/onepage/success');

            } elseif($response['status']=='CAPTURED'){ 

             if(isset($response['_id']))
                {
                $transactionId = $response['_id'];
                }
                 if(isset($response['status']))
                {
               $status=$response['status'];
                }
                if(isset($response['amount']))
                {
               $amount = $response['amount'];
               $total = ($amount / 100);
                }
                if(isset($response['card']['expiry_month']))
                {
               $expiry_month = $response['card']['expiry_month'];
                }
                if(isset($response['card']['expiry_year']))
                {
               $expiry_year = $response['card']['expiry_year'];
                }
                if(isset($response['card']['card_number']))
                {
                $card_number = $response['card']['card_number'];
                }
                if(isset($response['card']['token']))
                {
               $card_token = $response['card']['token'];
                }
                if(isset($response['authorization_code']))
                {
                $authorization_code = $response['authorization_code'];
                }
                 if(isset($response['card_brand']))
                {
                $card_brand = $response['card_brand'];
                }


                $payment->setLastTransId($transactionId);
                $payment->setCcType($card_brand);
                $payment->setCcExpMonth($expiry_month);
                $payment->setCcExpYear($expiry_year);
                $payment->setCcNumberEnc($card_number);
                $payment->setCcLast4(substr($card_number, -4));
                $payment->setCcApproval($authorization_code);
                $payment->setRrno($payment->getParentTransactionId());
                $payment->setTransactionType(self::REQUEST_TYPE_CAPTURE_ONLY);
                $payment->setAmount(($response['amount']/100)); 
                $payment->setTransactionId($response['_id'])
              ->setCurrencyCode($order->getBaseCurrencyCode())
              ->setParentTransactionId($response['_id'])
              ->setShouldCloseParentTransaction(true)
              ->setIsTransactionClosed(0)
              ->registerCaptureNotification($order->getBaseGrandTotal());

                // Set Response into sales_order_payment table
                if (isset($response['reason_code'])) {
                    $payment->setAdditionalInformation('reason_code', $response['reason_code']);
                }
                if (isset($response['_id'])) {
                    $payment->setAdditionalInformation('_id', $response['_id']);
                }
                if (isset($response['authorization_code'])) {
                    $payment->setAdditionalInformation('authorization_code', $response['authorization_code']);
                }
                if (isset($response['merchant_reference'])) {
                    $payment->setAdditionalInformation('merchant_reference', $response['merchant_reference']);
                }
                if (isset($response['amount'])) {
                    $payment->setAdditionalInformation('amount', $response['amount']/100);
                }
                if (isset($response['status'])) {
                    $payment->setAdditionalInformation('status', $response['status']);
                }
                if (isset($card_number)) {
                    $payment->setAdditionalInformation('card_number', $card_number);
                }
                if (isset($expiry_month)) {
                    $payment->setAdditionalInformation('expiry_month', $expiry_month);
                }
                if (isset($expiry_year)) {
                    $payment->setAdditionalInformation('expiry_year', $expiry_year);
                }

                $transaction = $this->transactionBuilder->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($transactionId)
                    ->addAdditionalInformation('raw_details_info', $response)
                    ->setFailSafe(true)
                    ->build('capture');
                $transaction->setIsClosed(true);

                $payment->addTransactionCommentsToOrder($transaction, __('Captured amount of %1.', $order->getBaseCurrency()->formatTxt($total)));

                $order->setStatus('processing');
                $order->setState('processing');

                $payment->save();
                $order->save();
                $transaction->save();

               $resultRedirect->setPath('checkout/onepage/success');

            } else {

                $payment->setLastTransId($response['_id']);
                $payment->setTransactionId($response['_id']);
                $orderStatus = strtolower($response['status']);
                $order->setStatus($orderStatus);
                $order->save();
                $payment->save();
                
            $this->messageManager->addError(__($response['reason_message']));
            $resultRedirect->setPath('checkout/cart');
           }
            return $resultRedirect ;

        } catch (\Exception $e){
            $this->logger->critical($e);
            $this->messageManager->addError(__("Time Out Server"));
        }

    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ? bool
    {
        return true;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelTransactionOrder() {
        $this->cancelCurrentOrder('');
        //$this->restoreQuote();
    }

    /**
     * @param $comment
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelCurrentOrder($comment)
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order->getId() && $order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function restoreQuote()
    {
        return $this->checkoutSession->restoreQuote();
    }
}
