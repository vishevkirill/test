<?php

namespace TestVendor\Catalog\Sort\Product;

final class SortContext
{
    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->data[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }

        return (int)$value;
    }

    public function nullableInt(string $key): ?int
    {
        $value = $this->data[$key] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        $intValue = (int)$value;

        return $intValue > 0 ? $intValue : null;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->data[$key] ?? null;
        if ($value === null) {
            return $default;
        }

        return (string)$value;
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->data[$key] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        $str = (string)$value;

        return $str !== '' ? $str : null;
    }
}
