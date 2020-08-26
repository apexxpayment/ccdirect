<?php

namespace Apexx\CcDirect\Api;

interface ApexxOrderPaymentStatusInterface
{
    /**
     * @param string $orderId
     * @return string
     */
    public function getOrderPaymentStatus($orderId);
}
