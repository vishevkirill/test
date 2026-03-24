<?php

namespace TestVendor\Main\Assets;

use Bitrix\Main\Page\Asset;
use TestVendor\Config;

class CustomAssetsRegister
{
    public static function handler(): void
    {
        $asset = Asset::getInstance();

        foreach (Config::CUSTOM_CSS as $cssSrc) {
            $asset->addCss($cssSrc);
        }

        foreach (Config::CUSTOM_JS as $jsSrc) {
            $asset->addJs($jsSrc);
        }
    }
}