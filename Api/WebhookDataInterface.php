<?php

namespace Apexx\CcDirect\Api;

interface WebhookDataInterface
{
    /**
     * @param string $name
     * @return string
     */
    public function webhookData();
}
