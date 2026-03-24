<?php

namespace TestVendor\Aspro\Premier\Stores;

use TestVendor\Catalog\PropertyNameParser;
use Aspro\Premier\Stores\Property as AsproProperty;

class Property extends AsproProperty {

    public static function filterSmartProp(array &$arItems, array $arParams) :void {
        parent::filterSmartProp($arItems, $arParams);

        foreach ($arItems as &$propValue) {
            $parser = new PropertyNameParser();
            $parsed = $parser->parse((string)($propValue['NAME'] ?? ''));

            $propValue['NAME'] = $parsed['name'] !== '' ? $parsed['name'] : (string)($propValue['NAME'] ?? '');
        }

        unset($propValue);
    }
}