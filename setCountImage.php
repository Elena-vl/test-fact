<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use App\Classes\Services\HelpClass;

global $USER;

if( !$USER->IsAdmin())
    die;

$arFilter = ["IBLOCK_ID" => 2, "ACTIVE" => "Y"];
$arSelect = ["ID", "IBLOCK_ID", "NAME", "DETAIL_PICTURE", "PREVIEW_PICTURE", "PROPERTY_MORE_PHOTO"];
$res = \CIBlockElement::GetList(Array(), $arFilter, false, [], $arSelect);
while($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    $arProps = $ob->GetProperties();

    $countImage = isset($arProps["MORE_PHOTO"]["VALUE"]) ? count($arProps["MORE_PHOTO"]["VALUE"]) : 0;

    if( isset($arFields['DETAIL_PICTURE']) && !empty($arFields['DETAIL_PICTURE']) )
        $countImage += 1;
    if( isset($arFields['PREVIEW_PICTURE']) && !empty($arFields['PREVIEW_PICTURE']) )
        $countImage += 1;
    $helpClass = new HelpClass;
    try {
        $helpClass->setCountImageGoods($arFields["ID"], $countImage);
    } catch (\Bitrix\Main\SystemException $e) {
    }
}
