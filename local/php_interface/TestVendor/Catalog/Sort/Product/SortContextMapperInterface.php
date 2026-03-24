<?php

namespace TestVendor\Catalog\Sort\Product;

interface SortContextMapperInterface
{
    /**
     * Какие поля нужно запросить у CIBlockElement::GetList, чтобы маппер мог собрать SortContext.
     *
     * @return string[]
     */
    public function requiredSelectFields(): array;

    /**
     * @param array<string, mixed> $row
     */
    public function map(array $row): SortContext;
}