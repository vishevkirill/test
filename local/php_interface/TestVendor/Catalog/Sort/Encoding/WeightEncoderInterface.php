<?php

namespace TestVendor\Catalog\Sort\Encoding;

interface WeightEncoderInterface
{
    public function encode(int $tier, int $score): int;
}