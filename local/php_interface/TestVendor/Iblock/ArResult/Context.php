<?php

namespace TestVendor\Iblock\ArResult;

class Context
{
    public array $arResult;

    public array $meta;

    public function __construct(array $arResult, array $meta = [])
    {
        $this->arResult = $arResult;
        $this->meta = $meta;
    }
}