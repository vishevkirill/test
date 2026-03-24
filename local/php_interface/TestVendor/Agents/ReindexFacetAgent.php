<?php

namespace TestVendor\Agents;

use Bitrix\Iblock\PropertyIndex\Manager;
use Bitrix\Main\Loader;
use CBitrixComponent;
use CIBlock;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use Throwable;

class ReindexFacetAgent
{
    public static function run(): string
    {
        try {
            if (Loader::includeModule('iblock')) {
                (new self())->execute();
            }
        } catch (Throwable $e) {
            AddMessage2Log('Facet Reindex Error: ' . $e->getMessage());
        }

        return self::class . '::run();';
    }

    public function execute(): void
    {
        $iblockId = IblockHelper::getIdByCode(IblockCodes::CATALOG);

        if (!$iblockId) {
            return;
        }

        Manager::DeleteIndex($iblockId);
        Manager::markAsInvalid($iblockId);

        $index = Manager::createIndexer($iblockId);
        $index->startIndex();

        $isFinished = false;
        while (!$isFinished) {
            $isFinished = !$index->continueIndex(0);
        }

        $index->endIndex();

        Manager::checkAdminNotification();

        CBitrixComponent::clearComponentCache('bitrix:catalog.smart.filter');
        CIBlock::clearIblockTagCache($iblockId);
    }
}