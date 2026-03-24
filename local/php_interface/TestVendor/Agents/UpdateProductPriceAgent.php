<?php

namespace TestVendor\Agents;

class UpdateProductPriceAgent extends BaseExchangeAgent
{
    protected function getExchangeMethod(): string
    {
        return 'updateProductPrice';
    }
}