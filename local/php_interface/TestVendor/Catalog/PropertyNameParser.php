<?php

namespace TestVendor\Catalog;

final class PropertyNameParser
{
    private array $delimiter;
    private string $defaultGroupName;

    public function __construct(
        string $defaultGroupName = 'Общие характеристики',
    )
    {
        $this->defaultGroupName = $defaultGroupName;
        $this->delimiter = [
            '##',
            '||',
            '@@',
        ];
    }

    /**
     * Форматы:
     * 1) Категория||Группа||Имя
     * 2) Группа||Имя
     * 3) Имя (без разделителя) -> уйдет в defaultGroupName
     *
     * @return array{group: string, name: string}
     */
    public function parse(string $rawName): array
    {
        $rawName = trim($rawName);
        if ($rawName === '') {
            return [
                'group' => $this->defaultGroupName,
                'name' => '',
            ];
        }

        $pattern = '/(' . implode('|', array_map('preg_quote', $this->delimiter)) . ')/';

        $parts = array_values(array_filter(
            array_map('trim', preg_split($pattern, $rawName)),
            static fn($v) => $v !== ''
        ));

        $count = count($parts);

        if ($count >= 3) {
            return [
                'group' => $parts[1],
                'name' => $parts[2],
            ];
        }

        if ($count === 2) {
            return [
                'group' => $parts[0],
                'name' => $parts[1],
            ];
        }

        return [
            'group' => $this->defaultGroupName,
            'name' => $rawName,
        ];
    }
}