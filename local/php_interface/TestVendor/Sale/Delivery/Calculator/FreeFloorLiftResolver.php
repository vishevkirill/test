<?php

namespace TestVendor\Sale\Delivery\Calculator;

use CIBlockElement;
use CIBlockSection;
use CPHPCache;

final class FreeFloorLiftResolver
{
    public static function isFreeForProduct(int $productId): bool
    {
        $productId = ProductParentResolver::resolve($productId);
        if ($productId <= 0) {
            return false;
        }

        $cache = new CPHPCache();
        $cacheId = 'prod' . $productId . 'v1';
        $cacheDir = '/catalog/free_lift';

        if ($cache->InitCache(360000, $cacheId, $cacheDir)) {
            return (bool)$cache->GetVars();
        }

        if (!$cache->StartDataCache()) {
            return false;
        }

        $forcePay = false;
        $freeLift = false;

        $itemDb = CIBlockElement::GetList(
            [],
            [
                'ID' => $productId,
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
            ],
        );

        $item = $itemDb->fetch();
        if (!$item) {
            $cache->AbortDataCache();
            return false;
        }

        if (defined('BX_COMP_MANAGED_CACHE')) {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cacheDir);
        }

        $sections = CIBlockElement::GetElementGroups($item['ID'], true, ['ID']);
        while ($sectionTemp = $sections->Fetch()) {
            $sectDb = CIBlockSection::GetList(
                [
                    'left_margin' => 'DESC',
                ],
                [
                    'IBLOCK_ID' => $item['IBLOCK_ID'],
                    'ID' => $sectionTemp['ID'],
                ],
                false,
                [
                    'ID',
                    'NAME',
                    'DEPTH_LEVEL',
                    'LEFT_MARGIN',
                    'RIGHT_MARGIN',
                    'UF_FREE_FLOOR_LIFT',
                    'UF_FORCE_LIFT_PAY',
                ],
            );

            $section = $sectDb->fetch();
            if (!$section) {
                continue;
            }

            if (!empty($section['UF_FORCE_LIFT_PAY'])) {
                $forcePay = true;
                break;
            }

            if (!empty($section['UF_FREE_FLOOR_LIFT'])) {
                $freeLift = true;
            }

            if ((int)$section['DEPTH_LEVEL'] > 1) {
                $sectRootDb = CIBlockSection::GetList(
                    [
                        'left_margin' => 'DESC',
                    ],
                    [
                        'IBLOCK_ID' => $item['IBLOCK_ID'],
                        '<DEPTH_LEVEL' => $section['DEPTH_LEVEL'],
                        '<LEFT_BORDER' => $section['LEFT_MARGIN'],
                        '>RIGHT_BORDER' => $section['RIGHT_MARGIN'],
                    ],
                    false,
                    [
                        'ID',
                        'NAME',
                        'UF_FREE_FLOOR_LIFT',
                        'UF_FORCE_LIFT_PAY',
                    ],
                );

                while ($sectionRoot = $sectRootDb->fetch()) {
                    if (!empty($sectionRoot['UF_FORCE_LIFT_PAY'])) {
                        $forcePay = true;
                        break;
                    }

                    if (!empty($sectionRoot['UF_FREE_FLOOR_LIFT'])) {
                        $freeLift = true;
                    }
                }

                if ($forcePay) {
                    break;
                }
            }
        }

        if (defined('BX_COMP_MANAGED_CACHE')) {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->RegisterTag('iblock_id_' . $item['IBLOCK_ID']);
            $CACHE_MANAGER->EndTagCache();
        }

        $result = !$forcePay && $freeLift;
        $cache->EndDataCache($result);

        return $result;
    }
}
