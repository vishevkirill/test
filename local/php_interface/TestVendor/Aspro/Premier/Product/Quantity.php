<?php

declare(strict_types=1);

namespace TestVendor\Aspro\Premier\Product;

use Aspro\Functions\CAsproPremier as SolutionFunctions;
use Aspro\Premier\Product\Quantity as AsproQuantity;
use CPremier as Solution;
use TestVendor\Catalog\MadeToOrder;

final class Quantity extends AsproQuantity
{
    public static function show(string $statusCode, string $status, array $arOptions = []): void
    {
        if (isset($arOptions['PRODUCT_ID']) && (int)$arOptions['PRODUCT_ID'] > 0) {
            $onRequestData = MadeToOrder::checkProductOnMadeToOrder((int)$arOptions['PRODUCT_ID']);

            if ($onRequestData['SHOW']) {
                $statusCode = 'on_request';
                $status = $onRequestData['CONTENT'];
            }
        }

        $arDefaultOptions = [
            'USE_SHEMA_ORG' => true,
            'IS_DETAIL' => false,
            'CLASS' => 'status-container status-icon',
            'WRAPPER' => false,
            'WRAPPER_CLASS' => '',
            'SHOW_ICON' => true,
            'ICON_CLASS' => 'status__svg-icon mb mb--1',
            'ICON_WRAPPER' => false,
            'ICON_WRAPPER_CLASS' => '',
            'ICON_SVG_PATH' => '/local/templates/.default/images/svg/item_status_icons.svg#'.$statusCode,
            'ICON_SVG_SIZE' => ['WIDTH' => 13, 'HEIGHT' => 12],
            'ICON_USE_SIZE_IN_PATH' => false,
            'TEXT_CLASS' => 'js-replace-status',
            'TEXT_WRAPPER' => false,
            'TEXT_WRAPPER_CALSS' => '',
        ];

        $bDetail = $arOptions['IS_DETAIL'];
        if ($bDetail) {
            $arDefaultOptions = array_merge(
                $arDefaultOptions,
                [
                    'CLASS' => 'status-container catalog-detail__pseudo-link link-opacity-color link-opacity-color--hover color_222',
                    'WRAPPER' => true,
                    'WRAPPER_CLASS' => 'd-block',
                    'ICON_CLASS' => 'status__svg-icon pseudo-link__icon',
                    'ICON_WRAPPER' => true,
                    'ICON_WRAPPER_CLASS' => 'icon-container line-height-0',
                    'ICON_SVG_PATH' => '/local/templates/.default/images/svg/item_status_icons.svg#'.$statusCode.'_lg',
                    'ICON_SVG_SIZE' => ['WIDTH' => 16, 'HEIGHT' => 16],
                    'ICON_USE_SIZE_IN_PATH' => false,
                    'TEXT_CLASS' => 'js-replace-status status-icon',
                    'TEXT_WRAPPER' => true,
                    'TEXT_WRAPPER_CALSS' => 'catalog-detail__pseudo-link-text',
                ]
            );
        }

        $arOptions = array_merge($arDefaultOptions, $arOptions);

        $bUseSchema = $arOptions['USE_SHEMA_ORG'];

        $class = $arOptions['CLASS'].' '.$statusCode;
        $bWrapper = $arOptions['WRAPPER'];
        $wrapperClass = $arOptions['WRAPPER_CLASS'];

        $bShowIcon = $arOptions['SHOW_ICON'];
        $iconClass = $arOptions['ICON_CLASS'];
        $bIconWrapper = $arOptions['ICON_WRAPPER'];
        $iconWrapperClass = $arOptions['ICON_WRAPPER_CLASS'];

        $textClass = $arOptions['TEXT_CLASS'].' '.$statusCode;
        $bTextWrapper = $arOptions['TEXT_WRAPPER'];
        $textWrapperClass = $arOptions['TEXT_WRAPPER_CALSS'];
        ?>
        <?if ($bUseSchema):?>
        <?=SolutionFunctions::showSchemaAvailabilityMeta($statusCode);?>
    <?endif;?>

        <?if ($bWrapper):?>
        <span class="<?=$wrapperClass;?>">
    <?endif;?>

        <span class="<?=$class;?>" data-state="<?=$statusCode;?>">
                <?if ($bShowIcon && $arOptions['ICON_SVG_PATH']):?>
                    <?if ($bIconWrapper):?>
                        <span class="<?=$iconWrapperClass;?>">
                    <?endif;?>
                    <?=Solution::showSpriteIconSvg(
                        $arOptions['ICON_SVG_PATH'].($arOptions['ICON_SVG_SIZE'] && $arOptions['ICON_USE_SIZE_IN_PATH'] ? '-'.implode('-', $arOptions['ICON_SVG_SIZE']) : ''),
                        $iconClass,
                        $arOptions['ICON_SVG_SIZE'],
                    );?>
                    <?if ($bIconWrapper):?>
                        </span>
                    <?endif;?>
                <?endif;?>

            <?if ($bTextWrapper):?>
                    <span class="<?=$textWrapperClass;?>">
                <?endif;?>

                    <span class="<?=$textClass;?>"><?=$status;?></span>

                <?if ($bTextWrapper):?>
                    </span>
                <?endif;?>
            </span>

        <?if ($bWrapper):?>
        </span>
    <?endif;?>
        <?php
    }
}