<?php

namespace App\Classes\Services;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class HelpClass
{
    /**
     * @param $tableName
     * @return mixed
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function getTableClass($tableName)
    {
        $arHLBThemes = HighloadBlockTable::getList(["filter" => ['TABLE_NAME' => $tableName]])->fetch();
        $obEntityThemes = HighloadBlockTable::compileEntity($arHLBThemes);
        return $obEntityThemes->getDataClass();
    }

    /**
     * Установка количества изображений товара
     *
     * @param $productId
     * @param $quantity
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function setCountImageGoods($productId, $quantity)
    {
        $strEntityData = self::getTableClass('image_goods');
        $rsData = $strEntityData::getList(array(
            'filter' => [
                'UF_PRODUCT_ID' => $productId
            ]
        ));

        if (!$row = $rsData->fetch()) {
            $strEntityData::add([
                'UF_PRODUCT_ID' => $productId,
                'UF_QUANTITY' => $quantity > 1 ? 1 : 0,
            ]);
        } else {
            $strEntityData::update($row['ID'], [
                'UF_QUANTITY' => $quantity > 1 ? 1 : 0,
            ]);
        }
    }

    /**
     * Удаление количества изображений товара
     *
     * @param $productId
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function deleteCountImageGoods($productId)
    {
        $strEntityData = self::getTableClass('image_goods');
        $rsData = $strEntityData::getList(array(
            'filter' => [
                'UF_PRODUCT_ID' => $productId
            ]
        ));
        if ($arData = $rsData->fetch()) {
            $strEntityData::delete($arData['ID']);
        }
    }

    /**
     * Запись количества оплаченных товаров
     *
     * @param $quantity
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function setPaidGoodsYear($quantity)
    {
        $strEntityData = self::getTableClass('paid_goods_year');
        $rsData = $strEntityData::getList(array(
            "select" => array("*"),
        ));
        if (!$row = $rsData->fetch()) {
            $strEntityData::add([
                'UF_QUANTITY' => $quantity,
            ]);
        } else {
            $strEntityData::update($row['ID'], [
                'UF_QUANTITY' => $quantity,
            ]);
        }
    }

    /**
     * Установка времени просмотра товара пользователем
     *
     * @param $guestId
     * @param $productId
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function setGoodsViews($guestId, $productId)
    {
        $strEntityData = self::getTableClass('product_views');

        $rsData = $strEntityData::getList(array(
            'filter' => [
                'UF_GUEST_ID' => $guestId,
                'UF_PRODUCT_ID' => $productId
            ]
        ));

        if (!$row = $rsData->fetch()) {
            self::addDataViews(null, $strEntityData, 1, $productId, $guestId);
        } else {
            self::addDataViews($row['ID'], $strEntityData, $row['UF_VIEWING_TIME'] + 1, $productId, $guestId);
        }
    }

    /**
     * Добавление или обновление времени просмотра товара пользователем
     *
     * @param $id
     * @param $strEntityData
     * @param $time
     * @param $productId
     * @param $guestId
     * @throws ObjectException
     */
    protected function addDataViews($id, $strEntityData, $time, $productId, $guestId)
    {
        if(empty($id)) {
            $strEntityData::add([
                'UF_VIEWING_TIME' => $time,
                'UF_PRODUCT_ID' => $productId,
                'UF_GUEST_ID' => $guestId,
                'UF_DATE' => new \Bitrix\Main\Type\DateTime()
            ]);
        } else {
            $strEntityData::update($id, [
                'UF_VIEWING_TIME' => $time,
                'UF_PRODUCT_ID' => $productId,
                'UF_GUEST_ID' => $guestId
            ]);
        }
    }

    /**
     * Подсчет статистики просмотров на конец дня
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function statisticViewsDay()
    {
        $objDateTime = new \Bitrix\Main\Type\DateTime();
        $strEntityData = self::getTableClass('product_views');

        $rsData = $strEntityData::getList(array(
            'select' => ['*'],
            'order' => [
                'UF_PRODUCT_ID' => 'ASC',
            ],
            'filter' => [
                '<UF_DATE' => $objDateTime::createFromTimestamp(strtotime( "now 00:00:00" ))
            ],
        ));
        $statistic = [];

        while($arData = $rsData->Fetch()) {
            $productId = $arData['UF_PRODUCT_ID'];

            if( !isset($statistic[$productId]) ) {
                $statistic[$productId] = [
                    'positively' => 0,
                    'negative' => 0
                ];
            }

            if($arData['UF_VIEWING_TIME'] == 5) {
                $statistic[$productId]['positively'] += 1;
            } else {
                $statistic[$productId]['negative'] += 1;
            }
            $strEntityData::delete($arData['ID']);
        }
        if( !empty($statistic) )
            self::setStatisticDay($statistic);
    }

    /**
     * Запись статистики просмотров
     *
     * @param $statistics
     * @throws ArgumentException
     * @throws ObjectException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function setStatisticDay($statistics)
    {
        $strEntityData = self::getTableClass('statistic_day');

        foreach ($statistics as $productId => $statistic) {
            if( !empty($productId) )
                $strEntityData::add([
                    'UF_PRODUCT_ID' => $productId,
                    'UF_POSITIVELY' => $statistic['positively'],
                    'UF_NEGATIVE' => $statistic['negative'],
                    'UF_DATE' => new \Bitrix\Main\Type\DateTime()
                ]);
        }
    }

    /**
     * Подсчет популярности товаров на конец дня
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function statisticDay()
    {
        global $DB;

        $paidEntityData = self::getTableClass('paid_goods_year');
        $imageEntityData = self::getTableClass('image_goods');


        // количество оплаченных товаров за последний год
        $paidData = $paidEntityData::getList([
            'select' => ['UF_QUANTITY']
        ])->fetch();
        $paid = isset($paidData['UF_QUANTITY']) ? $paidData['UF_QUANTITY'] : 0;

        $arFilter = ["IBLOCK_ID" => 2, "ACTIVE" => "Y"];
        $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array(), ["ID"]);

        while($ob = $res->GetNext()) {
            $productId = $ob['ID'];
            $positively = $negative = 0;
            // изображения товаров
            $imageData = $imageEntityData::getList([
                'select' => ['UF_QUANTITY'],
                'filter' => [
                    'UF_PRODUCT_ID' => $productId,
                ]
            ])->fetch();
            $image = isset($imageData['UF_QUANTITY']) ? $imageData['UF_QUANTITY'] : 0;

            // статистика просмотров за последние полгода
            $sql = 'SELECT UF_PRODUCT_ID PRODUCT_ID, SUM(UF_POSITIVELY) POSITIVELY, SUM(UF_NEGATIVE) NEGATIVE FROM statistic_day';
            $sql .= ' WHERE UF_PRODUCT_ID="' . $productId . '" AND UF_DATE>="' . date( "Y-m-d", strtotime( "-6 month" )) . '"';
            $sql .= ' GROUP BY UF_PRODUCT_ID';
            $resStatistic = $DB->Query($sql, false);
            if( $arStatistic = $resStatistic->Fetch() ) {
                $positively = $arStatistic['POSITIVELY'];
                $negative = $arStatistic['NEGATIVE'];
            }

            $statistic['V'] = $positively;
            $statistic['B'] = $paid;
            $statistic['P'] = $image;
            $statistic['U'] = $negative;
            $statistic['popularity'] = $positively + 3 * $paid + 10 * $image - $negative;

            self::setStatistic($statistic, $productId);
        }

        self::clearOldData();
    }

    /**
     * Запись популярности товаров
     *
     * @param $statistics
     * @param $productId
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function setStatistic($statistics, $productId)
    {
        $strEntityData = self::getTableClass('popularity_goods');

        $rsData = $strEntityData::getList(array(
            'filter' => [
                'UF_PRODUCT_ID' => $productId
            ]
        ));
        if (!$row = $rsData->fetch()) {
            $strEntityData::add([
                'UF_PRODUCT_ID' => $productId,
                'UF_V' => $statistics['V'],
                'UF_B' => $statistics['B'],
                'UF_P' => $statistics['P'],
                'UF_U' => $statistics['U'],
                'UF_POPULARITY' => $statistics['popularity'],
            ]);
        } else {
            $strEntityData::update($row['ID'], [
                'UF_V' => $statistics['V'],
                'UF_B' => $statistics['B'],
                'UF_P' => $statistics['P'],
                'UF_U' => $statistics['U'],
                'UF_POPULARITY' => $statistics['popularity'],
            ]);
        }
        \CIBlockElement::SetPropertyValuesEx($productId, false, array('POPULARITY' => $statistics['popularity']));
    }

    /**
     * Очистка старых данных статистики просмотров
     *
     * @throws ArgumentException
     * @throws ObjectException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function clearOldData()
    {
        $objDateTime = new \Bitrix\Main\Type\DateTime();

        $statisticEntityData = self::getTableClass('statistic_day');
        $statisticData = $statisticEntityData::getList([
            'select' => ['ID'],
            'filter' => [
                '<UF_DATE' => $objDateTime::createFromTimestamp(strtotime( "-6 month 00:00:00" ))
            ]
        ]);
        while($arData = $statisticData->Fetch()) {
            $statisticEntityData::delete($arData['ID']);
        }
    }
}
