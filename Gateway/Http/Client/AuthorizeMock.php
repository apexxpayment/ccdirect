<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\HTTP\Client\Curl;
use Apexx\CcDirect\Helper\Data as CcDirectHelper;
use Apexx\Base\Helper\Data as ApexxBaseHelper;
use Apexx\Base\Helper\Logger\Logger as CustomLogger;

/**
 * Class ClientMock
 * @package Apexx\CcDirect\Gateway\Http\Client
 */
class AuthorizeMock implements ClientInterface
{
    /**
     * @var Curl
     */
    protected $curlClient;

    /**
     * @var CcDirectHelper
     */
    protected  $CcDirectHelper;

    /**
     * @var ApexxBaseHelper
     */
    protected  $apexxBaseHelper;

    /**
     * @var CustomLogger
     */
    protected $customLogger;

    /**
     * AuthorizeMock constructor.
     * @param Curl $curl
     * @param CcDirectHelper $CcDirectHelper
     * @param ApexxBaseHelper $apexxBaseHelper
     * @param CustomLogger $customLogger
     */
    public function __construct(
        Curl $curl,
        CcDirectHelper $CcDirectHelper,
        ApexxBaseHelper $apexxBaseHelper,
        CustomLogger $customLogger
    ) {
        $this->curlClient = $curl;
        $this->CcDirectHelper = $CcDirectHelper;
        $this->apexxBaseHelper = $apexxBaseHelper;
        $this->customLogger = $customLogger;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $url = $this->apexxBaseHelper->getApiEndpoint().'payment/direct';

        $resultCode = json_encode($transferObject->getBody());

        $response = $this->apexxBaseHelper->getCustomCurl($url, $resultCode);

        $resultObject = json_decode($response);
        $responseResult = json_decode(json_encode($resultObject), True);

        $this->customLogger->debug('CC Direct Authorize Request:', $transferObject->getBody());
        $this->customLogger->debug('CC Direct Authorize Response:', $responseResult);

        return $responseResult;
    }
}
