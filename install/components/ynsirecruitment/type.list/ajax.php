<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->RestartBuffer();
if (!CModule::IncludeModule("hrm"))
    {
        return;
    }
if(isset($_REQUEST['NAME']) && strlen($_REQUEST['NAME']) > 0){
    $_REQUEST["NAME"] = preg_replace('/\s+/u', ' ', $_REQUEST["NAME"]);
    $arResult = array();
    if($_REQUEST['LANGUAGE_ID'] == 'en') {
        $arFilter = array(
            'ENTITY' => $_REQUEST['entity'],
            '~NAME_EN' => '%' . $_REQUEST['NAME'] . '%',
        );
    }else{
        $arFilter = array(
            'ENTITY' => $_REQUEST['entity'],
            '~NAME_VN' => '%' . $_REQUEST['NAME'] . '%',
        );
    }
//    echo json_encode($arFilter);
    $arSort = array(
        'COUNT' => 'DESC',
        'ID'=> 'DESC'
        );
    $arNavParams = array(
        "nPageSize" => 8,
        "iNumPage" => 1
        );
    $rsSkill = HRMSkill::GetList($arSort,$arFilter,$arNavParams);
    $arResult['ITEMS'] = '';
    $arResult['ITEMS_VIEW'] = '';
    while($element_skill = $rsSkill->GetNext())
    {
        if($_REQUEST['LANGUAGE_ID'] == 'en') {
            $arItem['value'] = $element_skill['NAME_EN'];
            $arItem['label'] = $element_skill['NAME_EN'];
            $arResult['ITEMS_VIEW_EN'][] = $element_skill['NAME_EN'];
        }else{
            $arItem['value'] = $element_skill['NAME_VN'];
            $arItem['label'] = $element_skill['NAME_VN'];
            $arResult['ITEMS_VIEW_VN'][] = $element_skill['NAME_VN'];
        }
//        $arResult['ITEMS_VIEW_EN'][] = $element_skill['NAME_EN'];
//        $arResult['ITEMS_VIEW_VN'][] = $element_skill['NAME_VN'];
        $arItem['name'] = $element_skill['ID'];
        $arResult['ITEMS'][] = $arItem;   

    }
    echo json_encode($arResult);
    die;
}
?>
