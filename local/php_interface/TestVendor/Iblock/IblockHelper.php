<?php

namespace TestVendor\Iblock;

use Bitrix\Iblock\IblockSiteTable;
use Bitrix\Main\Loader;

class IblockHelper
{
    private array $data;

    private static $instance;

    private function __construct()
    {
        Loader::includeModule('iblock');
        $data = IblockSiteTable::getList([
            'select' => [
                'IBLOCK_ID',
                'IBLOCK_CODE' => 'IBLOCK.CODE'
            ],
            'filter' => [
                'IBLOCK.ACTIVE' => 'Y',
            ],
            'cache' => [
                'ttl' => 0,
            ]
        ])->fetchAll();

        $this->data = array_column($data, 'IBLOCK_ID', 'IBLOCK_CODE');
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getIdByCode(string $code): int
    {
        $instance = self::getInstance();
        return (int)$instance->data[$code];
    }
}