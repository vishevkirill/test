<?php

namespace TestVendor\Agents;

use CEventLog;
use TestVendor\Config;
use TestVendor\Helper\BitrixHelper;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use Throwable;

class UpdateProductBrandAgent
{
    public static function run(): string
    {
        try {
            $iblockId = IblockHelper::getIdByCode(IblockCodes::CATALOG);

            if ($iblockId && class_exists(BitrixHelper::class)) {
                BitrixHelper::updateProductPropertyBrand(
                    Config::AGENT_BRAND_PROP_NAME,
                    Config::AGENT_BRAND_PROP_NEW_CODE,
                    Config::AGENT_BRAND_PROP_OLD_CODE,
                    $iblockId
                );
            }
        } catch (Throwable $e) {
            CEventLog::Add([
                'SEVERITY' => 'ERROR',
                'AUDIT_TYPE_ID' => 'BRAND_AGENT_ERROR',
                'MODULE_ID' => 'iblock',
                'DESCRIPTION' => $e->getMessage(),
            ]);
        }

        return self::class . '::run();';
    }
}