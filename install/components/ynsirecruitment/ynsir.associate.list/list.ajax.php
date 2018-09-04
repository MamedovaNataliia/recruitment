<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__YNSIRAssociateViewEndResponse'))
{
    function __YNSIRAssociateViewEndResponse($result)
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

if (!CModule::IncludeModule('crm'))
{
    __YNSIRAssociateViewEndResponse(array('ERROR' => 'Could not include crm module.'));
}
if (!CModule::IncludeModule('ynsirecruitment'))
{
    ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
    return;
}
//$userPerms = CCrmPerms::GetCurrentUserPermissions();
//if(!CCrmPerms::IsAuthorized())
//{
//	__YNSIRAssociateViewEndResponse(array('ERROR' => 'Access denied.'));
//}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if ($action === 'GET_ROW_COUNT')
{
    \Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

//	if(!CCrmPerms::IsAccessEnabled($userPerms))
//	{
//		__YNSIRAssociateViewEndResponse(array('ERROR' => 'Access denied.'));
//	}

    $params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
    $gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
    if(!($gridID !== ''
        && isset($_SESSION['YNSIR_GRID_DATA'])
        && isset($_SESSION['YNSIR_GRID_DATA'][$gridID])
        && is_array($_SESSION['YNSIR_GRID_DATA'][$gridID])))
    {
        __YNSIRAssociateViewEndResponse(array('DATA' => array('TEXT' => '')));
    }

    $gridData = $_SESSION['YNSIR_GRID_DATA'][$gridID];
    $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
    $result = YNSIRAssociateJob::GetList(array(), $filter, array(), false, array(), array());

    $text = '';
    if(is_numeric($result))
    {
        $text = GetMessage('YNSIR_ASSOCIATE_VIEW_ROW_COUNT', array('#ROW_COUNT#' => $result));
        if($text === '')
        {
            $text = $result;
        }
    }
    __YNSIRAssociateViewEndResponse(array('DATA' => array('TEXT' => $text)));
}
else if ($action === 'GET_ROUND_STATUS')
{
    $params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
    $idOrder = $params['ORDER_JOB_ID'];
    $arInterview = YNSIRInterview::getListDetail(array(), array('JOB_ORDER' => $idOrder), false, false, array());
    if(!empty( $arInterview[$params['ORDER_JOB_ID']]))
        __YNSIRAssociateViewEndResponse(array('DATA' => array('ROUND' => $arInterview[$params['ORDER_JOB_ID']])));
    else
        __YNSIRAssociateViewEndResponse(array('ERROR' => 'Error.'));

}
?>