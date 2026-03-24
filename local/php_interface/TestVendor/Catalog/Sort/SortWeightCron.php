<?php

namespace TestVendor\Catalog\Sort;

use CIBlockElement;
use TestVendor\Catalog\Sort\Factories\SortAbstractFactoryInterface;
use TestVendor\Catalog\Sort\Prefetch\SortPrefetcherInterface;
use TestVendor\Catalog\Sort\Product\Product;
use TestVendor\Catalog\Sort\Product\SortContextMapperInterface;
use TestVendor\Config;

final class SortWeightCron
{
    public function __construct(
        private readonly SortContextMapperInterface $contextMapper,
        private readonly SortPrefetcherInterface $prefetcher,
        private readonly SortWeightCalculator $calculator
    ) {
    }

    public static function fromFactory(SortAbstractFactoryInterface $factory, SortServices $services): self
    {
        return new self(
            $factory->createMapper(),
            $factory->createPrefetcher($services),
            $factory->createCalculator($services)
        );
    }

    public function run(int $iblockId): void
    {
        $lastId = 0;
        $updated = 0;
        $skipped = 0;
        $newCount = 0;
        $oldCount = 0;
        $newFromTs = time() - (Config::SORT_NEW_PERIOD_DAYS * Config::SECONDS_PER_DAY);
        $newMinWeight = null;
        $newMaxWeight = null;
        $oldMinWeight = null;
        $oldMaxWeight = null;
        $newWeights = [];

        while (true) {
            $batch = $this->loadBatch($iblockId, $lastId, Config::SORT_BATCH_SIZE);
            if ($batch === []) {
                break;
            }

            $this->prefetcher->warmUp($batch);

            foreach ($batch as $product) {
                $createdAt = $product->sortContext()->int(Config::SORT_CONTEXT_CREATED_AT_KEY, 0);
                if ($createdAt > $newFromTs) {
                    $newCount++;
                } else {
                    $oldCount++;
                }

                $weight = $this->calculator->calculate($product->sortContext());

                if ($weight === $product->currentWeight) {
                    $skipped++;
                } else {
                    CIBlockElement::SetPropertyValuesEx(
                        $product->id,
                        $product->iblockId,
                        [
                            Config::CATALOG_SORT_WEIGHT_PROPERTY_CODE => $weight,
                        ]
                    );
                    $updated++;
                }

                if ($createdAt > $newFromTs) {
                    $newWeights[] = $weight;
                    if ($newMinWeight === null || $weight < $newMinWeight) {
                        $newMinWeight = $weight;
                    }
                    if ($newMaxWeight === null || $weight > $newMaxWeight) {
                        $newMaxWeight = $weight;
                    }
                } else {
                    if ($oldMinWeight === null || $weight < $oldMinWeight) {
                        $oldMinWeight = $weight;
                    }
                    if ($oldMaxWeight === null || $weight > $oldMaxWeight) {
                        $oldMaxWeight = $weight;
                    }
                }

                $lastId = max($lastId, $product->id);
            }
        }

        $newNotAboveOld = 0;
        if ($oldMinWeight !== null) {
            foreach ($newWeights as $newWeight) {
                if ($newWeight >= $oldMinWeight) {
                    $newNotAboveOld++;
                }
            }
        }

        echo 'Done. Updated: ' . $updated . ', skipped: ' . $skipped . PHP_EOL;
        echo 'Detected new products: ' . $newCount . ', old products: ' . $oldCount . PHP_EOL;
        echo 'New weight range: ' . ($newMinWeight ?? 'n/a') . ' .. ' . ($newMaxWeight ?? 'n/a') . PHP_EOL;
        echo 'Old weight range: ' . ($oldMinWeight ?? 'n/a') . ' .. ' . ($oldMaxWeight ?? 'n/a') . PHP_EOL;
        echo 'New products not above old by weight: ' . $newNotAboveOld . PHP_EOL;
    }

    /**
     * @return Product[]
     */
    private function loadBatch(int $iblockId, int $afterId, int $limit): array
    {
        $itemsById = [];

        $select = array_values(array_unique(array_merge(
            [
                'ID',
                'IBLOCK_ID',
                Config::CATALOG_CREATED_AT_FIELD,
                'PROPERTY_' . Config::CATALOG_SORT_WEIGHT_PROPERTY_CODE,
            ],
            $this->contextMapper->requiredSelectFields()
        )));

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                '>ID' => $afterId,
            ],
            false,
            [
                'nTopCount' => $limit,
            ],
            $select
        );

        while ($row = $res->Fetch()) {
            $id = (int)($row['ID'] ?? 0);
            if ($id <= 0 || isset($itemsById[$id])) {
                continue;
            }

            $currentWeight = (int)($row['PROPERTY_' . Config::CATALOG_SORT_WEIGHT_PROPERTY_CODE . '_VALUE'] ?? 0);
            $ctx = $this->contextMapper->map($row);

            $itemsById[$id] = new Product($id, (int)$row['IBLOCK_ID'], $currentWeight, $ctx);
        }

        return array_values($itemsById);
    }
}
