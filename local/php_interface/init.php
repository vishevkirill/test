<?php

if (!defined('GLOB_BRACE')) {
    define('GLOB_BRACE', 0);
}

if ($_SERVER['DOCUMENT_ROOT'] . '/bitrix/vendor/autoload.php') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/vendor/autoload.php';
}

require_once __DIR__ . '/bind.php';