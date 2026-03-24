<?php

namespace TestVendor\Agents;

class UpdateProductStoresAgent extends BaseExchangeAgent
{
    protected function getExchangeMethod(): string
    {
        return 'updateProductStores';
    }
}