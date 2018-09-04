<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule('ynsirecruitment') or die;

$arParams['ACTIVE'] = isset($arParams['ACTIVE']) ? $arParams['ACTIVE'] : YNSIR_SETTING;

$arResult['ITEMS'] = array();

/* JOB ORDER */
$arResult['ITEMS'][] = array(
    "TEXT" => GetMessage("SUBMENU_YNSIR_JOB_ORDER"),
    "URL" => "/recruitment/job-order/",
    "CLASS" => "crm-menu-lead crm-menu-item-wrap",
    "CLASS_SUBMENU_ITEM" => "crm-menu-more-lead",
    "ID" => YNSIR_JOB_ORDER,
    "SUB_LINK" => "",
    "COUNTER" => "",
    "IS_ACTIVE" => $arParams['ACTIVE'] == YNSIR_JOB_ORDER ? 1 : 0,
    "IS_LOCKED" => "",
    "SUB_LINK" => array(
        "CLASS" => "crm-menu-plus-btn",
        "URL" => "/recruitment/job-order/edit/0/",
    ),
);

/* CANDIDATE */
$arResult['ITEMS'][] = array(
	"TEXT" => GetMessage("SUBMENU_YNSIR_CANDIDATE"),
    "URL" => "/recruitment/candidate/",
    "CLASS" => "crm-menu-lead crm-menu-item-wrap",
    "CLASS_SUBMENU_ITEM" => "crm-menu-more-lead",
    "ID" => YNSIR_CANDIDATE,
    "SUB_LINK" => "",
    "COUNTER" => "",
    "IS_ACTIVE" => $arParams['ACTIVE'] == YNSIR_CANDIDATE ? 1 : 0,
    "IS_LOCKED" => "",
    "SUB_LINK" => array(
    	"CLASS" => "crm-menu-plus-btn",
    	"URL" => "/recruitment/candidate/edit/0/",
    ),
);

/* SETTING */
$arResult['ITEMS'][] = array(
	"TEXT" => GetMessage("SUBMENU_YNSIR_CONFIG"),
    "URL" => "/recruitment/config/",
    "CLASS" => "crm-menu-catalog crm-menu-item-wrap",
    "CLASS_SUBMENU_ITEM" => "crm-menu-more-catalog",
    "ID" => YNSIR_SETTING,
    "SUB_LINK" => "",
    "COUNTER" => "",
    "IS_ACTIVE" => $arParams['ACTIVE'] == YNSIR_SETTING ? 1 : 0,
    "IS_LOCKED" => ""
);

if (isset($arParams["MENU_MODE"]) && $arParams["MENU_MODE"] === "Y")
{
    $arLeftMenu = array();
    foreach ($arResult['ITEMS'] as $key => $item)
    {
        $arLeftMenu[] = array(
            $item["TEXT"],
            $item["URL"],
            array(),
            array("fixed" => "N"),
            ""
        );
    }
    return $arLeftMenu;
}
else
{
    $this->IncludeComponentTemplate();
}
?>