<?php

namespace TestVendor\Main\Handlers;

use CIBlockSection;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use CFile;

class AddCatalogIconToSectionListHandler
{
    public static function handler(&$list): void
    {
        $list->aHeaders['UF_CATALOG_ICON'] = [
            'id' => 'UF_CATALOG_ICON',
            'content' => 'Наличие иконки',
            'default' => true,
        ];
        
        
        if (
            empty($list->aRows)
        ) {
            return;
        }
        
        foreach ($list->aRows as $row) {
            if (!str_starts_with($row->id, 'S')) {
                continue;
            }
    
            $arSection = CIBlockSection::GetList(
                [],
                [
                    'ID' => (int)substr($row->id, 1),
                    'IBLOCK_ID' => IblockHelper::getIdByCode(IblockCodes::CATALOG),
                ],
                false,
                [
                    'ID',
                    'IBLOCK_ID',
                    'UF_CATALOG_ICON',
                ],
            )->GetNext();
            
            if (!$arSection) {
                continue;
            }
            
            $fileId = $arSection['UF_CATALOG_ICON'];
            
            if ($fileId == 0) {
                $row->AddViewField(
                    'UF_CATALOG_ICON',
                    '<span style="color:#ffc344; font-size:11px;">— отсутствует</span>',
                );
                continue;
            }
            
            $src = CFile::GetPath($fileId);
            
            $html = '<div style="display:flex; align-items:center; gap:10px;">';
            $html .= '<span style="color:#27ae60; font-size:18px; line-height:1;" title="Иконка загружена">●</span>';
            $html .= '<span style="color:#27ae60; font-weight:bold; font-size:11px;">ЕСТЬ</span>';
            
            if ($src) {
                $html .= '<img src="' . $src . '" width="48" height="48" style="object-fit:contain; margin-left:5px; padding:2px; border:1px solid #eee; background:#fff; border-radius:3px;"> ';
            }
            
            $html .= '</div>';
            
            $row->AddViewField(
                'UF_CATALOG_ICON',
                $html,
            );
        }
    }
}