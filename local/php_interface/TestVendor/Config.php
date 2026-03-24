<?php

namespace TestVendor;

/**
 * Конфиг
 */
final class Config
{
    /**
     *  Каталог
     */
    const CATALOG_BRAND_PROPERTY_CODE = '';
    const CATALOG_HIT_PROPERTY_CODE = '';
    const CATALOG_SORT_WEIGHT_PROPERTY_CODE = '';
    const CATALOG_POPULAR_FIELD = '';
    const CATALOG_CREATED_AT_FIELD = '';
    const CATALOG_MADE_TO_ORDER_PROPERTY_CODE = '';

    /**
     * Сортировка
     */
    const SORT_CONTEXT_BRAND_XML_ID_KEY = '';
    const SORT_CONTEXT_POPULARITY_KEY = '';
    const SORT_CONTEXT_CREATED_AT_KEY = '';
    const SORT_CONTEXT_IS_NEW_KEY = '';

    const SORT_NEW_PERIOD_DAYS = 30;

    const SORT_WEIGHT_BASE = 1_000_000_000;
    const SORT_BATCH_SIZE = 500;

    const SECONDS_PER_DAY = 86400;

    /**
     * Эксклюзивные производители
     */
    const EXCLUSIVE_MANUFACTURER_XML_IDS = [
        '',
    ];

    /**
     * 1С Exchange endpoints
     */
    public const EXCHANGE_ONEC_URL = '';
    public const EXCHANGE_ONEC_URL_NEW = '';

    public const EXCHANGE_ONEC_LOGIN = '';
    public const EXCHANGE_ONEC_PASSWORD = '';

    public const ENDPOINT_STOCK_LIST = '';
    public const ENDPOINT_PROPERTY_GET = '';

    /**
     * Миграция/Обновление Бренда (Agent)
     */
    public const AGENT_BRAND_PROP_NAME = '';
    public const AGENT_BRAND_PROP_NEW_CODE = '';
    public const AGENT_BRAND_PROP_OLD_CODE = '';

    /**
     * Phone/SMS auth
     */
    public const PHONE_AUTH_HL_BLOCK_NAME = '';
    public const PHONE_AUTH_SMS_EVENT_NAME = '';
    public const PHONE_AUTH_SITE_ID = '';


    /**
     * Подключение CSS и JS
     */
    public const CUSTOM_CSS = [
        '/local/templates/.default/js/libs/swiper/swiper-bundle.min.css',
        '/local/templates/.default/css/custom.css',
    ];

    public const CUSTOM_JS = [
        '/local/templates/.default/js/libs/swiper/swiper-bundle.min.js',
        '/local/templates/.default/js/modal-plait.js',
        '/local/templates/.default/js/delivery-tooltip.js',
        '/local/templates/.default/js/custom.js',
    ];

    /**
     * Значения свойств HIT
     */
    public const HIT_VALUES = [
        'HIT' => 0,
    ];

    public const PRICE_BASE = '';
    public const PRICE_OLD = '';
}
