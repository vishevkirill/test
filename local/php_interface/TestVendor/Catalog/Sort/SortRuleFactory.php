<?php

namespace TestVendor\Catalog\Sort;

use TestVendor\Catalog\Sort\Product\SortContext;
use TestVendor\Catalog\Sort\Rules\DefaultRule;
use TestVendor\Catalog\Sort\Rules\SortRuleInterface;

final class SortRuleFactory
{
    /** @var SortRuleInterface[] */
    private array $rules;

    private SortRuleInterface $fallback;

    /**
     * @param SortRuleInterface[] $rules
     */
    public function __construct(array $rules, ?SortRuleInterface $fallback = null)
    {
        $this->rules = $rules;
        $this->fallback = $fallback ?? new DefaultRule();
    }

    public function create(SortContext $ctx, SortServices $services): SortRuleInterface
    {
        foreach ($this->rules as $rule) {
            if ($rule->supports($ctx, $services)) {
                return $rule;
            }
        }

        return $this->fallback;
    }
}