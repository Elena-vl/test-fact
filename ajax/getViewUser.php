<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(isset($_REQUEST['element_id']) && !empty($_REQUEST['element_id'])) {
    getProductHistory($_REQUEST['element_id']);
}
