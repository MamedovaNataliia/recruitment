<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('ynsirecruitment')) {
	ShowError(GetMessage('RBIOC_MODULE'));
	die;
}
global $USER;
$arFilter['STATUS'] = 'NEW';
$arFilter['CREATED_BY'] = $USER->GetID();
$rs = YNSIRJobOrder::GetListJobOrder(array("ID" => "DESC"), $arFilter);
while($jo = $rs->Fetch()){
    $arResult['JO'][$jo['ID']] = $jo;
}
$this->IncludeComponentTemplate();
?>