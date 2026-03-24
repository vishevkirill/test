<?php

use Bitrix\Main\EventManager;
use TestVendor\Catalog\Plait\ProductCalcBlock;
use TestVendor\Iblock\ArResult\Dispatcher;
use TestVendor\Iblock\Handlers\AddNewTagProductHandler;
use TestVendor\Iblock\Handlers\UpdateFacetIndexHandler;
use TestVendor\Main\Assets\CustomAssetsRegister;
use TestVendor\Main\Handlers\AddCatalogIconToSectionListHandler;
use TestVendor\Main\Handlers\MailEventTemplateHandler;
use TestVendor\Sale\Handlers\BasketItemCheckHandler;
use TestVendor\Sale\Handlers\BasketSaveCheckHandler;
use TestVendor\Sale\Handlers\ComponentOrderJsDataHandler;
use TestVendor\Sale\Handlers\DeliveryExtraServicesClassNamesBuildListHandler;
use TestVendor\Sale\Handlers\DeliveryServiceCalculateHandler;
use TestVendor\Sale\Handlers\OrderAddressUpdateHandler;
use TestVendor\Sale\Handlers\OrderDeliveryExtraServicesPersistHandler;
use TestVendor\Sale\Handlers\OrderDeliveryPriceHandler;
use TestVendor\Sale\Handlers\OrderSaveCheckHandler;
use TestVendor\Sale\Handlers\OrderSavedHandler;
use TestVendor\Sale\PaySystem\BuildRestrictionClassListHandler;
use TestVendor\Vendors\Handlers\YandexDeliveryPriceHandler;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler(
    'main',
    'OnAfterIBlockElementAdd',
    [
        UpdateFacetIndexHandler::class,
        'handler',
    ],
);

$eventManager->addEventHandler(
    'main',
    'OnAfterIBlockElementUpdate',
    [
        UpdateFacetIndexHandler::class,
        'handler',
    ],
);

/**
 * Подключает ограничения платежных систем
 */
$eventManager->addEventHandler(
    'sale',
    'onSalePaySystemRestrictionsClassNamesBuildList',
    [
        BuildRestrictionClassListHandler::class,
        'handler',
    ],
);

/**
 * Подключает custom css|js
 */
$eventManager->addEventHandler(
    'main',
    'OnPageStart',
    [
        CustomAssetsRegister::class,
        'handler',
    ],
);

/**
 * Добавляет модальное окно плайт в </body>
 */
$eventManager->addEventHandler(
    'main',
    'OnEndBufferContent',
    [
        ProductCalcBlock::class,
        'modalHandler',
    ],
);

/**
 * Расчет стоимости доставки
 */
$eventManager->addEventHandler(
    'sale',
    'OnSaleOrderBeforeSaved',
    [
        OrderDeliveryPriceHandler::class,
        'handler',
    ],
);

/**
 * Проверка остатков перед добавлением в корзину
 */
$eventManager->addEventHandler(
    'sale',
    'OnBeforeBasketAdd',
    [
        BasketItemCheckHandler::class,
        'handler',
    ],
);

/**
 * Проверка остатков перед сохранением корзины
 */
$eventManager->addEventHandler(
    'sale',
    'OnSaleBasketBeforeSaved',
    [
        BasketSaveCheckHandler::class,
        'handler',
    ],
);

/**
 * Проверка остатков перед сохранением заказа
 */
$eventManager->addEventHandler(
    'sale',
    'OnSaleOrderBeforeSaved',
    [
        OrderSaveCheckHandler::class,
        'handler',
    ],
);

/**
 * Обновление адреса в свойствах заказа
 */
$eventManager->addEventHandler(
    'sale',
    'OnSaleOrderBeforeSaved',
    [
        OrderAddressUpdateHandler::class,
        'handler',
    ],
);

/**
 * Декодирование HTML сущностей в письмах
 */
$eventManager->addEventHandler(
    'main',
    'OnBeforeEventSend',
    [
        MailEventTemplateHandler::class,
        'handler',
    ],
);

/**
 * JS данные в компоненте заказа
 */
$eventManager->addEventHandler(
    'sale',
    'OnSaleComponentOrderJsData',
    [
        ComponentOrderJsDataHandler::class,
        'handler',
    ],
);

/**
 * Подключает кастомные доп. услуги доставки (extra services)
 */
$eventManager->addEventHandler(
    'sale',
    'onSaleDeliveryExtraServicesClassNamesBuildList',
    [
        DeliveryExtraServicesClassNamesBuildListHandler::class,
        'handler',
    ],
);

/**
 * Расчет доставки с учетом подъема и доставки до Калининграда
 */
$eventManager->addEventHandler(
    'sale',
    'OnSaleDeliveryServiceCalculate',
    [
        DeliveryServiceCalculateHandler::class,
        'handler',
    ],
);

/**
 * Действия после сохранения заказа
 */
$eventManager->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    [
        OrderSavedHandler::class,
        'handler',
    ],
);

/**
 * Вывод иконки раздела в списке товаров админки
 */
$eventManager->addEventHandler(
    'main',
    'OnAdminListDisplay',
    [
        AddCatalogIconToSectionListHandler::class,
        'handler',
    ],
);

/**
 * Добавляет тег новинка в каталог
 */
$eventManager->addEventHandler(
    'iblock',
    Dispatcher::EVENT_NAME,
    [
        AddNewTagProductHandler::class,
        'handler',
    ],
);