<?php
/**
 * Custom payment method in Magento 2
 * @category    CcDirect
 * @package     Apexx_CcDirect
 */
namespace Apexx\CcDirect\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Command\CommandException;

class RefundResponseValidator extends AbstractValidator
{
    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];
        
        if (isset($response['status'])){
            if ($response['status'] == 'REFUNDED') {
                return $this->createResult(
                    true,
                    []
                );
            } 
        elseif ($response['status'] == 'DECLINED') {
        throw new CommandException(__($response['reason_message']));
                    return $this->createResult(
                    false,
                    [__($response['reason_message'])]
                ); 

            }
        elseif ($response['status'] == 'FAILED') {
        throw new CommandException(__($response['reason_message']));
                    return $this->createResult(
                    false,
                    [__($response['reason_message'])]
                ); 
        } 
        else {
            if (isset($response['reason_message'])) {
             throw new CommandException(__($response['reason_message']));
                return $this->createResult(
                    false,
                    [__($response['reason_message'])]
                );
            } else {
                 throw new CommandException(__($response['message']));
                return $this->createResult(
                    false,
                    [__('Gateway rejected the transaction.')]
                );
            }
        }

    }
        else
        {   
            if(isset($response['message']))
            {
            throw new CommandException(__($response['message']));
            return $this->createResult(
                        false,
                        [__($response['message'])]
                    );
            }
            else
            {
                return $this->createResult(
                        false,
                        [__('Gateway rejected the transaction.')]
                    );
            }
        }
    }
}
