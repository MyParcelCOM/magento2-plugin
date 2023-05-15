<?php

namespace MyParcelCOM\Magento\Api;

interface WebhookInterface
{
    /**
     * @api
     */
    public function status(): bool;
}
