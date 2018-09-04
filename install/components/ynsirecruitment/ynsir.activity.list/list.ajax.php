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

//	if(!CCrmPerms::IsAccessEnabled($userPerms))
//	{
//		__YNSIRActivityListEndResonse(array('ERROR' => 'Access denied.'));
//	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['YNSIR_GRID_DATA'])
		&& isset($_SESSION['YNSIR_GRID_DATA'][$gridID])
		&& is_array($_SESSION['YNSIR_GRID_DATA'][$gridID])))
	{
		__YNSIRActivityListEndResonse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['YNSIR_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	$result = YNSIRActivity::GetList(array(), $filter, array(), false, array(), array());

	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('YNSIR_ACTIVITY_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__YNSIRActivityListEndResonse(array('DATA' => array('TEXT' => $text)));
}
elseif ($action === 'REBUILD_SEARCH_CONTENT')
{
	$agent = \Bitrix\Crm\Agent\Search\ActivitySearchContentRebuildAgent::getInstance();
	if(!$agent->isEnabled())
	{
		__YNSIRActivityListEndResonse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__YNSIRActivityListEndResonse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'REBUILD_ACT_STATISTICS')
{
	//~YNSIR_REBUILD_ACTIVITY_STATISTICS
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

//	if(!CCrmPerms::IsAdmin())
//	{
//		__YNSIRActivityListEndResonse(array('ERROR' => 'Access denied.'));
//	}

	if(COption::GetOptionString('ynsirecruitment', '~YNSIR_REBUILD_ACTIVITY_STATISTICS', 'N') !== 'Y')
	{
		__YNSIRActivityListEndResonse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('YNSIR_ACTIVITY_LIST_REBUILD_ACT_STATISTICS_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('ynsirecruitment', '~YNSIR_REBUILD_ACTIVITY_STATISTICS_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = YNSIRActivity::GetList(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = YNSIRActivity::GetList(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 20),
		array('ID')
	);

	$itemIDs = array();
	$itemQty = 0;
	if(is_object($dbResult))
	{
		while($fields = $dbResult->Fetch())
		{
			$itemIDs[] = (int)$fields['ID'];
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		\Bitrix\Crm\Statistics\ActivityStatisticEntry::rebuild($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('ynsirecruitment', '~YNSIR_REBUILD_ACTIVITY_STATISTICS_PROGRESS', serialize($progressData));
		__YNSIRActivityListEndResonse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'YNSIR_ACTIVITY_LIST_REBUILD_ACT_STATISTICS_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		COption::RemoveOption('ynsirecruitment', '~YNSIR_REBUILD_ACTIVITY_STATISTICS');
		COption::RemoveOption('ynsirecruitment', '~YNSIR_REBUILD_ACTIVITY_STATISTICS_PROGRESS');
		__YNSIRActivityListEndResonse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'YNSIR_ACTIVITY_LIST_REBUILD_ACT_STATISTICS_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
?>