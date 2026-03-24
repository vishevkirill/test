<?php

namespace TestVendor\Catalog\Sort;

use TestVendor\Catalog\Sort\Encoding\WeightEncoderInterface;
use TestVendor\Catalog\Sort\Product\SortContext;

final class SortWeightCalculator
{
    public function __construct(
        private readonly SortRuleFactory        $factory,
        private readonly SortServices           $services,
        private readonly WeightEncoderInterface $encoder,
        private readonly string                 $scoreKey = 'popularity'
    )
    {
    }

    public function calculate(SortContext $ctx): int
    {
        $rule = $this->factory->create($ctx, $this->services);
        $tier = $rule->tier();
        $score = $ctx->int($this->scoreKey, 0);

        return $this->encoder->encode($tier, $score);
    }
}