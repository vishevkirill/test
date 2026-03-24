<?php

namespace TestVendor\Agents;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use CIBlock;
use CIBlockElement;
use TestVendor\Config;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use Throwable;

class FillPropertyHitAgent
{
    private int $iblockId;
    private array $selectFields = [
        'ID',
        'IBLOCK_ID',
        'XML_ID',
    ];

    public function __construct()
    {
        if (!Loader::includeModule('iblock')) {
            throw new SystemException('Модуль iblock не установлен');
        }

        $this->iblockId = IblockHelper::getIdByCode(IblockCodes::CATALOG);

        if (!$this->iblockId) {
            throw new SystemException('Не удалось получить ID инфоблока каталога');
        }
    }

    /**
     * Точка входа для Агента
     * @return string
     */
    public static function run(): string
    {
        try {
            $agent = new self();
            $agent->execute();
        } catch (Throwable $e) {
            AddMessage2Log('Ошибка в агенте TestVendor\Agents\FillPropertyHitAgent: ' . $e->getMessage());
        }

        return self::class . '::run();';
    }


    public function execute(): void
    {
        // 1. Очищаем старые метки
        $this->clearHitProperty();

        // 2. Получаем данные из 1С
        $dataFrom1C = $this->fetchDataFrom1C();

        if (!empty($dataFrom1C)) {
            // 3. Заполняем новые метки
            $this->fillHitProperty($dataFrom1C);
        }

        // 4. Сбрасываем тегированный кеш инфоблока
        CIBlock::clearIblockTagCache($this->iblockId);
    }

    private function clearHitProperty(): void
    {
        $iterator = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $this->iblockId,
                '!PROPERTY_HIT' => false,
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
            ]
        );

        $el = new CIBlockElement();

        while ($row = $iterator->Fetch()) {
            CIBlockElement::SetPropertyValuesEx($row['ID'], $this->iblockId, ['HIT' => []]);
            $el->Update($row['ID'], []);
        }
    }

    private function fetchDataFrom1C(): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => Config::EXCHANGE_ONEC_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => Config::EXCHANGE_ONEC_LOGIN . ':' . Config::EXCHANGE_ONEC_PASSWORD,
            CURLOPT_TIMEOUT => 30,
        ]);

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$output || $httpCode !== 200) {
            return [];
        }

        $decoded = json_decode($output, true);
        if (
            json_last_error() !== JSON_ERROR_NONE ||
            empty($decoded['data'])
        ) {
            return [];
        }

        return $decoded['data'];
    }

    private function fillHitProperty(array $listElemProp): void
    {
        $itemsByXmlId = [];
        foreach ($listElemProp as $item) {
            if (!empty($item['Nom'])) {
                $itemsByXmlId[$item['Nom']] = $item;
            }
        }

        if (empty($itemsByXmlId)) {
            return;
        }

        $xmlIdChunks = array_chunk(array_keys($itemsByXmlId), 500);
        $el = new CIBlockElement();

        foreach ($xmlIdChunks as $chunkXmlIds) {
            $iterator = CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $this->iblockId,
                    'XML_ID' => $chunkXmlIds,
                    'ACTIVE' => 'Y',
                ],
                false,
                false,
                $this->selectFields,
            );

            while ($row = $iterator->Fetch()) {
                $xmlId = $row['XML_ID'];

                if (!isset($itemsByXmlId[$xmlId])) {
                    continue;
                }

                $itemData = $itemsByXmlId[$xmlId];
                $newPropertyValues = [];

                foreach (Config::HIT_VALUES as $key => $propertyEnumId) {
                    if (isset($itemData[$key]) && (int)$itemData[$key] === 1) {
                        $newPropertyValues[] = $propertyEnumId;
                    }
                }

                if (!empty($newPropertyValues)) {
                    CIBlockElement::SetPropertyValuesEx(
                        $row['ID'],
                        $this->iblockId,
                        ['HIT' => $newPropertyValues]
                    );

                    $el->Update($row['ID'], []);
                }
            }
        }
    }
}