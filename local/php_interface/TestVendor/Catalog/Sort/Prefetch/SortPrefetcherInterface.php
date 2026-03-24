<?php

namespace TestVendor\Catalog\Sort\Prefetch;

interface SortPrefetcherInterface
{
    /**
     * @param array<int, mixed> $products
     */
    public function warmUp(array $products): void;
}
