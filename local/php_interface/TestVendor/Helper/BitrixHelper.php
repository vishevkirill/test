<?php

namespace TestVendor\Helper;

use Bitrix\Catalog\Model\Product;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use CCatalogStoreProduct;
use CIBlockElement;
use CIBlockProperty;
use CIBlockPropertyEnum;
use CIBlockSection;
use CPrice;
use CUtil;

class BitrixHelper
{
    public const TRANSLIT_PARAMS = [
        'max_len' => '128',                // Обрезает символьный код до 128 символов
        'change_case' => 'L',              // Буквы преобразуются к нижнему регистру
        'replace_space' => '_',            // Меняем пробелы на нижнее подчеркивание
        'replace_other' => '_',            // Меняем левые символы на нижнее подчеркивание
        'delete_repeat_replace' => 'true', // Удаляем повторяющиеся нижние подчеркивания
        'use_google' => 'false',           // Отключаем использование google
    ];

    /**
     * Получает раздел инфоблока по XML_ID.
     */
    public static function getSectionByXMLID(
        int    $IBLOCK_ID,
        string $XML_ID,
        array  $arSelect
    ): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        return CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
                'XML_ID' => $XML_ID,
            ],
            false,
            $arSelect,
            false
        )->Fetch();
    }

    /**
     * Получает элемент инфоблока по XML_ID.
     */
    public static function getElementByXMLID(int $IBLOCK_ID, string $XML_ID, array $arSelect): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        return CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
                'XML_ID' => $XML_ID,
            ],
            false,
            false,
            $arSelect
        )->Fetch();
    }

    /**
     * Добавляет элемент инфоблока.
     */
    public static function addElement($arFields): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $blockElement = new CIBlockElement();
        $elementID = $blockElement->Add($arFields);

        return $elementID ? ['ID' => $elementID] : ['ERROR' => $blockElement->LAST_ERROR];
    }

    /**
     * Обновляет элемент инфоблока.
     */
    public static function updateElement($elementID, $arFields): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $blockElement = new CIBlockElement();
        $elementID = $blockElement->Update($elementID, $arFields);

        return $elementID ? ['ID' => $elementID] : ['ERROR' => $blockElement->LAST_ERROR];
    }

    /**
     * Добавляет свойство инфоблока (CODE генерируется из NAME через транслит).
     */
    public static function addProperty(array $arFields): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $cibp = new CIBlockProperty();
        $arFields['CODE'] = CUtil::translit(trim($arFields['NAME']), 'ru', self::TRANSLIT_PARAMS);
        $arFields['ID'] = $cibp->Add($arFields);

        return $arFields;
    }

    /**
     * Получает свойство инфоблока по XML_ID.
     */
    public static function getPropertyByXMLID(int $IBLOCK_ID, string $XML_ID): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        return CIBlockProperty::GetList(
            [],
            [
                'ACTIVE' => 'Y',
                'IBLOCK_ID' => $IBLOCK_ID,
                'XML_ID' => $XML_ID,
            ]
        )->Fetch();
    }

    /**
     * Получает свойство инфоблока по символьному коду.
     */
    public static function getPropertyByCode(int $iblockId, string $code): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $result = CIBlockProperty::GetList(
            [],
            [
                'ACTIVE' => 'Y',
                'IBLOCK_ID' => $iblockId,
                'CODE' => $code,
            ]
        )->Fetch();

        return !empty($result) ? $result : [];
    }

    /**
     * Получает свойства товара (элемента) за исключением указанных кодов.
     */
    public static function getProductProperties(int $IBLOCK_ID, int $elementID, array $exception): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $result = [];

        $propertiesGetList = CIBlockElement::GetProperty($IBLOCK_ID, $elementID, [], []);
        while ($propertiesGetListItem = $propertiesGetList->GetNext()) {
            if (in_array($propertiesGetListItem['CODE'], $exception, true)) {
                continue;
            }

            $result[$propertiesGetListItem['CODE']] = $propertiesGetListItem['VALUE'];
        }

        return $result;
    }

    /**
     * Добавляет значение перечисления (enum) для свойства.
     */
    public static function addPropertyValue($arFields): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $cibpe = new CIBlockPropertyEnum();
        $result['ID'] = $cibpe->Add($arFields);

        return $result;
    }

    /**
     * Получает значение перечисления свойства по XML_ID.
     */
    public static function getPropertyValueByXMLID($IBLOCK_ID, $arFields): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        return CIBlockPropertyEnum::GetList(
            [],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
                'CODE' => $arFields['CODE'],
                'XML_ID' => $arFields['XML_ID'],
            ]
        )->Fetch();
    }

    /**
     * Получает значение перечисления свойства по NAME.
     */
    public static function getPropertyValueByName($IBLOCK_ID, $arFields): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        return CIBlockPropertyEnum::GetList(
            [],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
                'CODE' => $arFields['CODE'],
                'NAME' => $arFields['NAME'],
            ]
        )->Fetch();
    }

    /**
     * Обновляет/добавляет цены элементов по нескольким типам цен.
     */
    public static function updateElementPrice(array $elements): array
    {
        if (!Loader::includeModule('catalog')) {
            return [];
        }

        $result = [];

        // $priceTypeId = 1;
        $priceTypeId = 4;
        $priceTypeId_Old = 3;
        $priceTypeId_Market = 5;
        $priceTypeId_Market_10 = 6;
        $currencyCode = 'RUB';

        foreach ($elements as $element) {
            // Текущий прайс (цена розница)
            $arFields = [
                'PRODUCT_ID' => $element['ID'],
                'CATALOG_GROUP_ID' => $priceTypeId,
                'PRICE' => $element['PRICE'],
                'CURRENCY' => $currencyCode,
            ];

            $resultPrice = CPrice::GetList(
                [],
                [
                    'PRODUCT_ID' => $element['ID'],
                    'CATALOG_GROUP_ID' => $priceTypeId,
                ]
            )->Fetch();

            $obPrice = new CPrice();

            if (!empty($resultPrice)) {
                $resultUpdate = $obPrice->Update($resultPrice['ID'], $arFields);

                $result[] = $resultUpdate
                    ? ['id' => $element['ID'], 'success' => self::getError('successUpdatePrice')]
                    : ['id' => $element['ID'], 'error' => self::getError('errorUpdatePrice')];
            } else {
                $resultAdd = $obPrice->Add($arFields);

                $result[] = $resultAdd
                    ? ['id' => $element['ID'], 'success' => self::getError('successAddPrice')]
                    : ['id' => $element['ID'], 'error' => self::getError('errorAddPrice')];
            }

            // Текущий прайс (старая цена)
            $arFields = [
                'PRODUCT_ID' => $element['ID'],
                'CATALOG_GROUP_ID' => $priceTypeId_Old,
                'PRICE' => $element['PRICEOLD'],
                'CURRENCY' => $currencyCode,
            ];

            $resultPrice = CPrice::GetList(
                [],
                [
                    'PRODUCT_ID' => $element['ID'],
                    'CATALOG_GROUP_ID' => $priceTypeId_Old,
                ]
            )->Fetch();

            $obPrice = new CPrice();

            if (!empty($resultPrice)) {
                if (empty($element['PRICEOLD'])) {
                    $obPrice->Delete($resultPrice['ID']);
                } else {
                    $resultUpdate = $obPrice->Update($resultPrice['ID'], $arFields);

                    $result[] = $resultUpdate
                        ? ['id' => $element['ID'], 'success' => self::getError('successUpdatePrice')]
                        : ['id' => $element['ID'], 'error' => self::getError('errorUpdatePrice')];
                }
            } else {
                if (!empty($element['PRICEOLD'])) {
                    $resultAdd = $obPrice->Add($arFields);

                    $result[] = $resultAdd
                        ? ['id' => $element['ID'], 'success' => self::getError('successAddPrice')]
                        : ['id' => $element['ID'], 'error' => self::getError('errorAddPrice')];
                }
            }

            // Текущий прайс (Price_market)
            $arFields = [
                'PRODUCT_ID' => $element['ID'],
                'CATALOG_GROUP_ID' => $priceTypeId_Market,
                'PRICE' => $element['PRICE_MARKET'],
                'CURRENCY' => $currencyCode,
            ];

            $resultPrice = CPrice::GetList(
                [],
                [
                    'PRODUCT_ID' => $element['ID'],
                    'CATALOG_GROUP_ID' => $priceTypeId_Market,
                ]
            )->Fetch();

            $obPrice = new CPrice();

            if (!empty($resultPrice)) {
                if (empty($element['PRICE_MARKET'])) {
                    $obPrice->Delete($resultPrice['ID']);
                } else {
                    $resultUpdate = $obPrice->Update($resultPrice['ID'], $arFields);

                    $result[] = $resultUpdate
                        ? ['id' => $element['ID'], 'success' => self::getError('successUpdatePrice')]
                        : ['id' => $element['ID'], 'error' => self::getError('errorUpdatePrice')];
                }
            } else {
                if (!empty($element['PRICE_MARKET'])) {
                    $resultAdd = $obPrice->Add($arFields);

                    $result[] = $resultAdd
                        ? ['id' => $element['ID'], 'success' => self::getError('successAddPrice')]
                        : ['id' => $element['ID'], 'error' => self::getError('errorAddPrice')];
                }
            }

            // Текущий прайс (Price_market_10)
            $arFields = [
                'PRODUCT_ID' => $element['ID'],
                'CATALOG_GROUP_ID' => $priceTypeId_Market_10,
                'PRICE' => $element['PRICE_MARKET_10'],
                'CURRENCY' => $currencyCode,
            ];

            $resultPrice = CPrice::GetList(
                [],
                [
                    'PRODUCT_ID' => $element['ID'],
                    'CATALOG_GROUP_ID' => $priceTypeId_Market_10,
                ]
            )->Fetch();

            $obPrice = new CPrice();

            if (!empty($resultPrice)) {
                if (empty($element['PRICE_MARKET_10'])) {
                    $obPrice->Delete($resultPrice['ID']);
                } else {
                    $resultUpdate = $obPrice->Update($resultPrice['ID'], $arFields);

                    $result[] = $resultUpdate
                        ? ['id' => $element['ID'], 'success' => self::getError('successUpdatePrice')]
                        : ['id' => $element['ID'], 'error' => self::getError('errorUpdatePrice')];
                }
            } else {
                if (!empty($element['PRICE_MARKET_10'])) {
                    $resultAdd = $obPrice->Add($arFields);

                    $result[] = $resultAdd
                        ? ['id' => $element['ID'], 'success' => self::getError('successAddPrice')]
                        : ['id' => $element['ID'], 'error' => self::getError('errorAddPrice')];
                }
            }
        }

        return $result;
    }

    /**
     * Обновляет/создаёт количество товара (QUANTITY) в каталоге.
     */
    public static function updateElementQuantity(array $elements): array
    {
        if (!Loader::includeModule('catalog')) {
            return [];
        }

        $result = [];

        foreach ($elements as $element) {
            $arFields = [
                'ID' => $element['ID'],
                'QUANTITY' => $element['QUANTITY'],
            ];

            $existProduct = Product::getCacheItem($arFields['ID'], true);

            if (!empty($existProduct)) {
                $resultUpdate = Product::update($element['ID'], $arFields);

                $result[] = $resultUpdate
                    ? ['id' => $element['ID'], 'success' => self::getError('successUpdateQuantity')]
                    : ['id' => $element['ID'], 'error' => self::getError('errorUpdateQuantity')];
            } else {
                $resultAdd = Product::add($arFields);

                $result[] = $resultAdd
                    ? ['id' => $element['ID'], 'success' => self::getError('successAddQuantity')]
                    : ['id' => $element['ID'], 'error' => self::getError('errorAddQuantity')];
            }
        }

        return $result;
    }

    /**
     * Обновляет количество товара на конкретном складе (один склад в структуре STORE).
     */
    public static function updateElementQuantityStore(array $elements): array
    {
        if (!Loader::includeModule('catalog')) {
            return [];
        }

        $result = [];

        foreach ($elements as $element) {
            if (empty($element['STORE'])) {
                continue;
            }

            $storeProductTableId = 0;

            $storeProductTableGetList = StoreProductTable::getList([
                'filter' => [
                    '=PRODUCT_ID' => $element['STORE']['PRODUCT_ID'],
                    '=STORE.ACTIVE' => 'Y',
                    '=STORE.ID' => $element['STORE']['STORE_ID'],
                ],
                'select' => [
                    'ID',
                    'AMOUNT',
                    'STORE_ID',
                    'STORE_TITLE' => 'STORE.TITLE',
                ],
            ]);

            while ($storeProductTableGetListItem = $storeProductTableGetList->fetch()) {
                $storeProductTableId = $storeProductTableGetListItem['ID'];
            }

            if (!empty($storeProductTableId)) {
                $arFields = [
                    'PRODUCT_ID' => $element['STORE']['PRODUCT_ID'],
                    'STORE_ID' => $element['STORE']['STORE_ID'],
                    'AMOUNT' => $element['STORE']['QUANTITY'],
                ];

                $resultUpdate = CCatalogStoreProduct::Update($storeProductTableId, $arFields);

                $result[] = $resultUpdate
                    ? ['id' => $element['ID'], 'success' => self::getError('successUpdateQuantityStore')]
                    : ['id' => $element['ID'], 'error' => self::getError('errorUpdateQuantityStore')];
            }
        }

        return $result;
    }

    /**
     * Обновляет количество по складам (STORES) и пишет лог в файл.
     */
    public static function updateElementQuantityStores(array $elements): array
    {
        if (!Loader::includeModule('catalog')) {
            return [];
        }

        $result = [];
        $logData = [];

        $logData[] = 'Выгрузка началась №' . rand();

        foreach ($elements as $element) {
            $productId = $element['ID'];
            $stores = $element['STORES'];

            if (empty($stores)) {
                $logData[] = 'Пустые склады, товар с ID ' . $productId;
                continue;
            }

            $logData[] = 'Товар обработки ID: ' . $productId;

            foreach ($stores as $storesItem) {
                $storeId = self::getStoreIdByXmlId($storesItem['UidSklad']);
                $storeQuantity = $storesItem['KolNaSklade'];

                if (!$storeId) {
                    $result[] = [
                        'id' => $productId . ' :Склад ' . $storesItem['UidSklad'],
                        'error' => 'Не удалось получить ID склада',
                    ];
                    $logData[] = 'Error: Не удалось получить ID склада для товара с ID '
                        . $productId . ' и UID склада ' . $storesItem['UidSklad'];
                    continue;
                }

                $logData[] = 'Склад ID: ' . $storeId . ', Количество: ' . $storeQuantity;

                $storeProductTableId = '';

                $storeProductTableGetList = StoreProductTable::getList([
                    'filter' => [
                        '=PRODUCT_ID' => $productId,
                        '=STORE.ACTIVE' => 'Y',
                        '=STORE.ID' => $storeId,
                    ],
                    'select' => [
                        'ID',
                        'AMOUNT',
                        'STORE_ID',
                        'STORE_TITLE' => 'STORE.TITLE',
                    ],
                ]);

                $arFields = [
                    'PRODUCT_ID' => $productId,
                    'STORE_ID' => $storeId,
                    'AMOUNT' => $storeQuantity,
                ];

                if ($storeProductTableGetListItem = $storeProductTableGetList->fetch()) {
                    $storeProductTableId = $storeProductTableGetListItem['ID'];
                    $logData[] = 'Ввод продуктов в магазине ID: ' . $storeProductTableId;
                } else {
                    $resultUpdate = CCatalogStoreProduct::Add($arFields);
                    $logData[] = 'Не существует остатков на складе, добавлено автоматически с ID ' . $resultUpdate;
                }

                if (!empty($storeProductTableId)) {
                    $resultUpdate = CCatalogStoreProduct::Update($storeProductTableId, $arFields);

                    if ($resultUpdate) {
                        $result[] = [
                            'id' => $productId . ' :Склад ' . $storeId,
                            'success' => self::getError('successUpdateQuantityStore'),
                        ];
                        $logData[] = 'Success: Обновлено количество на складе для товара с ID '
                            . $productId . ', StoreID ' . $storeId . ', Количество ' . $storeQuantity;
                    } else {
                        $result[] = [
                            'id' => $productId . ' :Склад ' . $storeId,
                            'error' => self::getError('errorUpdateQuantityStore'),
                        ];
                        $logData[] = 'Error: Не удалось обновить количество на складе для товара с ID '
                            . $productId . ', Склад ID ' . $storeId;
                    }
                } else {
                    $logData[] = 'Error: Не удалось добавить новую запись для товара с ID ' . $productId;
                }
            }
        }

        // Создание/обновление лог-файла
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/logStoresUpdate.txt';
        $logContent = implode("\n", $logData);
        file_put_contents($logFile, $logContent);

        return $result;
    }

    /**
     * Обнуляет остатки товара на всех активных складах.
     */
    public static function updateElementNulledQuantityStores(array $elements): array
    {
        if (!Loader::includeModule('catalog')) {
            return [];
        }

        $result = [];

        foreach ($elements as $element) {
            $productId = $element['ID'];
            $storeQuantity = 0;

            $storeProductTableGetList = StoreProductTable::getList([
                'filter' => [
                    '=PRODUCT_ID' => $productId,
                    '=STORE.ACTIVE' => 'Y',
                ],
                'select' => [
                    'ID',
                    'AMOUNT',
                    'STORE_ID',
                    'STORE_TITLE' => 'STORE.TITLE',
                ],
            ]);

            while ($storeProductTableGetListItem = $storeProductTableGetList->fetch()) {
                $arFields = [
                    'PRODUCT_ID' => $productId,
                    'STORE_ID' => $storeProductTableGetListItem['STORE_ID'],
                    'AMOUNT' => $storeQuantity,
                ];

                $resultUpdate = CCatalogStoreProduct::Update($storeProductTableGetListItem['ID'], $arFields);

                $result[] = $resultUpdate
                    ? [
                        'id' => $productId . ' :Склад ' . $storeProductTableGetListItem['STORE_ID'],
                        'success' => self::getError('successUpdateQuantityStore'),
                    ]
                    : [
                        'id' => $productId . ' :Склад ' . $storeProductTableGetListItem['STORE_ID'],
                        'error' => self::getError('errorUpdateQuantityStore'),
                    ];
            }
        }

        return $result;
    }

    /**
     * Получает ID склада по XML_ID.
     */
    public static function getStoreIdByXmlId(string $code): int
    {
        if (!Loader::includeModule('catalog')) {
            return 0;
        }

        $store = \CCatalogStore::GetList(
            ['ID' => 'ASC'],
            [
                'ACTIVE' => 'Y',
                'XML_ID' => $code,
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'XML_ID',
            ]
        )->GetNext();

        return (int)$store['ID'];
    }

    /**
     * Возвращает текст сообщения по ключу.
     */
    private static function getError(string $key): string
    {
        $errors = [
            'successUpdatePrice' => 'Успех обновления цены',
            'errorUpdatePrice' => 'Ошибка обновления цены',
            'successAddPrice' => 'Успех добавления цены',
            'errorAddPrice' => 'Ошибка добавления цены',
            'successUpdateQuantity' => 'Успех обновления количества',
            'errorUpdateQuantity' => 'Ошибка обновления количества',
            'successUpdateQuantityStore' => 'Успех обновления количества на складе',
            'errorUpdateQuantityStore' => 'Ошибка обновления количества на складе',
            'successAddQuantity' => 'Успех добавления количества',
            'errorAddQuantity' => 'Ошибка добавления количества',
        ];

        return $errors[$key];
    }

    /**
     * Ищет свойства по имени в инфоблоке и возвращает удобные массивы (CODE=>ID, IDs, данные).
     */
    public static function getPropertiesByPropertyName(string $name, int $iblockId): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $order = ['SORT' => 'ASC'];
        $filter = [
            'ACTIVE' => 'Y',
            'NAME' => $name,
            'IBLOCK_ID' => $iblockId,
        ];

        $properties = \Bitrix\Iblock\PropertyTable::getList([
            'order' => $order,
            'filter' => $filter,
            'cache' => [
                'ttl' => 60 * 60 * 3,
                'cache_joins' => true,
            ],
        ]);

        $result = [];

        while ($property = $properties->fetch()) {
            $result['propertiesCodeArr'][$property['CODE']] = $property['ID'];
            $result['propertiesIdArr'][] = $property['ID'];
            $result['propertiesArr'][$property['ID']] = $property;
        }

        return $result;
    }

    /**
     * Находит enum-значение свойства по текстовому VALUE.
     */
    public static function getPropertyEnumByValue(int $propertyId, string $value): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $filter = [
            'PROPERTY_ID' => $propertyId,
            'VALUE' => $value,
        ];

        $resultGetList = PropertyEnumerationTable::getList([
            'filter' => $filter,
            'cache' => [
                'ttl' => 60 * 60 * 3,
                'cache_joins' => true,
            ],
        ])->fetch();

        return !empty($resultGetList) ? $resultGetList : [];
    }

    /**
     * Переносит значения всех свойств с указанным NAME в одно свойство с CODE (с учётом типа L/S).
     */
    public static function updateProductPropertyByName(string $name, string $code, int $iblockId): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $resultGetPropertiesByPropertyName = self::getPropertiesByPropertyName($name, $iblockId);
        $propertiesIdArr = $resultGetPropertiesByPropertyName['propertiesIdArr'];
        $propertiesCodeArr = $resultGetPropertiesByPropertyName['propertiesCodeArr'];
        $propertiesArr = $resultGetPropertiesByPropertyName['propertiesArr'];

        $propertyCurrentId = $propertiesArr[$propertiesCodeArr[$code]]['ID'];
        $propertyCurrentType = $propertiesArr[$propertiesCodeArr[$code]]['PROPERTY_TYPE'];

        $result = [];

        if (!empty($propertiesIdArr)) {
            $elementsArr = [];

            $order = ['ID' => 'ASC'];
            $select = [
                'ID',
                'NAME',
                'PROPERTY',
            ];
            $filter = [
                'ACTIVE' => 'Y',
                'IBLOCK_ID' => $iblockId,
                '!PROPERTY.VALUE' => false,
            ];

            $elements = ElementTable::getList([
                'order' => $order,
                'select' => $select,
                'filter' => $filter,
                'cache' => [
                    'ttl' => 60 * 60 * 3,
                    'cache_joins' => true,
                ],
                'runtime' => [
                    new ReferenceField(
                        'PROPERTY',
                        ElementPropertyTable::getEntity(),
                        ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID'],
                        ['join_type' => 'INNER']
                    ),
                ],
            ]);

            while ($element = $elements->fetch()) {
                if (!empty($propertiesArr[$element['IBLOCK_ELEMENT_PROPERTY_IBLOCK_PROPERTY_ID']])) {
                    $elementsArr[$element['ID']][] = [
                        'PROPERTY_ID' => $propertiesArr[$element['IBLOCK_ELEMENT_PROPERTY_IBLOCK_PROPERTY_ID']]['ID'],
                        'PROPERTY_CODE' => $propertiesArr[$element['IBLOCK_ELEMENT_PROPERTY_IBLOCK_PROPERTY_ID']]['CODE'],
                        'PROPERTY_TYPE' => $propertiesArr[$element['IBLOCK_ELEMENT_PROPERTY_IBLOCK_PROPERTY_ID']]['PROPERTY_TYPE'],
                        'ID' => $element['IBLOCK_ELEMENT_PROPERTY_ID'],
                        'VALUE' => $element['IBLOCK_ELEMENT_PROPERTY_VALUE'],
                    ];
                }
            }

            foreach ($elementsArr as $key => $elementsArrItem) {
                foreach ($elementsArrItem as $propertyItem) {
                    if ($propertyItem['PROPERTY_CODE'] === $code) {
                        continue;
                    }

                    $propertyValue = '';

                    if ($propertyItem['PROPERTY_TYPE'] === 'L') {
                        $filter = [
                            'ID' => $propertyItem['VALUE'],
                        ];

                        $propertyEnum = PropertyEnumerationTable::getList([
                            'filter' => $filter,
                            'cache' => [
                                'ttl' => 60 * 60 * 3,
                                'cache_joins' => true,
                            ],
                        ])->fetch();

                        $propertyValue = $propertyEnum['VALUE'];
                    } elseif ($propertyItem['PROPERTY_TYPE'] === 'S') {
                        $propertyValue = $propertyItem['VALUE'];
                    }

                    if ($propertyCurrentType === 'L') {
                        $arFields = [
                            'PROPERTY_ID' => $propertyCurrentId,
                            'VALUE' => $propertyValue,
                        ];

                        $propertyEnumValue = self::getPropertyEnumByValue($propertyCurrentId, $propertyValue);

                        if (!empty($propertyEnumValue)) {
                            $value = $propertyEnumValue['ID'];
                        } else {
                            $value = self::addPropertyValue($arFields);
                        }
                    } elseif ($propertyCurrentType === 'S') {
                        $value = $propertyValue;
                    }

                    if (!empty($value)) {
                        $propertiesSetFields = [$code => $value];
                        CIBlockElement::SetPropertyValuesEx($key, $iblockId, $propertiesSetFields);
                        $result['success'][] = 'Свойство ' . $code . ' Товара ' . $key . ' обновлено';
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Переносит значения в E-свойство: сопоставляет значение старого свойства с именем элемента связанного инфоблока.
     */
    public static function updatePropertyEProduct(string $codeNewProperty, string $codeOldProperty, int $iblockId): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $codeNewPropertyArr = self::getPropertyByCode($iblockId, $codeNewProperty);
        $codeOldPropertyArr = self::getPropertyByCode($iblockId, $codeOldProperty);

        $elementsPropertyTypeEArr = [];

        $order = ['ID' => 'ASC'];
        $select = [
            'ID',
            'NAME',
        ];
        $filter = [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => $codeNewPropertyArr['LINK_IBLOCK_ID'],
        ];

        $elementsPropertyTypeE = ElementTable::getList([
            'order' => $order,
            'select' => $select,
            'filter' => $filter,
            'cache' => [
                'ttl' => 60 * 60 * 3,
                'cache_joins' => true,
            ],
        ]);

        while ($elementPropertyTypeE = $elementsPropertyTypeE->fetch()) {
            $elementsPropertyTypeEArr[$elementPropertyTypeE['ID']] = $elementPropertyTypeE['NAME'];
        }

        $elementsArr = [];

        $order = ['ID' => 'ASC'];
        $select = [
            'ID',
            'NAME',
        ];
        $filter = [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => $iblockId,
        ];

        $elements = ElementTable::getList([
            'order' => $order,
            'select' => $select,
            'filter' => $filter,
            'cache' => [
                'ttl' => 60 * 60 * 3,
                'cache_joins' => true,
            ],
        ]);

        while ($element = $elements->fetch()) {
            $elementsArr[] = $element['ID'];
        }

        $result = [];

        if (!empty($elementsArr)) {
            foreach ($elementsArr as $elementsArrItem) {
                $order = ['ID' => 'ASC'];
                $filter = [
                    'IBLOCK_PROPERTY_ID' => $codeOldPropertyArr['ID'],
                    'IBLOCK_ELEMENT_ID' => $elementsArrItem,
                ];

                $valueOldPropertyArr = ElementPropertyTable::getList([
                    'order' => $order,
                    'filter' => $filter,
                    'cache' => [
                        'ttl' => 60 * 60 * 3,
                        'cache_joins' => true,
                    ],
                ])->fetch();

                if (!empty($valueOldPropertyArr['VALUE'])) {
                    $propertyValue = '';

                    if ($codeOldPropertyArr['PROPERTY_TYPE'] === 'L') {
                        $filter = [
                            'ID' => $valueOldPropertyArr['VALUE'],
                        ];

                        $propertyEnum = PropertyEnumerationTable::getList([
                            'filter' => $filter,
                            'cache' => [
                                'ttl' => 60 * 60 * 3,
                                'cache_joins' => true,
                            ],
                        ])->fetch();

                        $propertyValue = $propertyEnum['VALUE'];
                    } elseif ($codeOldPropertyArr['PROPERTY_TYPE'] === 'S') {
                        $propertyValue = $valueOldPropertyArr['VALUE'];
                    }

                    foreach ($elementsPropertyTypeEArr as $key => $elementsPropertyTypeEArrItem) {
                        if (trim(strtolower($propertyValue)) === trim(strtolower($elementsPropertyTypeEArrItem))) {
                            $propertiesSetFields = [$codeNewProperty => $key];
                            CIBlockElement::SetPropertyValuesEx($elementsArrItem, $iblockId, $propertiesSetFields);
                            $result['success'][] = 'Свойство ' . $codeNewProperty . ' Товара ' . $elementsArrItem . ' обновлено';
                            break;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Комбо-обновление: переносит бренды/значения и затем заполняет E-свойство по совпадению названий.
     */
    public static function updateProductPropertyBrand(
        string $name,
        string $codeNewProperty,
        string $codeOldProperty,
        int    $iblockId
    ): array
    {
        $resultUPPBN = self::updateProductPropertyByName($name, $codeOldProperty, $iblockId);
        $resultUPEP = self::updatePropertyEProduct($codeNewProperty, $codeOldProperty, $iblockId);

        return array_merge($resultUPPBN, $resultUPEP);
    }
}