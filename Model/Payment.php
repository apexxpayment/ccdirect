<?php
namespace Apexx\CcDirect\Model;
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
 const CODE = 'ccdirect_gateway';

    protected $_code = self::CODE;
}