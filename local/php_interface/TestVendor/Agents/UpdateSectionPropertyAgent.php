<?php

namespace TestVendor\Agents;

use TestVendor\Config;

class UpdateSectionPropertyAgent extends BaseExchangeAgent
{
    protected function getExchangeMethod(): string
    {
        return 'updateSectionProperty';
    }

    protected function getEndpoint(): string
    {
        return Config::ENDPOINT_PROPERTY_GET;
    }
}