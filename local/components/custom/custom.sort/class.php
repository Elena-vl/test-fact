<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\Application;


class CustomSortComponent extends \CBitrixComponent
{
    protected $requiredModules = ['iblock'];

    /**
     * Возврат параметров для сортировки
     * @return array
     */
    public static function getSortOrderList() {

        Loc::loadMessages(__FILE__);

        $sortingParams = [];

        $sortingParams['ORDERS_LIST'] = ['asc'        => Loc::getMessage('CUSTOM_SORT_COMPONENT_SORT_ORDER_ASC_VALUE'),
                                         'desc'       => Loc::getMessage('CUSTOM_SORT_COMPONENT_SORT_ORDER_DESC_VALUE')];

        $sortingParams['ORDERS_DEFAULT_LIST'] = ['asc', 'desc'];

        $sortingParams['TYPES_LIST'] = [
            ['NAME' => Loc::getMessage('CUSTOM_SORT_COMPONENT_SORT_TYPES_NAME_VALUE'),'CODE' => 'name']
        ];

        $sortingParams['FIELDS_DEFAULT_LIST'] = ['name'];

        return $sortingParams;
    }

    /**
     * Возврат полей доступных для сортировки
     * @return array
     */
    public function getSortOrderListByCurrentFields() {

        $allFieldsList = self::getSortOrderList()['TYPES_LIST'];
        $fieldsList = array();

        foreach ($allFieldsList as $field) {
            if (in_array($field['CODE'], $this->arParams['FIELDS_CODE'])) {
                $fieldsList[$field['CODE']] = $field;
            }
        }
        return $fieldsList;
    }

    /**
     * Возврат свойств инфоблока
     *
     * @return array
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    public function getSortOrderListByCurrentProperties() {

        $propertyList = [];

        $propertiesCollection = \Bitrix\Iblock\PropertyTable::getList([
            'select' => ['NAME','CODE'],
            'filter' => [
                'IBLOCK_ID' => (int)$this->arParams['IBLOCK_ID'],
                'CODE' => $this->arParams['PROPERTY_CODE']
            ]
        ]);
        while ($property = $propertiesCollection->fetch()) {
            $property['CODE'] = 'property_' . $property['CODE'];
            $propertyList[$property['CODE']]   = $property;
        }

        return $propertyList;
    }

    /**
     * Проверяем наличие модуля
     * @return $this
     * @throws SystemException
     * @throws \Bitrix\Main\LoaderException
     */
    protected function checkModules() {
        foreach ($this->requiredModules as $moduleName) {
            if (!Loader::includeModule($moduleName)) {
                throw new SystemException(Loc::getMessage('CUSTOM_SORT_COMPONENT_NO_MODULE', ['#MODULE#', $moduleName]));
            }
        }
        return $this;
    }

    /**
     * Принимает параметры компонента в качестве аргумента и должен возвращать их в формате по мере необходимости..
     *
     * @param  array [string]mixed $arParams
     * @return array[string]mixed
     */
    public function onPrepareComponentParams($params) {

        global ${$params['SORT_NAME']};

        if (trim($params['SORT_NAME']) == '') {
            $params['SORT_NAME'] = 'SORT';
        }

        if (!(${$params['SORT_NAME']})) {
            ${$params['SORT_NAME']} = [];
        }

        global ${$params['ORDER_NAME']};

        if (trim($params['ORDER_NAME']) == '') {
            $params['ORDER_NAME'] = 'ORDER';
        }

        if (!(${$params['ORDER_NAME']})) {
            ${$params['ORDER_NAME']} = [];
        }

        if (!isset($params['CACHE_TIME'])) {
            $params['CACHE_TIME'] = 36000000;
        }

        return $params;
    }

    /**
     * Подключение языковых файлов
     *
     * @return void
     */
    public function onIncludeComponentLang() {
        $this->includeComponentLang(basename(__FILE__));
        Loc::loadMessages(__FILE__);
    }

    /**
     * Проверка, что сортировка активна
     * @param      $value
     * @param bool $isOrder
     *
     * @return bool
     * @throws SystemException
     */
    protected function isSortActive($value, $isOrder = false) {

        $request = Application::getInstance()->getContext()->getRequest();

        $isOrder = (bool)$isOrder;
        $value   = trim($value);

        $isActive = false;

        if ($isOrder) {

            if ($request->getQuery('order') == $value) {
                $isActive = true;
            }

            $order = $request->getQuery('order');

            if (empty($order) && ($_SESSION['order'] == $value)
                && ($this->arParams['INCLUDE_SORT_TO_SESSION'] == 'Y')
            ) {
                $isActive = true;
            }
        } else {
            if ($request->getQuery('sort') == $value) {
                $isActive = true;
            }

            $sort = $request->getQuery('sort');

            if (empty($sort) && ($_SESSION['sort'] == $value)
                && ($this->arParams['INCLUDE_SORT_TO_SESSION'] == 'Y')
            ) {
                $isActive = true;
            }
        }

        return $isActive;
    }

    /**
     * Меняем направление сортировки
     * @param string $sortOrder
     *
     * @return string
     */
    protected function getInvertSortOrder($sortOrder) {

        $sortOrder = trim($sortOrder);
        $invertSortOrder = '';

        if (empty($sortOrder) || $sortOrder == 'asc') {
            $invertSortOrder = 'desc';
        }
        elseif ($sortOrder == 'desc') {
            $invertSortOrder = 'asc';
        }

        return $invertSortOrder;
    }

    /**
     * Текущая сортировка
     * @param bool $isOrder
     *
     * @return string
     * @throws SystemException
     */
    protected function getCurrentSort( $isOrder = false) {

        $request = Application::getInstance()->getContext()->getRequest();

        $isOrder = (bool)$isOrder;
        $value = '';

        if ($isOrder) {

            $order = $request->getQuery('order');

            if (!empty($order)) {
                $value = $request->getQuery('order');
            }

            if ($this->arParams['INCLUDE_SORT_TO_SESSION'] == 'Y') {
                if ((empty($order)) && (isset($_SESSION['order']) && (!empty($_SESSION['order'])))) {
                    $value = $_SESSION['order'];
                }
            }
        } else {

            $sort = $request->getQuery('sort');

            if (!empty($sort)) {
                $value = $request->getQuery('sort');
            }

            if ($this->arParams['INCLUDE_SORT_TO_SESSION'] == 'Y') {
                if ((empty($sort))
                    && (isset($_SESSION['sort']) && (!empty($_SESSION['sort'])))) {
                    $value = $_SESSION['sort'];
                }
            }
        }

        return $value;
    }

    /**
     * @return void
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    protected function prepareResult() {

        global $USER;

        $request = Application::getInstance()->getContext()->getRequest();

        $cacheId = $request->getQuery('sort') . $request->getQuery('order');
        $cacheId .= serialize($this->arParams);

        if ($this->arParams['INCLUDE_SORT_TO_SESSION'] == 'Y') {
            $cacheId .= $_SESSION['sort'] . $_SESSION['order'];
        }

        $cacheId .= $USER->GetGroups();

        $cache = new CPHPCache();

        if ($cache->InitCache($this->arParams['CACHE_TIME'], $cacheId, '/')) {
            $result = $cache->GetVars();
        } elseif ($cache->StartDataCache()) {

            $result['SORT']['PROPERTIES'] = array();

            if ($this->arParams['FIELDS_CODE']) {
                $result['SORT']['PROPERTIES'] = array_merge(
                    $result['SORT']['PROPERTIES'], $this->getSortOrderListByCurrentFields()
                );
            }

            if ($this->arParams['PROPERTY_CODE']) {
                $result['SORT']['PROPERTIES'] = array_merge(
                    $result['SORT']['PROPERTIES'], $this->getSortOrderListByCurrentProperties()
                );
            }

            $cache->EndDataCache($result);
        }

        global $APPLICATION;

        foreach ($result['SORT']['PROPERTIES'] as &$prop) { // проходимся по св-м и полям доступных для сортировки

            $prop['ACTIVE'] = $this->isSortActive($prop['CODE']);

            if ($prop['ACTIVE']) {
                $invertCurrentSortOrder = $this->getInvertSortOrder( $this->getCurrentSort($isOrder = true));
                $prop['ORDER'] = $invertCurrentSortOrder;
                $prop['URL'] = $APPLICATION->GetCurPageParam(
                    'sort=' . $prop['CODE'] . '&order=' . $invertCurrentSortOrder,
                    ['sort', 'order']
                );

            }
            else {
                $prop['ORDER'] = $this->getCurrentSort(true);
                $prop['URL'] = $APPLICATION->GetCurPageParam('sort=' . $prop['CODE'], ['sort']);
            }
        }
        if (!empty($this->arParams['SORT_ORDER'])) {

            foreach ($this->arParams['SORT_ORDER'] as $sortOrder) {

                $result['SORT']['ORDERS'][] = [
                    'ACTIVE' => $this->isSortActive($sortOrder, $isOrder = true),
                    'CODE'   => $sortOrder,
                    'URL'    => $APPLICATION->GetCurPageParam('order='. $sortOrder, ['order'])
                ];
            }

        }
        $this->arResult = $result;
    }

    /**
     * @return void
     * @throws SystemException
     */
    protected function outputtingSortingParameters() {

        global ${$this->arParams['SORT_NAME']};
        global ${$this->arParams['ORDER_NAME']};

        $request = Application::getInstance()->getContext()->getRequest();

        if ($this->arParams['INCLUDE_SORT_TO_SESSION'] == 'Y') {//если установлено, что сохраняем сортировку в сессии

            $sort = $request->getQuery('sort');

            if (empty($sort)) {
                ${$this->arParams['SORT_NAME']} = $_SESSION['sort'];
            } else {
                $_SESSION['sort']               = $request->getQuery('sort');
                ${$this->arParams['SORT_NAME']} = $request->getQuery('sort');
            }

            $order = $request->getQuery('order');

            if (empty($order)) {
                ${$this->arParams['ORDER_NAME']} = $_SESSION['order'];
            } else {
                $_SESSION['order']               = $request->getQuery('order');
                ${$this->arParams['ORDER_NAME']} = $request->getQuery('order');
            }
        } else {
            ${$this->arParams['SORT_NAME']}  = $request->getQuery('sort');
            ${$this->arParams['ORDER_NAME']} = $request->getQuery('order');
        }
    }

    public function executeComponent() {
        try {
            $this->checkModules()->prepareResult();
            $this->outputtingSortingParameters();
            $this->includeComponentTemplate();
        } catch (SystemException $e) {
            self::__showError($e->getMessage());
        } catch (\Bitrix\Main\LoaderException $e) {
            self::__showError($e->getMessage());
        }
    }
}
