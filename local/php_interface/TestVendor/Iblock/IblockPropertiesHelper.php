<?php

namespace TestVendor\Iblock;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;

final class IblockPropertiesHelper
{
    private int $iblockId;
    
    private array $codeToId = [];
    
    public function __construct(int $iblockId)
    {
        Loader::includeModule('iblock');
        
        $this->iblockId = $iblockId;
        $this->load();
    }
    
    public function getId(string $code): ?int
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        
        return $this->codeToId[$code] ?? null;
    }
    
    public function all(): array
    {
        return $this->codeToId;
    }
    
    public function reload(): void
    {
        $this->codeToId = [];
        $this->load();
    }
    
    private function load(): void
    {
        $result = PropertyTable::getList([
            'filter' => [
                '=IBLOCK_ID' => $this->iblockId,
            ],
            'select' => [
                'ID',
                'CODE',
            ],
            'order' => [
                'ID' => 'ASC',
            ],
            'cache' => [
                'ttl' => 0,
            ]
        ]);
        
        while ($row = $result->fetch()) {
            $code = (string)($row['CODE'] ?? '');
            if ($code === '') {
                continue;
            }
            
            $this->codeToId[$code] = (int)$row['ID'];
        }
    }
}