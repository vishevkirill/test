<?php

namespace TestVendor\Agents;

use TestVendor\Catalog\Sort\Factories\BrandPopularitySortFactory;
use TestVendor\Catalog\Sort\SortServices;
use TestVendor\Catalog\Sort\SortWeightCron;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use Throwable;

class SortPopularAgent
{
    public static function run(): string
    {
        try {
            $catalogIblockId = IblockHelper::getIdByCode(IblockCodes::CATALOG);

            $services = new SortServices();
            $profile = new BrandPopularitySortFactory();
            $cron = SortWeightCron::fromFactory($profile, $services);

            $cron->run($catalogIblockId);
        } catch (Throwable $e) {
            AddMessage2Log('Calc sort error: ' . $e->getMessage());
        }

        return self::class . '::run();';
    }
}