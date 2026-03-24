<?php

namespace TestVendor\Catalog\Sort\Factories;

use TestVendor\Catalog\Sort\Prefetch\SortPrefetcherInterface;
use TestVendor\Catalog\Sort\Product\SortContextMapperInterface;
use TestVendor\Catalog\Sort\SortServices;
use TestVendor\Catalog\Sort\SortWeightCalculator;

/**
 * Абстрактная фабрика профиля сортировки.
 *
 * Один профиль = семейство согласованных объектов:
 * - mapper (какие данные вытащить из Битрикса)
 * - prefetcher (какие репозитории прогреть)
 * - calculator (как посчитать вес)
 */
interface SortAbstractFactoryInterface
{
    public function createMapper(): SortContextMapperInterface;

    public function createPrefetcher(SortServices $services): SortPrefetcherInterface;

    public function createCalculator(SortServices $services): SortWeightCalculator;
}
