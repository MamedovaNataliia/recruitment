<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/js/crm/css/crm.css");
CJSCore::Init(array("pin"));

$js = <<<HTML

<script>
if (!window.B24menuItemsObj.initPagetitleStar())
{
	BX.ready(function() {
		window.B24menuItemsObj.initPagetitleStar()
	});
}

</script>
HTML;

$isBitrix24 = SITE_TEMPLATE_ID === "bitrix24";
if ($isBitrix24)
{
	$this->SetViewTarget("above_pagetitle", $js, 10);
}
$APPLICATION->IncludeComponent(
	"bitrix:main.interface.buttons",
	"",
	array(
		"ID" => 'ynsirecruitment',
		"ITEMS" => $arResult['ITEMS'],
		"MORE_BUTTON" => $arResult['MORE_BUTTON'],
	)
);
if ($isBitrix24)
{
	$this->EndViewTarget("sidebar");
}
?>