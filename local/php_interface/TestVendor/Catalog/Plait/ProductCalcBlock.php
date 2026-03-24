<?php

namespace TestVendor\Catalog\Plait;

use CCurrencyLang;

class ProductCalcBlock
{
    /**
     * Блок плайт
     *
     * @param $price
     * @return string
     */
    public static function getBlock($price): string
    {
        global $APPLICATION;
        $pricePerPayment = $price / 4;

        ob_start();
        $APPLICATION->IncludeFile(
            SITE_DIR . 'include/blocks/catalog/plait_block.php',
            [
                'PRICE' => number_format($pricePerPayment, 0, '.', ' '),
                'CURRENCY' => str_replace("999", "", CCurrencyLang::CurrencyFormat("999", 'RUB')),
            ],
            [
                'SHOW_BORDER' => false,
            ]
        );
        return ob_get_clean();
    }

    public static function getModal(): string
    {
        return file_get_contents($_SERVER['DOCUMENT_ROOT'] . SITE_DIR . 'include/blocks/catalog/plait_modal.php');
    }

    public static function modalHandler(&$content): void
    {
        $modal = self::getModal();

        if (str_contains($content, '</body>')) {
            $content = str_replace(
                '</body>',
                $modal . '</body>',
                $content
            );
        }
    }
}