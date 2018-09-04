<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('ynsirecruitment'))
{
    ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
    return;
}
if (false)
{
	ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
	return;
}

//$arParams['PATH_TO_EVENT_LIST'] = CrmCheckPath('PATH_TO_EVENT_LIST', $arParams['PATH_TO_EVENT_LIST'], $APPLICATION->GetCurPage());
//$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

$arResult['EVENT_ENTITY_LINK'] = isset($arParams['EVENT_ENTITY_LINK']) && $arParams['EVENT_ENTITY_LINK'] == 'Y'? 'Y': 'N';
$arResult['EVENT_HINT_MESSAGE'] = isset($arParams['EVENT_HINT_MESSAGE']) && $arParams['EVENT_HINT_MESSAGE'] == 'N'? 'N': 'Y';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult['INTERNAL'] = isset($arParams['INTERNAL']) && $arParams['INTERNAL'] === 'Y';
$arResult['SHOW_INTERNAL_FILTER'] = isset($arParams['SHOW_INTERNAL_FILTER']) && $arParams['SHOW_INTERNAL_FILTER'] === 'Y';
$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL']? 'N': 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
$arResult['PATH_TO_EVENT_DELETE'] =  CHTTP::urlAddParams($arParams['PATH_TO_EVENT_LIST'], array('sessid' => bitrix_sessid()));

$arResult['SESSION_ID'] = bitrix_sessid();


if(isset($arParams['ENABLE_CONTROL_PANEL']))
{
	$arResult['ENABLE_CONTROL_PANEL'] = (bool)$arParams['ENABLE_CONTROL_PANEL'];
}
else
{
	$arResult['ENABLE_CONTROL_PANEL'] = !(isset($arParams['INTERNAL']) && $arParams['INTERNAL'] === 'Y');
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

//$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getHistoryViewRestriction();
//if(!$restriction->hasPermission())
//{
//	$arResult['ERROR'] = $restriction->getHtml();
//	if(!is_string($arResult['ERROR']) || $arResult['ERROR'] === '')
//	{
//		$arResult['ERROR'] = GetMessage('YNSIR_PERMISSION_DENIED');
//	}
//	$this->IncludeComponentTemplate();
//	return;
//}

$arFilter = array();
$arSort = array();

$bInternal = false;
if ($arParams['INTERNAL'] == 'Y' || $arParams['GADGET'] == 'Y')
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
$arResult['INTERNAL_EDIT'] = false;
if ($arParams['INTERNAL_EDIT'] == 'Y')
	$arResult['INTERNAL_EDIT'] = true;
$arResult['GADGET'] =  isset($arParams['GADGET']) && $arParams['GADGET'] == 'Y'? 'Y': 'N';
$isInGadgetMode = $arResult['GADGET'] === 'Y';

$entityType = isset($arParams['ENTITY_TYPE']) ? $arParams['ENTITY_TYPE'] : '';
$entityTypeID = YNSIROwnerType::ResolveID($entityType);

if ($entityType !== '')
{
	$arFilter['ENTITY_TYPE'] = $arResult['ENTITY_TYPE'] = $entityType;
}

if (isset($arParams['ENTITY_ID']))
{
	if (is_array($arParams['ENTITY_ID']))
	{
		array_walk($arParams['ENTITY_ID'], create_function('&$v',  '$v = (int)$v;'));
		$arFilter['ENTITY_ID'] = $arResult['ENTITY_ID'] = $arParams['ENTITY_ID'];
	}
	elseif ($arParams['ENTITY_ID'] > 0)
	{
		$arFilter['ENTITY_ID'] = $arResult['ENTITY_ID'] = (int)$arParams['ENTITY_ID'];
	}
}

if(isset($arParams['EVENT_COUNT']))
	$arResult['EVENT_COUNT'] = intval($arParams['EVENT_COUNT']) > 0? intval($arParams['EVENT_COUNT']): 20;
else
	$arResult['EVENT_COUNT'] = 20;

$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['VIEW_ID'] = isset($arParams['VIEW_ID']) ? $arParams['VIEW_ID'] : '';

$filterFieldPrefix = '';
if($bInternal)
{
	if($arResult['VIEW_ID'] !== '')
	{
		$filterFieldPrefix = $arResult['VIEW_ID'].'_';
	}
	elseif($arResult['TAB_ID'] !== '')
	{
		$filterFieldPrefix = strtoupper($arResult['TAB_ID']).'_';
	}
}

$arResult['FILTER_FIELD_PREFIX'] = $filterFieldPrefix;

$tabParamName = $arResult['FORM_ID'] !== '' ? $arResult['FORM_ID'].'_active_tab' : 'active_tab';
$activeTabID = isset($_REQUEST[$tabParamName]) ? $_REQUEST[$tabParamName] : '';

$arResult['GRID_ID'] = $arResult['INTERNAL'] ? 'CANDIDATE_INTERNAL_TRACK_LIST' : 'CANDIDATE_EVENT_LIST';
if($arResult['VIEW_ID'] !== '')
{
	$arResult['GRID_ID'] .= '_'.$arResult['VIEW_ID'];
}
elseif($arResult['TAB_ID'] !== '')
{
	$arResult['GRID_ID'] .= '_'.strtoupper($arResult['TAB_ID']);
}


if(check_bitrix_sessid())
{
	//Deletion of DELETE event is disabled
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['action_'.$arResult['GRID_ID']]))
	{
		if ($_REQUEST['action_'.$arResult['GRID_ID']] == 'delete' && isset($_REQUEST['ID']))
		{
			$YNSIREvent =  new YNSIREvent;

			if(is_array($_REQUEST['ID']))
			{
				foreach($_REQUEST['ID'] as $ID)
				{
					$ID = (int)$ID;
					if($ID > 0 && YNSIREvent::GetEventType($ID) !== $YNSIREvent::TYPE_DELETE)
					{
						$YNSIREvent->Delete($ID);
					}
				}
			}
			elseif($_REQUEST['ID'] > 0)
			{
				$ID = (int)$_REQUEST['ID'];
				if($ID > 0 && YNSIREvent::GetEventType($ID) !== $YNSIREvent::TYPE_DELETE)
				{
					$YNSIREvent->Delete($ID);
				}
			}
			unset($_REQUEST['ID']); // otherwise the filter will work
		}

		if (!$arResult['IS_AJAX_CALL'])
			LocalRedirect('?'.$arParams['FORM_ID'].'_active_tab=tab_event');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action_'.$arResult['GRID_ID']]))
	{
		if ($_GET['action_'.$arResult['GRID_ID']] == 'delete')
		{
			$YNSIREvent =  new YNSIREvent;
			$ID = (int)$_GET['ID'];
			if($ID > 0 && YNSIREvent::GetEventType($ID) !== $YNSIREvent::TYPE_DELETE)
			{
				$YNSIREvent->Delete($ID);
			}
			unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
		}

		if (!$arResult['IS_AJAX_CALL'])
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab='.$arResult['TAB_ID'] : '');
	}
}


$arResult['FILTER'] = array();
$arResult['FILTER_PRESETS'] = array();
if (!$arResult['INTERNAL'] || $arResult['SHOW_INTERNAL_FILTER'])
{
	$arResult['FILTER'] = array(
		array('id' => 'ID', 'name' => 'ID', 'default' => false),
	);
	$arResult['FILTER2LOGIC'] = array('EVENT_DESC');

	$arResult['FILTER'][] = array('id' => 'EVENT_TYPE','params' => array('multiple' => 'Y'),  'name' => GetMessage('YNSIR_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => YNSIREvent::GetEventTypes());
//	$arResult['FILTER'][] = array('id' => 'EVENT_ID', 'name' => GetMessage('YNSIR_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('EVENT_TYPE'));
	$arResult['FILTER'][] = array('id' => 'EVENT_DESC', 'name' => GetMessage('YNSIR_COLUMN_EVENT_DESC'));
	$arResult['FILTER'][] = array(
		'id' => 'CREATED_BY_ID',
		'name' => GetMessage('YNSIR_COLUMN_CREATED_BY_ID'),
		'default' => true,
		'type' => 'custom_entity',
		'selector' => array(
			'TYPE' => 'user',
			'DATA' => array('ID' => 'created_by_id', 'FIELD_ID' => 'CREATED_BY_ID')
		)
	);
	$arResult['FILTER'][] = array('id' => 'DATE_CREATE', 'name' => GetMessage('YNSIR_COLUMN_DATE_CREATE'), 'default' => true, 'type' => 'date');

	$currentUserID = $USER::GetId();
	$currentUserName = CYNSIRViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
//		'filter_change_today' => array('name' => GetMessage('YNSIR_PRESET_CREATE_TODAY'), 'fields' => array('DATE_CREATE_datesel' => 'today')),
//		'filter_change_yesterday' => array('name' => GetMessage('YNSIR_PRESET_CREATE_YESTERDAY'), 'fields' => array('DATE_CREATE_datesel' => 'yesterday')),
//		'filter_change_my' => array('name' => GetMessage('YNSIR_PRESET_CREATE_MY'), 'fields' => array('CREATED_BY_ID' => $currentUserID, 'CREATED_BY_ID_name' => $currentUserName))
	);
}

$arResult['HEADERS'] = array();
$arResult['HEADERS'][] = array('id' => 'ID', 'name' => 'ID', 'sort' => 'id', 'default' => false, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'DATE_CREATE', 'name' => GetMessage('YNSIR_COLUMN_DATE_CREATE'), 'sort' => '', 'default' => true, 'editable' => false, 'width'=>'140px');
if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
{
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TYPE', 'name' => GetMessage('YNSIR_COLUMN_ENTITY_TYPE'), 'sort' => '', 'default' => true, 'editable' => false);
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TITLE', 'name' => GetMessage('YNSIR_COLUMN_ENTITY_TITLE'), 'sort' => '', 'default' => true, 'editable' => false);
}
$arResult['HEADERS'][] = array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('YNSIR_COLUMN_CREATED_BY'), 'sort' => '', 'default' => true, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'EVENT_NAME', 'name' => GetMessage('YNSIR_COLUMN_EVENT_NAME'), 'sort' => '', 'default' => true, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'EVENT_DESC', 'name' => GetMessage('YNSIR_COLUMN_EVENT_DESC'), 'sort' => '', 'default' => true, 'editable' => false);

$arNavParams = array(
	'nPageSize' => $arResult['EVENT_COUNT']
);

$gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arFilter += $filterOptions->getFilter($arResult['FILTER']);

foreach ($arFilter as $k => $v)
{
	$arMatch = array();
	if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if($v !== '')
		{
			$arFilter['>='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	else if (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if($v !== '')
		{
			if($arMatch[1] == 'DATE_CREATE' && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
			{
				$v = CCrmDateTimeHelper::SetMaxDayTime($v);
			}
			$arFilter['<='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	else if (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			//Bugfix #42761 replace logic field name
			$arFilter['?'.($k === 'EVENT_DESC' ? 'EVENT_TEXT_1' : $k)] = $v;
		}
		unset($arFilter[$k]);
	}
	else if ($k == 'CREATED_BY_ID')
	{
		// For suppress comparison by LIKE
		$arFilter['=CREATED_BY_ID'] = $v;
		unset($arFilter['CREATED_BY_ID']);
	}
}

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

$_arSort = $gridOptions->GetSorting(array(
	'sort' => array('id' => 'desc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arNavParams = $gridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;
$arSelect = $gridOptions->GetVisibleColumns();
// HACK: ignore entity related fields if entity info is not displayed

$gridOptions->SetVisibleColumns($arSelect);

$nTopCount = false;
if ($isInGadgetMode)
{
	$nTopCount = $arResult['EVENT_COUNT'];
}

if($nTopCount > 0)
{
	$arNavParams['nTopCount'] = $nTopCount;
}

$arEntityList = Array();
$arResult['EVENT'] = Array();

//region Navigation data initialization
$pageSize = (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['EVENT_COUNT']);
$enableNextPage = false;
$pageNum = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int)$_REQUEST['page'] : 0;
if (isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y') {
    $pageNum = 1;
} elseif ($pageSize > 0 && isset($_REQUEST['page'])) {
    $pageNum = (int)$_REQUEST['page'];
    if ($pageNum < 0) {
        //Backward mode
        $offset = -($pageNum + 1);
        $total = YNSIREvent::GetListEx(array(), $arFilter, array());
        $pageNum = (int)(ceil($total / $pageSize)) - $offset;
        if ($pageNum <= 0) {
            $pageNum = 1;
        }
    }
}
if($pageNum > 0)
{
	if(!isset($_SESSION['YNSIRECRUITMENT_PAGINATION_DATA']))
	{
		$_SESSION['YNSIRECRUITMENT_PAGINATION_DATA'] = array();
	}
	$_SESSION['YNSIRECRUITMENT_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum);
}
else
{
	if(isset($_SESSION['YNSIRECRUITMENT_PAGINATION_DATA'])
		&& isset($_SESSION['YNSIRECRUITMENT_PAGINATION_DATA'][$arResult['GRID_ID']])
		&& isset($_SESSION['YNSIRECRUITMENT_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM']))
	{
		$pageNum = (int)$_SESSION['YNSIRECRUITMENT_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'];
	}

	if($pageNum <= 0)
	{
		$pageNum  = 1;
	}
}


//endregion

if ($isInGadgetMode && isset($arNavParams['nTopCount']))
{
	$arOptions = array('QUERY_OPTIONS' => array('LIMIT' => $arNavParams['nTopCount']));
}
else
{
	$arOptions = array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)));
}

$obRes = YNSIREvent::GetListEx($arResult['SORT'], $arFilter, false, false, array(), $arOptions);

$qty = 0;
while ($arEvent = $obRes->Fetch())
{
	if(++$qty > $pageSize)
	{
		$enableNextPage = true;
		break;
	}

	$arEvent['~FILES'] = $arEvent['FILES'];
	$arEvent['~EVENT_NAME'] = $arEvent['EVENT_NAME'];
	if (!empty($arEvent['CREATED_BY_ID']))
		$arEvent['~CREATED_BY_FULL_NAME'] = CUser::FormatName(
			$arParams["NAME_TEMPLATE"],
			array(
				'LOGIN' => $arEvent['CREATED_BY_LOGIN'],
				'NAME' => $arEvent['CREATED_BY_NAME'],
				'LAST_NAME' => $arEvent['CREATED_BY_LAST_NAME'],
				'SECOND_NAME' => $arEvent['CREATED_BY_SECOND_NAME']
			),
			true, false
		);
	$arEvent['CREATED_BY_FULL_NAME'] = htmlspecialcharsbx($arEvent['~CREATED_BY_FULL_NAME']);
	$arEvent['CREATED_BY_LINK'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'], array('user_id' => $arEvent['CREATED_BY_ID']));
	$arEvent['EVENT_NAME'] = htmlspecialcharsbx($arEvent['~EVENT_NAME']);

	$arEvent['CREATED_BY_PHOTO_URL'] = '';
	$createdByPhotoID = isset($arEvent['CREATED_BY_PERSONAL_PHOTO']) ? (int)$arEvent['CREATED_BY_PERSONAL_PHOTO'] : 0;
	if($createdByPhotoID > 0)
	{
		$file = new CFile();
		$fileInfo = $file->ResizeImageGet(
			$createdByPhotoID,
			array('width' => 38, 'height'=> 38),
			BX_RESIZE_IMAGE_EXACT
		);
		if(is_array($fileInfo) && isset($fileInfo['src']))
		{
			$arEvent['CREATED_BY_PHOTO_URL'] = $fileInfo['src'];
		}
	}

	$arEvent['~EVENT_TEXT_1'] = $arEvent['EVENT_TEXT_1'];
	$arEvent['~EVENT_TEXT_2'] = $arEvent['EVENT_TEXT_2'];

	$entityType = isset($arEvent['ENTITY_TYPE']) ? $arEvent['ENTITY_TYPE'] : '';
	$entityField = isset($arEvent['ENTITY_FIELD']) ? $arEvent['ENTITY_FIELD'] : '';

	if($entityField === 'COMMENTS'
		&& ($entityType === 'LEAD' || $entityType === 'CONTACT' || $entityType === 'COMPANY' || $entityType === 'DEAL'))
	{
		$arEvent['EVENT_TEXT_1'] = $arEvent['~EVENT_TEXT_1'];
		$arEvent['EVENT_TEXT_2'] = $arEvent['~EVENT_TEXT_2'];
	}
	else
	{
		$arEvent['EVENT_TEXT_1'] = strip_tags($arEvent['~EVENT_TEXT_1'], '<br><br/>');
		$arEvent['EVENT_TEXT_2'] = strip_tags($arEvent['~EVENT_TEXT_2'], '<br><br/>');
	}

	if (strlen($arEvent['EVENT_TEXT_1'])>255 && strlen($arEvent['EVENT_TEXT_2'])>255)
	{
		$arEvent['EVENT_DESC'] = '<div id="event_desc_short_'.$arEvent['ID'].'"><a href="#more" onclick="crm_event_desc('.$arEvent['ID'].'); return false;">'.GetMessage('YNSIR_EVENT_DESC_MORE').'</a></div>';
		$arEvent['EVENT_DESC'] .= '<div id="event_desc_full_'.$arEvent['ID'].'" style="display: none"><b>'.GetMessage('YNSIR_EVENT_DESC_BEFORE').'</b>:<br>'.($arEvent['EVENT_TEXT_1']).'<br><br><b>'.GetMessage('YNSIR_EVENT_DESC_AFTER').'</b>:<br>'.($arEvent['EVENT_TEXT_2']).'</div>';
	}
	elseif (strlen($arEvent['EVENT_TEXT_1'])>255)
	{
		$arEvent['EVENT_DESC'] = '<div id="event_desc_short_'.$arEvent['ID'].'">'.substr(($arEvent['EVENT_TEXT_1']), 0, 252).'... <a href="#more" onclick="crm_event_desc('.$arEvent['ID'].'); return false;">'.GetMessage('YNSIR_EVENT_DESC_MORE').'</a></div>';
		$arEvent['EVENT_DESC'] .= '<div id="event_desc_full_'.$arEvent['ID'].'" style="display: none">'.($arEvent['EVENT_TEXT_1']).'</div>';
	}
	else if (strlen($arEvent['EVENT_TEXT_2'])>255)
	{
		$arEvent['EVENT_DESC'] = '<div id="event_desc_short_'.$arEvent['ID'].'">'.substr(($arEvent['EVENT_TEXT_2']), 0, 252).'... <a href="#more" onclick="crm_event_desc('.$arEvent['ID'].'); return false;">'.GetMessage('YNSIR_EVENT_DESC_MORE').'</a></div>';
		$arEvent['EVENT_DESC'] .= '<div id="event_desc_full_'.$arEvent['ID'].'" style="display: none">'.($arEvent['EVENT_TEXT_2']).'</div>';
	}
	else if (strlen($arEvent['EVENT_TEXT_1'])>0 && strlen($arEvent['EVENT_TEXT_2'])>0)
		$arEvent['EVENT_DESC'] = ($arEvent['EVENT_TEXT_1']).' <span>&rarr;</span> '.($arEvent['EVENT_TEXT_2']);
	else
		$arEvent['EVENT_DESC'] = !empty($arEvent['EVENT_TEXT_1'])? ($arEvent['EVENT_TEXT_1']): '';
	$arEvent['EVENT_DESC'] = nl2br($arEvent['EVENT_DESC']);

	$arEvent['FILES'] = $arEvent['~FILES'] = $arEvent['FILES'] !== '' ? unserialize($arEvent['FILES']) : array();
	if (!empty($arEvent['FILES']))
	{
		$i=1;
		$arFiles = array();
		$rsFile = CFile::GetList(array(), array('@ID' => implode(',', $arEvent['FILES'])));
		while($arFile = $rsFile->Fetch())
		{
			$arFiles[$i++] = array(
				'NAME' => $arFile['ORIGINAL_NAME'],
				'PATH' => CComponentEngine::MakePathFromTemplate(
					'/bitrix/components/ynsirecruitment/ynsir.event.view/show_file.php?eventId=#event_id#&fileId=#file_id#',
					array('event_id' => $arEvent['ID'], 'file_id' => $arFile['ID'])
				),
				'SIZE' => CFile::FormatSize($arFile['FILE_SIZE'], 1)
			);
		}
		$arEvent['FILES'] = $arFiles;
	}
	$arEntityList[$arEvent['ENTITY_TYPE']][$arEvent['ENTITY_ID']] = $arEvent['ENTITY_ID'];

	$arEvent['PATH_TO_DELETE'] = CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_EVENT_LIST'],
		array(
			'action_'.$arResult['GRID_ID'] => 'delete',
			'ID' => $arEvent['ID'],
			'sessid' => $arResult['SESSION_ID']
		)
	);
	$arResult['EVENT'][] = $arEvent;
}

//region Navigation data storing
$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
// Prepare raw filter ('=CREATED_BY' => 'CREATED_BY')
$arResult['DB_FILTER'] = array();
foreach($arFilter as $filterKey => &$filterItem)
{
	$info = CSqlUtil::GetFilterOperation($filterKey);
	$arResult['DB_FILTER'][$info['FIELD']] = $filterItem;
}
unset($filterItem);

if(!isset($_SESSION['YNSIR_GRID_DATA']))
{
	$_SESSION['YNSIR_GRID_DATA'] = array();
}
$_SESSION['YNSIR_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
//endregion

$this->IncludeComponentTemplate();

return $obRes->SelectedRowsCount();

?>