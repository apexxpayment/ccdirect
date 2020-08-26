<?php

namespace Apexx\CcDirect\Controller\Process;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    protected $_pageFactory;
    protected $request;
    protected $messageManager;
    protected $_checkoutSession;
    protected $_urlInterface;


    public function __construct(
      \Magento\Framework\App\Action\Context $context,
      \Magento\Framework\View\Result\PageFactory $pageFactory,
      \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
      \Magento\Framework\App\Request\Http $request,
      \Magento\Framework\Message\ManagerInterface $messageManager,
       \Magento\Framework\UrlInterface $urlInterface,
      \Magento\Checkout\Model\Session $checkoutSession
  )
    {
      $this->_pageFactory = $pageFactory;
      $this->resultRedirectFactory = $resultRedirectFactory;
      $this->request = $request;
      $this->messageManager = $messageManager;
      $this->_checkoutSession = $checkoutSession;
      $this->_urlInterface = $urlInterface;
      return parent::__construct($context);
    }

    public function execute()
    {
      $data=$this->request->getParams();
    
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
}
