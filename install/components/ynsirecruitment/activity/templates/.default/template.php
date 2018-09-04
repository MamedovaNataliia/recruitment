<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CUtil::InitJSCore( array('ajax' , 'jquery' , 'popup' ));
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/activity.js');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/interface_grid.js');
?>
<div class="container">
<?

$component = $this->getComponent();
$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID"=>$arResult["GRID_ID"],
		"HEADERS"=>$arResult['HEADERS'],
		"SORT"=>$arResult["SORT"],
		"SORT_VARS"=>$arResult["SORT_VARS"],
		"ROWS"=>$arResult["ROWS"],
		// "FOOTER"=>array(array("title"=>GetMessage('Total'), "value"=>$arResult["ROWS_COUNT"])),
		"ACTION_ALL_ROWS"=>false,
		"EDITABLE"=>false,
		"NAV_OBJECT"=>$arResult["NAV_OBJECT"],
	),
	$component
);
?>
</div>
