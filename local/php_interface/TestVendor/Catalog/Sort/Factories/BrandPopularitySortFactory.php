<?php

namespace TestVendor\Catalog\Sort\Factories;

use TestVendor\Catalog\Sort\Encoding\TierPopularityWeightEncoder;
use TestVendor\Catalog\Sort\Prefetch\SortPrefetcherInterface;
use TestVendor\Catalog\Sort\Product\BrandPopularitySortContextMapper;
use TestVendor\Catalog\Sort\Product\SortContextMapperInterface;
use TestVendor\Catalog\Sort\Rules\DefaultRule;
use TestVendor\Catalog\Sort\Rules\ExclusiveBrandRule;
use TestVendor\Catalog\Sort\Rules\NewProductRule;
use TestVendor\Catalog\Sort\SortRuleFactory;
use TestVendor\Catalog\Sort\SortServices;
use TestVendor\Catalog\Sort\SortWeightCalculator;
use TestVendor\Config;

final class BrandPopularitySortFactory implements SortAbstractFactoryInterface
{
    public function createMapper(): SortContextMapperInterface
    {
        return new BrandPopularitySortContextMapper(
            Config::CATALOG_BRAND_PROPERTY_CODE,
            Config::CATALOG_POPULAR_FIELD,
            Config::SORT_CONTEXT_BRAND_XML_ID_KEY,
            Config::SORT_CONTEXT_POPULARITY_KEY,
            Config::CATALOG_CREATED_AT_FIELD,
            Config::SORT_CONTEXT_CREATED_AT_KEY
        );
    }

    public function createPrefetcher(SortServices $services): SortPrefetcherInterface
    {
        return new class implements SortPrefetcherInterface {
            public function warmUp(array $products): void
            {
            }
        };
    }

    public function createCalculator(SortServices $services): SortWeightCalculator
    {
        $nowTs = time();

        $rules = [
            new ExclusiveBrandRule(
                Config::SORT_CONTEXT_BRAND_XML_ID_KEY,
                1,
                true,
                Config::SORT_CONTEXT_CREATED_AT_KEY,
                Config::SORT_NEW_PERIOD_DAYS,
                $nowTs
            ),
            new NewProductRule(
                Config::SORT_CONTEXT_CREATED_AT_KEY,
                Config::SORT_NEW_PERIOD_DAYS,
                2,
                $nowTs
            ),
            new ExclusiveBrandRule(
                Config::SORT_CONTEXT_BRAND_XML_ID_KEY,
                3,
                false,
                null,
                null,
                $nowTs
            ),
            new DefaultRule(4),
        ];

        $ruleFactory = new SortRuleFactory($rules);
        $encoder = new TierPopularityWeightEncoder(Config::SORT_WEIGHT_BASE);

        return new SortWeightCalculator(
            $ruleFactory,
            $services,
            $encoder,
            Config::SORT_CONTEXT_POPULARITY_KEY
        );
    }
}
