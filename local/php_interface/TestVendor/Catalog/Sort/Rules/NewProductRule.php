<?php

namespace TestVendor\Catalog\Sort\Rules;

use TestVendor\Catalog\Sort\Product\SortContext;
use TestVendor\Catalog\Sort\SortServices;
use TestVendor\Config;

final class NewProductRule implements SortRuleInterface
{
    private readonly int $newFromTs;

    public function __construct(
        private readonly string $createdAtKey,
        private readonly int    $newPeriodDays,
        private readonly int    $tier,
        int                     $nowTs,
    )
    {
        $this->newFromTs = $nowTs - ($this->newPeriodDays * Config::SECONDS_PER_DAY);
    }

    public function supports(SortContext $ctx, SortServices $services): bool
    {
        $createdAt = $ctx->int($this->createdAtKey, 0);
        if ($createdAt <= 0) {
            return false;
        }

        return $createdAt > $this->newFromTs;
    }

    public function tier(): int
    {
        return $this->tier;
    }
}
