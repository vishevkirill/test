<?php

namespace TestVendor\Agents;

class UpdateProductQuantityAgent extends BaseExchangeAgent
{
    protected function getExchangeMethod(): string
    {
        return 'updateProductQuantity';
    }
}