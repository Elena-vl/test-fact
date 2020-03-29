<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/Classes/Services/HelpClass.php');

CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');
CModule::IncludeModule('sale');
CModule::IncludeModule("statistic");
CModule::IncludeModule("highloadblock");

use Bitrix\Catalog\CatalogViewedProductTable;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale;
use App\Classes\Services\HelpClass;

/*
 * Замер времени просмотра товара в текущей сессии пользователя
 */
function getProductHistory($productId)
{
    CModule::IncludeModule('catalog');
    if( isset($_SESSION['basket_user_id']) ) {
        $basketUserId = $_SESSION['basket_user_id'];
        $dateVisit = $_SESSION['start_day'];
    } else {
        $basketUserId = (int)CSaleBasket::GetBasketUserID(false);
        $_SESSION['basket_user_id'] = $basketUserId;
        $dateVisit = new \Bitrix\Main\Type\DateTime(date('Y-m-d 00:00:00', strtotime('now')), 'Y-m-d H:i:s');
        $_SESSION['start_day'] = $dateVisit;
    }

    if ($basketUserId > 0) {
        $viewedIterator = CatalogViewedProductTable::GetList(
            array(
                'select' => array('*'),
                'filter' => array('FUSER_ID' => $basketUserId, 'SITE_ID' => SITE_ID, 'ELEMENT_ID' => $productId, '>=DATE_VISIT' => $dateVisit),
                'order' => array('DATE_VISIT' => 'DESC')
            )
        );
        while ($viewedProduct = $viewedIterator->fetch()) {
            if ($viewedProduct['VIEW_COUNT'] == 1) { //если это первый просмотр за день, то записываем нахождение на странице
                $helpClass = new HelpClass;
                try {
                    $helpClass->setGoodsViews($basketUserId, $productId);
                } catch (\Bitrix\Main\SystemException $e) {
                }
            }
        }
    }
}

CAgent::AddAgent(
    "setPayProducts();",  // имя функции
    "",                // идентификатор модуля
    "N",                      // агент не критичен к кол-ву запусков
    86400,                    // интервал запуска - 1 сутки
    (new \Bitrix\Main\Type\DateTime())::createFromTimestamp(strtotime( "tomorrow 00:01:00" )),  // дата первой проверки
    "Y",                      // агент активен
    "",                       // дата первого запуска
    30);
/**
 * Один раз в день считаем оплаченные товары за прошедший год
 * @throws ArgumentNullException
 */
function setPayProducts()
{
    $quantity = 0;

    $orders = CSaleOrder::GetList(
        Array(),
        Array ("!CANCELED" => "Y", "PAYED" => "Y", ">DATE_PAYED"=>date( "Y-m-d 00:00:00", strtotime( "-1 year" ))),
        false,
        false,
        Array ("ID"));
    while ($arOrder = $orders->Fetch()) {
        $order = Sale\Order::load($arOrder["ID"]);
        $basket = $order->getBasket();
        $quantity += array_sum($basket->getQuantityList());
    }
    $helpClass = new HelpClass;
    try {
        $helpClass->setPaidGoodsYear($quantity);
    } catch (\Bitrix\Main\SystemException $e) {
    }
    return "setPayProducts();";
}

CAgent::AddAgent(
    "setStatisticDay();",
    "",
    "N",
    86400,// интервал запуска - 1 сутки
    "",
    "Y",
    (new \Bitrix\Main\Type\DateTime())::createFromTimestamp(strtotime( "tomorrow 00:01:00" )),
    30);
/**
 * Подсчет статистики просмотров на конец дня
 */
function setStatisticDay()
{
    $helpClass = new HelpClass;
    try {
        $helpClass->statisticViewsDay();
    } catch (\Bitrix\Main\SystemException $e) {
    }
    return "setStatisticDay();";
}

CAgent::AddAgent(
    "setPopularityGoods();",
    "",
    "N",
    86400,// интервал запуска - 1 сутки
    "",
    "Y",
    (new \Bitrix\Main\Type\DateTime())::createFromTimestamp(strtotime( "tomorrow 01:00:00" )),
    30);
/**
 * Подсчет популярности на конец дня
 */
function setPopularityGoods()
{
    $helpClass = new HelpClass;
    try {
        $helpClass->statisticDay();
    } catch (\Bitrix\Main\SystemException $e) {
    }
    return "setPopularityGoods();";
}

AddEventHandler("iblock", "OnAfterIBlockElementAdd", "OnBeforeIBlockElementAddHandler");
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", "OnBeforeIBlockElementAddHandler");
function OnBeforeIBlockElementAddHandler(&$arFields)
{
    if ($arFields["ID"] > 0) {
        $countImage = 0;
        $property_enums = CIBlockProperty::GetList(
            Array(),
            Array("IBLOCK_ID" => $arFields['IBLOCK_ID'], "CODE" => "MORE_PHOTO"));
        if ($enum_fields = $property_enums->Fetch()) { //получаем "Картинки галереи"
            $image = $arFields['PROPERTY_VALUES'][$enum_fields['ID']];
            $countImage += count($image);
        }
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
}

AddEventHandler("iblock", "OnBeforeIBlockElementDelete", "OnBeforeIBlockElementDeleteHandler");
function OnBeforeIBlockElementDeleteHandler($ID)
{
    $helpClass = new HelpClass;
    try {
        $helpClass->deleteCountImageGoods($ID);
    } catch (\Bitrix\Main\SystemException $e) {
    }
}
