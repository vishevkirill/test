<?php

declare(strict_types=1);

namespace TestVendor\Aspro\Premier\Product;

use Aspro\Premier\Product\Common;
use Aspro\Premier\Product\Price;
use Aspro\Premier\Product\Prices as AsproPrices;
use Aspro\Premier\Vendor\Includ;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

final class Prices extends AsproPrices
{
    public function showRow(?array $row, ?bool $bWithPopup = false): void
    {
        if ($row && is_array($row)) {
            $bWithTitle = isset($row['TITLE']) && strlen($row['TITLE']);
            $bWithRange = isset($row['RANGE_TITLE']) && strlen($row['RANGE_TITLE']);
            $bDel = isset($row['IS_DISCOUNT']) && $row['IS_DISCOUNT'];
            $isCurrent = $this->currentPrice['PRICE_ID'] === $row['PRICE_ID'];

            $priceClasses = ['price', $this->options['PRICE_BLOCK_CLASS']];
            if ($bWithPopup) {
                $priceClasses[] = 'price--with-popup price__row';
            }
            if ($bWithTitle) {
                $priceClasses[] = 'price--with-title';
            }
            if ($bWithRange) {
                $priceClasses[] = 'price--with-range';
            }
            if ($this->isDetailPage() && $isCurrent) {
                $priceClasses[] = 'price--current';
            }
            $priceClasses = trim(implode(' ', array_unique($priceClasses)));

            $bCatalogPrices = isset($row['CATALOG_MEASURE']);
            ?>
            <div class="<?= $priceClasses; ?>">
                <? if ($bWithTitle): ?>
                    <div class="price__title font_12 <?= $bWithRange ? 'dark-color fw-500 mb mb--8' : 'secondary-color'; ?>"><?= $row['TITLE']; ?></div>
                <?endif; ?>

                <? if ($bWithRange): ?>
                    <div class="price__range font_12 secondary-color"><span><?= $row['RANGE_TITLE']; ?></span></div>
                <?endif; ?>

                <? if (!$bWithPopup): ?>
                <div class="price__row">
                    <?endif; ?>

                    <? if ($bWithPopup): ?>
                        <?php
                        $pricesTablePopover = new \Aspro\Premier\Popover\PricesTable($this);
                        ?>

                        <button
                                type="button"
                                class="price__popup-toggle xpopover-toggle secondary-color rounded-4"
                                <? $pricesTablePopover->showToggleAttrs(); ?>
                        >
                            <? $pricesTablePopover->showContent(); ?>
                        </button>
                    <?endif; ?>

                    <?php
                    $classPriceNew = '';

                    if (
                            isset($row['VALUE'])
                            && (
                                    $bCatalogPrices
                                    && $row['VALUE'] > $row['DISCOUNT_VALUE']
                            )
                            || (
                                    !$bCatalogPrices
                                    && isset($row['DISCOUNT_VALUE'])
                                    && $row['VALUE'] != $row['DISCOUNT_VALUE']
                            )
                    ) {
                        if ($this->isShowOldPrice()) {
                            $classPriceNew = 'price__new--red';
                        }
                    }
                    ?>
                    <div class="price__new <?= $classPriceNew ?> fw-<?= $this->options['PRICE_WEIGHT']; ?>">
                        <?= '<' . ($bDel ? 'del' : 'span') . ' class="price__new-val font_' . ($bDel ? $this->options['PRICEOLD_FONT'] : $this->options['PRICE_FONT']) . '">'; ?>
                        <? if ($bCatalogPrices): ?>
                            <?= Price::formatWithSchemaByTypes([
                                    'PRICE' => $row,
                                    'CATALOG_MEASURE' => $row['CATALOG_MEASURE'],
                                    'SHOW_SCHEMA' => $this->isShowSchema(),
                            ]); ?>
                        <? else: ?>
                            <?= Price::formatWithSchemaByProps(
                                    $row['DISCOUNT_VALUE'],
                                    $this->isShowSchema(),
                                    $this->getPropsPrices() ?: [],
                                    $row
                            ); ?>
                        <?endif; ?>
                        <?= '</' . ($bDel ? 'del' : 'span') . '>'; ?>
                    </div>

                    <?php


                    if (
                    isset($row['VALUE'])
                    && (
                            $bCatalogPrices
                            && $row['VALUE'] > $row['DISCOUNT_VALUE']
                    )
                    || (
                            !$bCatalogPrices
                            && isset($row['DISCOUNT_VALUE'])
                            && $row['VALUE'] != $row['DISCOUNT_VALUE']
                    )
                    ): ?>
                    <? if ($this->isShowOldPrice()): ?>
                        <div class="price__old fw-<?= $this->options['PRICEOLD_WEIGHT']; ?>">
                            <del class="price__old-val font_<?= $this->options['PRICEOLD_FONT']; ?> secondary-color">
                                <? if ($bCatalogPrices): ?>
                                    <?= $row['PRINT_VALUE']; ?>
                                <? else: ?>
                                    <?= Price::formatWithSchemaByProps(
                                            $row['VALUE'],
                                            false,
                                            $this->item ? ($this->item['PROPERTIES'] ?: $this->item['DISPLAY_PROPERTIES'] ?: $this->item) : [],
                                            $row
                                    ); ?>
                                <?endif; ?>
                            </del>
                        </div>
                    <?endif; ?>

                    <? if ($this->isShowDiscountPercent()): ?>
                    <?php
                    $discountDiff = $row['DISCOUNT_DIFF'] ?? ($bCatalogPrices && $row['VALUE'] > 0 ? ($row['VALUE'] - $row['DISCOUNT_VALUE']) : 0);
                    $discountPercent = $row['DISCOUNT_DIFF_PERCENT'] ?? ($bCatalogPrices && $row['VALUE'] > 0 ? round(($discountDiff / $row['VALUE']) * 100, 0) : 0);
                    ?>
                    <? if ($this->isShowDiscountDiff()): ?>
                    <? if ($discountPercent || $discountDiff): ?>
                    <? if (!$bWithPopup): ?>
                </div><?// class="price__row"?>
                <div class="price__row">
                    <?endif; ?>

                    <div class="price__economy sticker price__economy--with-diff">
                        <? if ($discountPercent): ?>
                            <span class="price__economy-percent sticker__item sticker__item--sale-text font_12">-<?= $discountPercent . ' %'; ?></span>
                        <?endif; ?>

                        <? if ($discountDiff): ?>
                            <span class="price__economy-val sticker__item sticker__item--sale font_12">
                                                <? if ($bCatalogPrices): ?>
                                                    -<?= $row['PRINT_DISCOUNT_DIFF']; ?>
                                                <? else: ?>
                                                    -<?= Price::formatWithSchemaByProps(
                                                            $discountDiff,
                                                            false,
                                                            $this->item ? ($this->item['PROPERTIES'] ?: $this->item['DISPLAY_PROPERTIES'] ?: $this->item) : [],
                                                            $row
                                                    ); ?>
                                                <?endif; ?>
                                            </span>
                        <?endif; ?>
                    </div>
                    <?endif; ?>
                    <? else: ?>
                        <? if ($discountPercent): ?>
                            <div class="price__economy sticker">
                                        <span class="price__economy-percent sticker__item sticker__item--sale-text font_12">
                                            <? if ($bCatalogPrices): ?>
                                                -<?= $discountPercent . '%'; ?>
                                            <? else: ?>
                                                -<?= Price::formatWithSchemaByProps(
                                                        $discountPercent,
                                                        false,
                                                        $this->item ? ($this->item['PROPERTIES'] ?: $this->item['DISPLAY_PROPERTIES'] ?: $this->item) : [],
                                                        $row
                                                ); ?>
                                            <?endif; ?>
                                        </span>
                            </div>
                        <?endif; ?>
                    <?
                    endif; ?>
                    <? else: ?>

                    <?
                    endif; ?>
                    <?endif; ?>

                    <? if (!$bWithPopup): ?>
                    <?= $this->options['EXTENDED_INFO']; ?>
                </div><?// class="price__row"?>
            <?endif; ?>
            </div>
            <?php
        }
    }

    public function captureTable(): string
    {
        $html = '';

        if ($this->item['PRODUCT_ANALOG']) {
            return $html;
        }

        ob_start();
        ?>
        <div class="prices__table flexbox flexbox--direction-column gap gap--8">
            <?php
            if ($this->isUseCompatible()) {
                if ($prices = $this->getMatrixPrices()) {
                    $priceClasses = ['price', $this->options['PRICE_BLOCK_CLASS']];
                    $priceClasses = trim(implode(' ', $priceClasses));

                    $oldColKey = null;
                    $baseColKey = null;

                    foreach (($prices['COLS'] ?? []) as $ck => $col) {
                        $code = (string) ($col['NAME'] ?? '');
                        if ($code === \TestVendor\Config::PRICE_OLD) {
                            $oldColKey = $ck;
                        } elseif ($code === \TestVendor\Config::PRICE_BASE) {
                            $baseColKey = $ck;
                        }
                    }
                    $arDiscountPrices = self::getDiscountPrices();

                    foreach ($prices['MATRIX'] as $colkey => $matrixCol) {
                        $priceTitle = $prices['COLS'][$colkey]['NAME_LANG'] ?? '';
                        $priceCode = $prices['COLS'][$colkey]['NAME'] ?? '';
                        $bDiscountPrice = $arDiscountPrices && isset($arDiscountPrices[$priceCode]);

                        $i = 0;
                        foreach ($matrixCol as $rowkey => $price) {
                            $measureId = $this->item['ITEM_MEASURE']['ID'] ?? $this->item['CATALOG_MEASURE'] ?? '';
                            $measure = $this->item['ITEM_MEASURE']['NAME'] ?? trim($measureId ? Common::showMeasure(Common::getMeasureById($measureId)) : '', '/');

                            $minQnt = $prices['ROWS'][$rowkey]['QUANTITY_FROM'];
                            $bWithRange = $minQnt > 0;
                            if ($bWithRange) {
                                $maxQnt = $prices['ROWS'][$rowkey]['QUANTITY_TO'];
                                $rangeTitle = Loc::getMessage('PRICE_RANGE_FROM').' '.$minQnt.($maxQnt ? ' '.Loc::getMessage('PRICE_RANGE_TO').' '.$maxQnt : '').' '.$measure;
                            }

                            $discountDiff = $discountPercent = 0;
                            if ($price['PRICE'] > 0) {
                                $discountDiff = $price['PRICE'] - $price['DISCOUNT_PRICE'];
                                $discountPercent = round(($discountDiff / $price['PRICE']) * 100, 0);
                            }

                            $row = [
                                    'CURRENCY' => $price['CURRENCY'],
                                    'VALUE' => $price['PRICE'],
                                    'PRINT_VALUE' => \CCurrencyLang::CurrencyFormat($price['PRICE'], $price['CURRENCY'], true),
                                    'DISCOUNT_VALUE' => $price['DISCOUNT_PRICE'],
                                    'PRINT_DISCOUNT_VALUE' => \CCurrencyLang::CurrencyFormat($price['DISCOUNT_PRICE'], $price['CURRENCY'], true),
                                    'DISCOUNT_DIFF' => $discountDiff,
                                    'PRINT_DISCOUNT_DIFF' => \CCurrencyLang::CurrencyFormat($discountDiff, $price['CURRENCY'], true),
                                    'DISCOUNT_DIFF_PERCENT' => $discountPercent,
                                    'PRICE_ID' => $price['ID'],
                                    'PRICE_TYPE_ID' => $colkey,
                                    'CATALOG_MEASURE' => $measureId,
                                    'MEASURE' => $measure,
                                    'IS_DISCOUNT' => $bDiscountPrice,
                            ];

                            $bWithTitle = strlen($priceTitle) && !$i++;
                            if ($bWithTitle) {
                                // show only once for price type (col)
                                $row['TITLE'] = $priceTitle;
                            }

                            if ($bWithRange) {
                                $row['RANGE_TITLE'] = $rangeTitle;
                            }

                            $currentCode = (string) ($prices['COLS'][$colkey]['NAME'] ?? '');

                            if ($oldColKey !== null && $colkey === $oldColKey) {
                                continue;
                            }

                            if ($baseColKey !== null && $colkey === $baseColKey && $oldColKey !== null) {
                                $oldCell = $prices['MATRIX'][$oldColKey][$rowkey] ?? null;

                                $oldNoDiscount = (float) ($oldCell['PRICE'] ?? 0);
                                $baseNoDiscount = (float) ($row['VALUE'] ?? 0);

                                if ($oldNoDiscount > 0 && $baseNoDiscount > 0) {
                                    $delta = $oldNoDiscount - $baseNoDiscount;

                                    if ($delta > 0) {
                                        $row['VALUE'] = $baseNoDiscount + $delta;
                                        $row['PRINT_VALUE'] = \CCurrencyLang::CurrencyFormat($row['VALUE'], (string) ($row['CURRENCY'] ?? ''), true);

                                        $row['DISCOUNT_DIFF'] = $delta;
                                        $row['PRINT_DISCOUNT_DIFF'] = \CCurrencyLang::CurrencyFormat($delta, (string) ($row['CURRENCY'] ?? ''), true);
                                        $base = (float) $row['VALUE'];
                                        $final = (float) ($row['DISCOUNT_VALUE'] ?? 0);

                                        $row['DISCOUNT_DIFF_PERCENT'] = $base > 0
                                                ? (float) abs(round(((100 / $base) * $final) - 100, 0))
                                                : 0.0;
                                    }
                                }
                            }
                            ?>
                            <?$this->showRow($row);?>
                            <?php
                        }
                    }
                } elseif ($prices = $this->getSimplePrices()) {
                    $priceClasses = ['price', $this->options['PRICE_BLOCK_CLASS']];
                    $priceClasses = trim(implode(' ', $priceClasses));

                    $oldColKey = null;
                    $baseColKey = null;

                    foreach (($prices['COLS'] ?? []) as $ck => $col) {
                        $code = (string) ($col['NAME'] ?? '');
                        if ($code === \TestVendor\Config::PRICE_OLD) {
                            $oldColKey = $ck;
                        } elseif ($code === \TestVendor\Config::PRICE_BASE) {
                            $baseColKey = $ck;
                        }
                    }

                    $arDiscountPrices = self::getDiscountPrices();

                    foreach ($prices as $priceCode => $price) {
                        $measureId = $this->item['ITEM_MEASURE']['ID'] ?? $this->item['CATALOG_MEASURE'] ?? '';
                        $measure = $this->item['ITEM_MEASURE']['NAME'] ?? trim($measureId ? Common::showMeasure(Common::getMeasureById($measureId)) : '', '/');

                        $priceTitle = $this->item['CATALOG_GROUP_NAME_'.$price['PRICE_ID']] ?? '';
                        $bDiscountPrice = $arDiscountPrices && isset($arDiscountPrices[$priceCode]);

                        $row = [
                                'CURRENCY' => $price['CURRENCY'],
                                'VALUE' => $price['VALUE'],
                                'PRINT_VALUE' => $price['PRINT_VALUE'],
                                'DISCOUNT_VALUE' => $price['DISCOUNT_VALUE'],
                                'PRINT_DISCOUNT_VALUE' => $price['PRINT_DISCOUNT_VALUE'],
                                'DISCOUNT_DIFF' => $price['DISCOUNT_DIFF'],
                                'PRINT_DISCOUNT_DIFF' => $price['PRINT_DISCOUNT_DIFF'],
                                'DISCOUNT_DIFF_PERCENT' => $price['DISCOUNT_DIFF_PERCENT'],
                                'PRICE_ID' => $price['ID'],
                                'PRICE_TYPE_ID' => $price['PRICE_ID'],
                                'CATALOG_MEASURE' => $measureId,
                                'MEASURE' => $measure,
                                'IS_DISCOUNT' => $bDiscountPrice,
                        ];

                        if (strlen($priceTitle)) {
                            // show only once for price type (col)
                            $row['TITLE'] = $priceTitle;
                        }
                        $currentCode = (string) ($prices['COLS'][$colkey]['NAME'] ?? '');

                        if ($oldColKey !== null && $colkey === $oldColKey) {
                            continue;
                        }

                        if ($baseColKey !== null && $colkey === $baseColKey && $oldColKey !== null) {
                            $oldCell = $prices['MATRIX'][$oldColKey][$rowkey] ?? null;

                            $oldNoDiscount = (float) ($oldCell['PRICE'] ?? 0);
                            $baseNoDiscount = (float) ($row['VALUE'] ?? 0);

                            if ($oldNoDiscount > 0 && $baseNoDiscount > 0) {
                                $delta = $oldNoDiscount - $baseNoDiscount;

                                if ($delta > 0) {
                                    $row['VALUE'] = $baseNoDiscount + $delta;
                                    $row['PRINT_VALUE'] = \CCurrencyLang::CurrencyFormat($row['VALUE'], (string) ($row['CURRENCY'] ?? ''), true);

                                    $row['DISCOUNT_DIFF'] = $delta;
                                    $row['PRINT_DISCOUNT_DIFF'] = \CCurrencyLang::CurrencyFormat($delta, (string) ($row['CURRENCY'] ?? ''), true);
                                    $base = (float) $row['VALUE'];
                                    $final = (float) ($row['DISCOUNT_VALUE'] ?? 0);

                                    $row['DISCOUNT_DIFF_PERCENT'] = $base > 0
                                            ? (float) abs(round(((100 / $base) * $final) - 100, 0))
                                            : 0.0;
                                }
                            }
                        }
                        ?>
                        <?$this->showRow($row);?>
                        <?php
                    }
                }
            } else {
                if (
                        $this->isUseCount()
                        && $prices = $this->getItemPrices()
                ) {
                    $priceClasses = ['price', $this->options['PRICE_BLOCK_CLASS']];
                    $priceClasses = trim(implode(' ', $priceClasses));

                    foreach ($prices as $i => $price) {
                        $measureId = $this->item['ITEM_MEASURE']['ID'] ?? $this->item['CATALOG_MEASURE'];
                        $measure = $this->item['ITEM_MEASURE']['NAME'] ?? trim($measureId ? Common::showMeasure(Common::getMeasureById($measureId)) : '', '/');

                        $minQnt = $price['QUANTITY_FROM'];
                        $bWithRange = $minQnt > 0;
                        if ($bWithRange) {
                            $maxQnt = $price['QUANTITY_TO'];
                            $rangeTitle = Loc::getMessage('PRICE_RANGE_FROM').' '.$minQnt.($maxQnt ? ' '.Loc::getMessage('PRICE_RANGE_TO').' '.$maxQnt : '').' '.$measure;
                        }

                        $row = [
                                'CURRENCY' => $price['CURRENCY'],
                                'VALUE' => $price['BASE_PRICE'],
                                'PRINT_VALUE' => $price['PRINT_BASE_PRICE'],
                                'DISCOUNT_VALUE' => $price['PRICE'],
                                'PRINT_DISCOUNT_VALUE' => $price['PRINT_PRICE'],
                                'DISCOUNT_DIFF' => $price['DISCOUNT'],
                                'PRINT_DISCOUNT_DIFF' => $price['PRINT_DISCOUNT'],
                                'DISCOUNT_DIFF_PERCENT' => $price['PERCENT'],
                                'PRICE_ID' => $price['ID'],
                                'PRICE_TYPE_ID' => $price['PRICE_TYPE_ID'],
                                'CATALOG_MEASURE' => $measureId,
                                'MEASURE' => $measure,
                        ];

                        if ($bWithRange) {
                            $row['RANGE_TITLE'] = $rangeTitle;
                        }
                        ?>
                        <?$this->showRow($row);?>
                        <?php
                    }
                }
            }

            if (!$prices) {
                if ($prices = $this->getCustomPrices()) {
                    $priceClasses = ['price', $this->options['PRICE_BLOCK_CLASS']];
                    $priceClasses = trim(implode(' ', $priceClasses));

                    foreach ($prices as $price) {
                        $currency = $price['CURRENCY'] ?? $price['PRICE_CURRENCY'] ?? '';
                        $discountDiff = $price['DISCOUNT_DIFF'] ?? ($price['PRICE'] > 0 ? ($price['PRICE'] - $price['DISCOUNT_VALUE']) : 0);
                        $discountPercent = $price['DISCOUNT_DIFF_PERCENT'] ?? ($price['VALUE'] > 0 ? round(($discountDiff / $price['VALUE']) * 100, 0) : 0);

                        if (Loader::includeModule('currency')) {
                            $price['PRINT_VALUE'] = $price['PRINT_VALUE'] ?? \CCurrencyLang::CurrencyFormat($price['VALUE'], $currency, true);
                            $price['PRINT_DISCOUNT_VALUE'] = $price['PRINT_DISCOUNT_VALUE'] ?? \CCurrencyLang::CurrencyFormat($price['DISCOUNT_VALUE'], $currency, true);
                            $price['PRINT_DISCOUNT_DIFF'] = $price['PRINT_DISCOUNT_DIFF'] ?? \CCurrencyLang::CurrencyFormat($discountDiff, $currency, true);
                        }

                        $row = [
                                'CURRENCY' => $currency,
                                'VALUE' => $price['VALUE'],
                                'PRINT_VALUE' => $price['PRINT_VALUE'],
                                'DISCOUNT_VALUE' => $price['DISCOUNT_VALUE'],
                                'PRINT_DISCOUNT_VALUE' => $price['PRINT_DISCOUNT_VALUE'],
                                'DISCOUNT_DIFF' => $discountDiff,
                                'PRINT_DISCOUNT_DIFF' => $price['PRINT_DISCOUNT_DIFF'],
                                'DISCOUNT_DIFF_PERCENT' => $discountPercent,
                        ];

                        if (isset($this->item['ITEM_MEASURE']['ID']) || isset($this->item['CATALOG_MEASURE'])) {
                            $measureId = $this->item['ITEM_MEASURE']['ID'] ?? $this->item['CATALOG_MEASURE'] ?? '';
                            $measure = $this->item['ITEM_MEASURE']['NAME'] ?? trim($measureId ? Common::showMeasure(Common::getMeasureById($measureId)) : '', '/');

                            $row['CATALOG_MEASURE'] = $measureId;
                            $row['MEASURE'] = $measure;
                        }
                        ?>
                        <?$this->showRow($row);?>
                        <?php
                    }
                }
            }
            ?>
        </div>
        <?php
        $html = trim(ob_get_clean());

        return $html;
    }
}