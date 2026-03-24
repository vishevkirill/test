<?php

namespace TestVendor\Agents;

class UpdateProductsPQAgent extends BaseExchangeAgent
{
    protected function getExchangeMethod(): string
    {
        return 'updateProductQP';
    }
}