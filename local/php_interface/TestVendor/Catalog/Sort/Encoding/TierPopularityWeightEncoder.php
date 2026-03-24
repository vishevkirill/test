<?php

namespace TestVendor\Catalog\Sort\Encoding;

/**
 * Классический энкодер: tier + score.
 * Меньше значение => выше в выдаче (ASC).
 */
final class TierPopularityWeightEncoder implements WeightEncoderInterface
{
    public function __construct(
        private readonly int $base = 1_000_000_000,
    )
    {
    }

    public function encode(int $tier, int $score): int
    {
        $score = max(0, min($score, $this->base - 1));

        return (int)($tier * $this->base - $score);
    }
}
