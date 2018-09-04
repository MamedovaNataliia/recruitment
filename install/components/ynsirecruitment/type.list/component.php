<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $arOrderReasontype, $arOrderType;
global $DB, $USER;
CJSCore::Init(array("jquery"));
if (!CModule::IncludeModule("bizproc")) {
    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}
if (!CModule::IncludeModule("ynsirecruitment")) {
    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}
$CURRENT_USER = $USER->GetID();
$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat(false);

$userPermissions = YNSIRPerms_::GetCurrentUserPermissions();

$arInSuggest = array(
    GetMessage('STATUS_NO'),
    GetMessage('STATUS_YES')
);

$arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");
$arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");

$typeList = YNSIRConfig::getTypeList();
switch ($arParams['entity']) {
    default:
        $arResult['SHOW_ADDINATIONAL_VALUE'] = true;
        $arResult['SHOW_CODE'] = false;
        break;
}
$arParams['entity'] = $arParams['entity'] == 'city'?'current_former_issue_place':$arParams['entity'];
if (empty($typeList[strtoupper($arParams['entity'])])) {
    LocalRedirect('/recruitment/config/');
}
$arResult['CONFIG']['CONTENT_TYPE'] = YNSIRConfig::getListContentType();
$arResult['TYPE_LIST'] = $typeList[strtoupper($arParams['entity'])];
/*
 * get permission
 */
$arResult['ACCESS_PERMS']['READ']    = !$userPermissions->HavePerm('CONFIG', YNSIR_PERM_NONE);//!$userPermissions->HavePerm(YNSIR_PERM_ENTITY_LIST, YNSIR_PERM_NONE, 'READ');
$arResult['ACCESS_PERMS']['ADD']     = !$userPermissions->HavePerm('CONFIG', YNSIR_PERM_NONE);//!$userPermissions->HavePerm(YNSIR_PERM_ENTITY_LIST, YNSIR_PERM_NONE, 'ADD');
$arResult['ACCESS_PERMS']['EDIT']    = !$userPermissions->HavePerm('CONFIG', YNSIR_PERM_NONE);//!$userPermissions->HavePerm(YNSIR_PERM_ENTITY_LIST, YNSIR_PERM_NONE, 'WRITE');
$arResult['ACCESS_PERMS']['DELETE']  = !$userPermissions->HavePerm('CONFIG', YNSIR_PERM_NONE);//!$userPermissions->HavePerm(YNSIR_PERM_ENTITY_LIST, YNSIR_PERM_NONE, 'DELETE');
/*
 * end getpermission
 */
if (isset($_REQUEST['action_button_YNSIR_'.$arParams['entity']])
    && $_REQUEST['action_button_YNSIR_'.$arParams['entity']] == 'delete') {
    $APPLICATION->RestartBuffer();
    $st = YNSIRTypelist::DeletebyArray($_REQUEST['ID'],$arParams['entity']);
}
if (!$arResult['ACCESS_PERMS']['READ'] ) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $APPLICATION->RestartBuffer();
        $arReturn['STATUS'] = "FAIL";
        $arReturn['MESSAGE'] = GetMessage('YNSIR_ACCESS_DENY');
        echo json_encode($arReturn);
        exit();
    } else {
        ShowError(GetMessage('YNSIR_ACCESS_DENY'));
    }
} else {

    $arResult["HEADERS"] = array(
		array("id" => "ID", "name" => GetMessage('YNSIR_KEY_ID'), "sort" => "ID", "default" => false),
        array("id" => "NAME_EN", "name" => GetMessage('YNSIR_NAME_EN'), "sort" => "NAME_EN", "default" => true),
        array("id" => "SORT", "name" => GetMessage('YNSIR_SORT'), "sort" => "SORT", "default" => true),
        array("id" => "CREATED_BY", "name" => GetMessage('YNSIR_CREATED_BY'), "sort" => "CREATED_BY", "default" => false),
        array("id" => "MODIFIED_BY", "name" => GetMessage('YNSIR_MODIFIED_BY'), "sort" => "MODIFIED_BY", "default" => false),
        array("id" => "CREATED_DATE", "name" => GetMessage('YNSIR_CREATED_DATE'), "sort" => "CREATED_DATE", "default" => false),
        array("id" => "MODIFIED_DATE", "name" => GetMessage('YNSIR_MODIFIED_DATE'), "sort" => "MODIFIED_DATE", "default" => false),
        );

    $arResult["FILTER"] = array(
		array("id" => "ID", "name" => GetMessage('YNSIR_KEY_ID'), 'type' => 'int'),
        array("id" => "NAME_EN", "name" => GetMessage('YNSIR_NAME_EN'), 'type' => 'string'),
        array("id" => "SORT", "name" => GetMessage('YNSIR_SORT'), 'type' => 'int'),
        array(
            'id' => "CREATED_BY",
            'name' => GetMessage('YNSIR_CREATED_BY'),
            'type' => 'custom_entity',
            'params' => array('multiple' => 'Y'),
            'selector' => array(
                'TYPE' => 'user',
                'DATA' => array('ID' => 'CREATED_BY', 'FIELD_ID' => "CREATED_BY" )
            )),
        array(
            'id' => "MODIFIED_BY",
            'name' => GetMessage('YNSIR_MODIFIED_BY'),
            'type' => 'custom_entity',
            'params' => array('multiple' => 'Y'),
            'selector' => array(
                'TYPE' => 'user',
                'DATA' => array('ID' => 'MODIFIED_BY', 'FIELD_ID' => "MODIFIED_BY" )
            )),
        array("id" => "CREATED_DATE", "name" => GetMessage('YNSIR_CREATED_DATE'), 'type' => 'date'),
        array("id" => "MODIFIED_DATE", "name" => GetMessage('YNSIR_MODIFIED_DATE'), 'type' => 'date')
    );
    if($arResult['SHOW_CODE']) {
        $arResult["HEADERS"][] = array("id" => "CODE", "name" => GetMessage('YNSIR_LIST_KEY_CODE'), "sort" => "CODE", "default" => true);
        $arResult["FILTER"][] = array("id" => "CODE", "name" => GetMessage('YNSIR_LIST_KEY_CODE'), "type" => "string");
    }
    if($arResult['SHOW_ADDINATIONAL_VALUE']) {
        $arResult["HEADERS"][] = array("id" => "ADDITIONAL_INFO", "name" => GetMessage('YNSIR_ADDITIONAL_INFO'), "sort" => "ADDITIONAL_INFO", "default" => true);
        $arResult["HEADERS"][] = array("id" => "ADDITIONAL_INFO_LABEL_EN", "name" => GetMessage('YNSIR_ADDITIONAL_INFO_LABEL_EN'), "sort" => "ADDITIONAL_INFO_LABEL_EN", "default" => true);
        $arResult["FILTER"][] = array("id" => "ADDITIONAL_INFO", "name" => GetMessage('YNSIR_ADDITIONAL_INFO'), 'type' => 'list','items'=>$arResult['CONFIG']['CONTENT_TYPE'] );
        $arResult["FILTER"][] = array("id" => "ADDITIONAL_INFO_LABEL_EN", "name" => GetMessage('YNSIR_ADDITIONAL_INFO_LABEL_EN'), 'type' => 'string');
    }
    $arResult["GRID_ID"] = 'YNSIR_'.$arParams['entity'];
    $arParams["ORDER_PER_PAGE"] = (intval($arParams["ORDER_PER_PAGE"]) <= 0 ? 10 : intval($arParams["ORDER_PER_PAGE"]));


    $arResult['FILTER_PRESETS'] = array();
    $arFilter = $arSort = array();
    $arNavigation = CDBResult::GetNavParams($arNavParams);
    $gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
    $filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
    $arNavParams = $gridOptions->GetNavParams($arNavParams);
    $arNavParams['bShowAll'] = false;
    if (!$arResult['IS_EXTERNAL_FILTER']) {
        $arFilter += $filterOptions->getFilter($arResult['FILTER']);
    }

    // converts data from filter
    if(isset($arFilter['FIND']))
    {
        if(is_string($arFilter['FIND']))
        {
            $find = trim($arFilter['FIND']);
            if($find !== '')
            {
                $arFilter['SEARCH_CONTENT'] = $find;
            }
        }
        unset($arFilter['FIND']);
    }

    $arImmutableFilters = array(
        'ID','NAME_EN', 'NAME_EN','ADDITIONAL_INFO_LABEL_EN',
        'CREATED_BY', 'MODIFY_BY_ID',
        'WEBFORM_ID', 'IS_RETURN_CUSTOMER',
        'SEARCH_CONTENT',
        'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID'
    );
    foreach ($arFilter as $k => $v)
    {
        if(in_array($k, $arImmutableFilters, true))
        {
            continue;
        }

        $arMatch = array();

        if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
        {
            if(strlen($v) > 0)
            {
                $arFilter['>='.$arMatch[1]] = $v;
            }
            unset($arFilter[$k]);
        }
        elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
        {
            if(strlen($v) > 0)
            {
                if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
                {
                    $v = CCrmDateTimeHelper::SetMaxDayTime($v);
                }
                $arFilter['<='.$arMatch[1]] = $v;
            }
            unset($arFilter[$k]);
        }
        elseif (in_array($k, $arResult['FILTER2LOGIC']))
        {
            // Bugfix #26956 - skip empty values in logical filter
            $v = trim($v);
            if($v !== '')
            {
                $arFilter['?'.$k] = $v;
            }
            unset($arFilter[$k]);
        }
        elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && $k != '__JOINS' && $k != '__CONDITIONS' && strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1)
        {
            $arFilter['%'.$k] = $v;
            unset($arFilter[$k]);
        }
    }

    if (!empty($arFilter['SEARCH_CONTENT'])) {
        $t = $arFilter;
        unset($t['SEARCH_CONTENT']);
        $arF = explode(" ", $arFilter['SEARCH_CONTENT']);

        foreach ($arF as $k => $v) {
            $f['__INNER_FILTER_ID_' . $v] = array(
                'LOGIC' => 'OR',
                '%NAME_EN' =>  $v,
                '%ADDITIONAL_INFO_LABEL_EN' => $v,);
        }
        $arFilter = array('LOGIC' => 'AND',
            '__INNER_FILTER_ID1' => $t,
            '__INNER_FILTER_ID2' => $f);
    }
    CCrmEntityHelper::PrepareMultiFieldFilter($arFilter, array(), '=%', false);
    //SORT
    $aSort = $gridOptions->GetSorting(
        array(
            'sort' => array('id' => 'desc'),
            'vars' => array('by' => 'by', 'order' => 'order')
        )
    );
    $aSortVal = $aSort['sort'];
    //end config sort

    //page
    //region Navigation data initialization
    $pageNum = 0;
    $pageSize = !$isInExportMode
        ? (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['DEAL_COUNT']) : 0;

    $enableNextPage = false;
    if(isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
    {
        $pageNum = 1;
    }
    elseif($pageSize > 0 && isset($_REQUEST['page']))
    {
        $pageNum = (int)$_REQUEST['page'];
        if($pageNum < 0)
        {
            //Backward mode
            $offset = -($pageNum + 1);
//            $total = YNSIRTypelist::GetList(array(), $arFilter, array());
            $gridData = $_SESSION['YNSIR_PAGINATION_DATA'][$arResult["GRID_ID"]];
            $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
            $result = YNSIRTypelist::GetList(array(), $filter, array(),false,  array());//CCrmContact::GetListEx(array(), $filter, array(), false, array(), array());

            $pageNum = (int)(ceil($result / $pageSize)) - $offset;
            if($pageNum <= 0)
            {
                $pageNum = 1;
            }
        }
    }
    if ($pageNum > 0) {
        if (!isset($_SESSION['YNSIR_PAGINATION_DATA'])) {
            $_SESSION['YNSIR_PAGINATION_DATA'] = array();
        }
        $_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum);
    } else {
        if (!$bInternal
            && !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
            && isset($_SESSION['YNSIR_PAGINATION_DATA'])
            && isset($_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']])
            && isset($_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'])
        ) {
            $pageNum = (int)$_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'];
        }

        if ($pageNum <= 0) {
            $pageNum = 1;
        }
    }
    if ($isInGadgetMode && isset($arNavParams['nTopCount']))
    {
        $navListOptions = array_merge($arOptions, array('QUERY_OPTIONS' => array('LIMIT' => $arNavParams['nTopCount'])));
    }
    else
    {
        $navListOptions = $isInExportMode
            ? $arExportOptions
            : array_merge(
                array(),
                array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)))
            );
    }
    //end page
    //Un deleted order;ms);
    $arFilter['ENTITY'] = strtoupper($arParams['entity']);
    $_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']]['FILTER'] = $arFilter;
    $rsOrder = YNSIRTypelist::GetList($aSortVal, $arFilter, false,false,$navListOptions,false);
    $strNameFormat = CSite::GetNameFormat(false);

    $qty = 0;
    while ($arTypeList = $rsOrder->GetNext()) {
        if($pageSize > 0 && ++$qty > $pageSize)
        {
            $enableNextPage = true;
            break;
        }
        $arItem = $arTypeList;
        $arItem['CREATED_DATE'] = $DB->FormatDate($arTypeList['CREATED_DATE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_BX_SHORT']);
        $arItem['MODIFIED_DATE'] = $DB->FormatDate($arTypeList['MODIFIED_DATE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_BX_SHORT']);
        //get user created name
        if (!empty($arItem['CREATED_BY']))
            $arItem['CREATED_BY_FULL_NAME'] = CUser::FormatName(
                $arParams["NAME_TEMPLATE"],
                array(
                    'LOGIN' => $arItem['CREATED_BY_LOGIN'],
                    'NAME' => $arItem['CREATED_BY_NAME'],
                    'LAST_NAME' => $arItem['CREATED_BY_LAST_NAME'],
                    'SECOND_NAME' => $arItem['CREATED_BY_SECOND_NAME']
                    ),
                true, false
            );
        $arItem['CREATED_BY_PHOTO_URL'] = '';
        $createdByPhotoID = isset($arItem['CREATED_BY_PERSONAL_PHOTO']) ? (int)$arItem['CREATED_BY_PERSONAL_PHOTO'] : 0;
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
                $arItem['CREATED_BY_PHOTO_URL'] = $fileInfo['src'];
            }
        }
        if($arItem['CREATED_BY_FULL_NAME'] !== '')
        {
            $arItem['CREATED_BY'] = "<div class = \"ynsir-list-summary-wrapper\">
				<div class = \"ynsir-list-photo-wrapper\">
					<div class=\"ynsir-list-def-pic\">
						<img alt=\"Author Photo\" src=\"{$arItem['CREATED_BY_PHOTO_URL']}\"/>
					</div>
				</div>
				<div class=\"ynsir-list-info-wrapper\">
					<div class=\"ynsir-list-title-wrapper\">
						<a href=\"/company/personal/user/" . $arItem['CREATED_BY'] . "/\" id=\"LIST_CREATED_BY_{$arResult['GRID_ID']}_{$arItem['ID']}\">{$arItem['CREATED_BY_FULL_NAME']}</a>
						<script type=\"text/javascript\">BX.tooltip({$arItem['CREATED_BY']}, \"LIST_CREATED_BY_{$arResult['GRID_ID']}_{$arItem['ID']}\", \"\");</script>
					</div>
				</div>
			</div>";
        }
        //get user created name
        if (!empty($arItem['MODIFIED_BY']))
            $arItem['MODIFIED_BY_FULL_NAME'] = CUser::FormatName(
                $arParams["NAME_TEMPLATE"],
                array(
                    'LOGIN' => $arItem['MODIFIED_BY_LOGIN'],
                    'NAME' => $arItem['MODIFIED_BY_NAME'],
                    'LAST_NAME' => $arItem['MODIFIED_BY_LAST_NAME'],
                    'SECOND_NAME' => $arItem['MODIFIED_BY_SECOND_NAME']
                ),
                true, false
            );
        $arItem['MODIFIED_BY_PHOTO_URL'] = '';
        $createdByPhotoID = isset($arItem['MODIFIED_BY_PERSONAL_PHOTO']) ? (int)$arItem['MODIFIED_BY_PERSONAL_PHOTO'] : 0;
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
                $arItem['MODIFIED_BY_PHOTO_URL'] = $fileInfo['src'];
            }
        }
        if($arItem['MODIFIED_BY_FULL_NAME'] !== '')
        {
            $arItem['MODIFIED_BY'] = "<div class = \"ynsir-list-summary-wrapper\">
				<div class = \"ynsir-list-photo-wrapper\">
					<div class=\"ynsir-list-def-pic\">
						<img alt=\"Author Photo\" src=\"{$arItem['MODIFIED_BY_PHOTO_URL']}\"/>
					</div>
				</div>
				<div class=\"ynsir-list-info-wrapper\">
					<div class=\"ynsir-list-title-wrapper\">
						<a href=\"/company/personal/user/" . $arItem['MODIFIED_BY'] . "/\" id=\"LIST_MODIFIED_BY_{$arResult['GRID_ID']}_{$arItem['ID']}\">{$arItem['MODIFIED_BY_FULL_NAME']}</a>
						<script type=\"text/javascript\">BX.tooltip({$arItem['MODIFIED_BY']}, \"LIST_MODIFIED_BY_{$arResult['GRID_ID']}_{$arItem['ID']}\", \"\");</script>
					</div>
				</div>
			</div>";
        }
        $arResult["ITEMS"][] = $arItem;
    }


    foreach ($arResult["ITEMS"] as $idex => &$arTypeList) {
        $aActions = Array();
        if($arResult['ACCESS_PERMS']['EDIT'] ) {
            $aActions[] = Array(
//                "ICONCLASS" => "edit",
                "TEXT" => GetMessage('YNSIR_SKILL_ACTION_EDIT'),
                "DEFAULT" => true,
                "ONCLICK" => "Edit_key(" . $arTypeList["ID"] . ")");
        }
        if($arResult['ACCESS_PERMS']['DELETE'] ) {
            $aActions[] = Array(/*"ICONCLASS" => "delete",*/ "TEXT" => GetMessage('YNSIR_SKILL_ACTION_DELETE'), "ONCLICK" => "Delete_key(" . $arTypeList["ID"] . ")");
        }
        $arTypeList['ADDITIONAL_INFO'] = (key_exists($arTypeList['ADDITIONAL_INFO'],$arResult['CONFIG']['CONTENT_TYPE']))?$arResult['CONFIG']['CONTENT_TYPE'][$arTypeList['ADDITIONAL_INFO']]:'';

        $aRows[] = array("data" => $arTypeList, "actions" => $aActions, "columns" => $aCols);
    }

    $arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage,'NUM_PAGES_SHOW' => 3);
    $arResult["ROWS"] = $aRows;
    $arResult["ROWS_COUNT"] = $rsOrder->SelectedRowsCount();
    $arResult["NAV_STRING"] = $rsOrder->GetPageNavString(GetMessage("SUP_PAGES"));
    $arResult["CURRENT_PAGE"] = htmlspecialcharsbx($APPLICATION->GetCurPage());

    $arResult["SORT"] = $aSort["sort"];
    $arResult["SORT_VARS"] = $aSort["vars"];

    $arResult["NAV_OBJECT"] = $rsOrder;
    $arResult['COMPONENTPATH'] = $componentPath;
    $this->IncludeComponentTemplate();
}
function removeqsvar($url, $varname)
{
    list($urlpart, $qspart) = array_pad(explode('?', $url), 2, '');
    parse_str($qspart, $qsvars);
    unset($qsvars[$varname]);
    $newqs = http_build_query($qsvars);
    return $urlpart . '?' . $newqs;
}

?>