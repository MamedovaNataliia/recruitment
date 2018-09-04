<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);

//AGENTS ARE REQUIRED FOR REBUILD SEARCH INDEX
define('NO_AGENT_CHECK', (!isset($_REQUEST['ACTION']) || $_REQUEST['ACTION'] !== 'REBUILD_SEARCH_CONTENT'));

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__YNSIRActivityListEndResonse'))
{
	function __YNSIRActivityListEndResonse($result)
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

if (!CModule::IncludeModule('ynsirecruitment'))
{
	__YNSIRActivityListEndResonse(array('ERROR' => 'Could not include crm module.'));
}
if (!CModule::IncludeModule('ynsirecruitment'))
{
    __YNSIRActivityListEndResonse(array('ERROR' => 'Could not include ynsirecruitment module.'));
}
//$userPerms = CCrmPerms::GetCurrentUserPermissions();
//if(!CCrmPerms::IsAuthorized())
//{
//	__YNSIRActivityListEndResonse(array('ERROR' => 'Access denied.'));
//}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if ($action === 'GET_ROW_COUNT')
{
    \Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

    if(!YNSIRJobOrder::CheckReadPermission(0, $userPerms))
    {
        __YNSIRContactListEndResonse(array('ERROR' => 'Access denied.'));
    }

    $params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
    $gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
    $gridData = $_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'][$gridID];
    $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
    $obRes = YNSIRFeedback::GetList(
        array(),
        $filter,
        array('GROUP_BY' => 'ID'),
        false,
        false
    );

    $result = $obRes -> SelectedRowsCount();

    $text = GetMessage('YNSIR_CONTACT_LIST_ROW_COUNT', array('#ROW_COUNT#' => intval($result)));
    __YNSIRActivityListEndResonse(array('DATA' => array('TEXT' => $text)));
}
?>