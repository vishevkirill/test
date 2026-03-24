<?php

namespace TestVendor\Catalog\Sort\Rules;

use InvalidArgumentException;
use TestVendor\Catalog\Sort\Product\SortContext;
use TestVendor\Catalog\Sort\SortServices;
use TestVendor\Config;

final class ExclusiveBrandRule implements SortRuleInterface
{
    private readonly int $newFromTs;

    /** @var array<string, true>|null */
    private static ?array $exclusiveSet = null;

    public function __construct(
        private readonly string $brandXmlIdKey,
        private readonly int $tier,
        private readonly bool $requireNew,
        private readonly ?string $createdAtKey,
        private readonly ?int $newPeriodDays,
        int $nowTs,
    )
    {
        if ($this->requireNew) {
            if ($this->createdAtKey === null || $this->newPeriodDays === null || $this->newPeriodDays <= 0) {
                throw new InvalidArgumentException('ExclusiveBrandRule: requireNew требует createdAtKey и newPeriodDays');
            }
            $this->newFromTs = $nowTs - ($this->newPeriodDays * Config::SECONDS_PER_DAY);
        } else {
            $this->newFromTs = 0;
        }
    }

    public function supports(SortContext $ctx, SortServices $services): bool
    {
        $xmlId = $ctx->nullableString($this->brandXmlIdKey);
        if ($xmlId === null) {
            return false;
        }

        if (self::$exclusiveSet === null) {
            self::$exclusiveSet = [];
            foreach (Config::EXCLUSIVE_MANUFACTURER_XML_IDS as $v) {
                self::$exclusiveSet[(string)$v] = true;
            }
        }

        if (!isset(self::$exclusiveSet[$xmlId])) {
            return false;
        }

        if (!$this->requireNew) {
            return true;
        }

        if ($this->createdAtKey === null) {
            return false;
        }

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
