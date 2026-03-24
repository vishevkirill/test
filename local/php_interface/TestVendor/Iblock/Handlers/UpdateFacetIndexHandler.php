<?php

namespace TestVendor\Iblock\Handlers;

use Bitrix\Iblock\PropertyIndex\Manager;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CIBlock;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;

class UpdateFacetIndexHandler
{
    /**
     * @throws LoaderException
     */
    public static function handler(&$arFields): void
    {
        if (!Loader::includeModule('iblock')) {
            return;
        }

        $iblockId = IblockHelper::getIdByCode(IblockCodes::CATALOG);

        if ($arFields['IBLOCK_ID'] === $iblockId) {
            CIBlock::clearIblockTagCache($iblockId);
            Manager::updateElementIndex($iblockId, $arFields['ID']);
        }
    }
}