<?php

namespace TestVendor\Catalog\Sort\Product;

final class Product
{
    public function __construct(
        public int                   $id,
        public int                   $iblockId,
        public int                   $currentWeight,
        private readonly SortContext $sortContext
    )
    {
    }

    public function sortContext(): SortContext
    {
        return $this->sortContext;
    }
}