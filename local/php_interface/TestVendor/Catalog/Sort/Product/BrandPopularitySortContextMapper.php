<?php

namespace TestVendor\Catalog\Sort\Product;

use CIBlockPropertyEnum;
use TestVendor\Config;

final class BrandPopularitySortContextMapper implements SortContextMapperInterface
{
    /** @var array<int, string> */
    private static array $enumXmlCache = [];

    /** @var array<string, int>|null */
    private static ?array $exclusiveRank = null;

    private static ?int $subBase = null;

    public function __construct(
        private readonly string $brandPropertyCode,
        private readonly string $popularityField,
        private readonly string $brandXmlIdKey,
        private readonly string $popularityKey,
        private readonly string $createdAtField,
        private readonly string $createdAtKey,
    )
    {
    }

    public function requiredSelectFields(): array
    {
        return [
            $this->popularityField,
            $this->createdAtField,
            'PROPERTY_' . $this->brandPropertyCode,
        ];
    }

    public function map(array $row): SortContext
    {
        $valueKey = 'PROPERTY_' . $this->brandPropertyCode . '_VALUE';
        $enumKey = 'PROPERTY_' . $this->brandPropertyCode . '_ENUM_ID';

        $brandValue = $row[$valueKey] ?? null;
        if (is_array($brandValue)) {
            $brandValue = reset($brandValue);
        }

        $enumId = (int)($row[$enumKey] ?? 0);
        $brandXmlId = $enumId > 0 ? $this->enumXmlId($enumId) : '';
        if ($brandXmlId === '') {
            $brandXmlId = trim((string)($brandValue ?? ''));
        }

        $popularity = (int)($row[$this->popularityField] ?? 0);
        $score = $this->score($brandXmlId, $popularity);

        $createdAt = $this->parseTs($row[$this->createdAtField] ?? null);

        return new SortContext([
            $this->brandXmlIdKey => $brandXmlId,
            $this->popularityKey => $score,
            $this->createdAtKey => $createdAt,
        ]);
    }

    private function parseTs(mixed $dateRaw): int
    {
        if ($dateRaw === null || $dateRaw === '') {
            return 0;
        }

        $dateStr = (string)$dateRaw;

        return (int)(\MakeTimeStamp($dateStr) ?: strtotime($dateStr) ?: 0);
    }

    private function enumXmlId(int $enumId): string
    {
        if (isset(self::$enumXmlCache[$enumId])) {
            return self::$enumXmlCache[$enumId];
        }

        $e = CIBlockPropertyEnum::GetList([], ['ID' => $enumId])->Fetch();
        $xmlId = $e ? trim((string)($e['XML_ID'] ?? '')) : '';

        self::$enumXmlCache[$enumId] = $xmlId;

        return $xmlId;
    }

    private function score(string $brandXmlId, int $popularity): int
    {
        $rank = $this->exclusiveRank($brandXmlId);
        if ($rank === null) {
            return $popularity;
        }

        $n = count(Config::EXCLUSIVE_MANUFACTURER_XML_IDS);
        $subBase = $this->subBase($n);

        $pop = $popularity;
        if ($subBase > 1 && $pop > ($subBase - 1)) {
            $pop = $subBase - 1;
        }

        return (($n - $rank) * $subBase) + $pop;
    }

    private function exclusiveRank(string $brandXmlId): ?int
    {
        if ($brandXmlId === '') {
            return null;
        }

        if (self::$exclusiveRank === null) {
            self::$exclusiveRank = [];
            foreach (Config::EXCLUSIVE_MANUFACTURER_XML_IDS as $i => $xmlId) {
                self::$exclusiveRank[(string)$xmlId] = (int)$i;
            }
        }

        return self::$exclusiveRank[$brandXmlId] ?? null;
    }

    private function subBase(int $n): int
    {
        if (self::$subBase !== null) {
            return self::$subBase;
        }

        $div = $n + 1;
        if ($div < 2) {
            $div = 2;
        }

        $sb = (int)intdiv(Config::SORT_WEIGHT_BASE, $div);
        if ($sb < 2) {
            $sb = 2;
        }

        self::$subBase = $sb;

        return self::$subBase;
    }
}
