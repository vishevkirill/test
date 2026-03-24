<?php

namespace TestVendor\Catalog\Sort\Rules;

use TestVendor\Catalog\Sort\Product\SortContext;
use TestVendor\Catalog\Sort\SortServices;

interface SortRuleInterface
{
    public function supports(SortContext $ctx, SortServices $services): bool;

    /**
     * Чем меньше tier, тем выше приоритет.
     * (1 — топ, 2 — ниже, 3 — ещё ниже...)
     */
    public function tier(): int;
}
