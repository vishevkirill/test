<?php

AddEventHandler("iblock", "OnBeforeIBlockElementAdd", "DisableNoPhotoItems");
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", "DisableNoPhotoItems");

function DisableNoPhotoItems(&$arFields) {
    if ($arFields["IBLOCK_ID"] == 29) {
        
        $hasPreview = !empty($arFields["PREVIEW_PICTURE"]["name"]) || !empty($arFields["PREVIEW_PICTURE"]["tmp_name"]);
        $hasDetail = !empty($arFields["DETAIL_PICTURE"]["name"]) || !empty($arFields["DETAIL_PICTURE"]["tmp_name"]);

        if (!$hasPreview && !$hasDetail && $arFields["ID"] > 0) {
            $db_res = CIBlockElement::GetList([], ["ID" => $arFields["ID"]], false, false, ["PREVIEW_PICTURE", "DETAIL_PICTURE"]);
            if ($res = $db_res->Fetch()) {
                if ($res["PREVIEW_PICTURE"]) $hasPreview = true;
                if ($res["DETAIL_PICTURE"]) $hasDetail = true;
            }
        }

        if (!$hasPreview && !$hasDetail) {
            $arFields["ACTIVE"] = "N";
        }
    }
}
?>