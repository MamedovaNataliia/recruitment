<?

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if (!CModule::IncludeModule("ynsirecruitment")) {
    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}
$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if(!function_exists('__CrmContactListEndResonse'))
{
    function __CrmContactListEndResonse($result)
    {
        $GLOBALS['APPLICATION']->RestartBuffer();
        Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
        if(!empty($result))
        {
            echo CUtil::PhpToJSObject($result);
        }
        require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
        die();
    }
}
if ($action === 'GET_ROW_COUNT') {
    \Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
    $GLOBALS['APPLICATION']->RestartBuffer();
    $params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
    $gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
//    if (!($gridID !== ''
//        && isset($_SESSION['YNSIR_PAGINATION_DATA'])
//        && isset($_SESSION['YNSIR_PAGINATION_DATA'][$gridID])
//        && is_array($_SESSION['YNSIR_PAGINATION_DATA'][$gridID]))
//    ) {
//        __CrmContactListEndResonse(array('DATA' => array('TEXT' => '')));
//    }

    $gridData = $_SESSION['YNSIR_PAGINATION_DATA'][$gridID];
    $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
    $result = YNSIRTypelist::GetList(array(), $filter, array(),false,  array());//CCrmContact::GetListEx(array(), $filter, array(), false, array(), array());
    $text = '';
    if (is_numeric($result)) {
        $text = GetMessage('YNSIR_TYPE_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
        if ($text === '') {
            $text = $result;
        }
    }
    __CrmContactListEndResonse(array('DATA' => array('TEXT' => $text)));
}
?>