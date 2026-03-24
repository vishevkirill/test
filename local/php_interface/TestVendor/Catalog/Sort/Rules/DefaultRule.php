<?php

namespace TestVendor\Catalog\Sort\Rules;

use TestVendor\Catalog\Sort\Product\SortContext;
use TestVendor\Catalog\Sort\SortServices;

final class DefaultRule implements SortRuleInterface
{
    public function __construct(private readonly int $tier = 2)
    {
    }

    public function supports(SortContext $ctx, SortServices $services): bool
    {
        return true;
    }

    public function tier(): int
    {
        return $this->tier;
    }
}
