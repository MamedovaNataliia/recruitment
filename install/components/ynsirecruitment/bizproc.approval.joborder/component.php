<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('ynsirecruitment')) {
	ShowError(GetMessage('RBIOC_MODULE'));
	die;
}
$arFilter['ID'] = $arParams['job_order_id'];
if($arParams['action'] == RECRUITMENT_ACTION_APPROVED){
    $arFilter['STATUS'] = 'WAITING';
}
$rs = YNSIRJobOrder::GetListJobOrder(array("ID" => "DESC"), $arFilter);
if($jo = $rs->Fetch()){
    $arResult['JO'] = $jo;
}
$arResult['STATUS'] = YNSIRGeneral::getListJobStatus();
$this->IncludeComponentTemplate();
?>