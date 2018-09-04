<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

IncludeModuleLangFile(__FILE__);
$aMenuLinks = $GLOBALS["APPLICATION"]->IncludeComponent(
    'ynsirecruitment:controll_panel',
    '',
    array(
        "MENU_MODE" => "Y"
    )
);
?>