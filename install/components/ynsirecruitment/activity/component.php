<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('ynsirecruitment')) {
	ShowError(GetMessage('RECRUITMENT_MODULE_NOT_INSTALLED'));
	return;
}

$arResult["GRID_ID"] = "ACTIVITY_GRID";
$arResult['HEADERS'] = array(
	array("id"=>"TYPE", "name"=>GetMessage("RECRUITMENT_COLUMN_TYPE"), "default"=>true),
	array("id"=>"USER", "name"=>GetMessage("RECRUITMENT_COLUMN_USER"), "default"=>true),
	array("id"=>"DATE", "name"=>GetMessage("RECRUITMENT_COLUMN_DATE"), "default"=>true),
	array("id"=>"DESCRIPTION", "name"=>GetMessage("RECRUITMENT_COLUMN_DESCRIPTION"), "default"=>true),
);

$arFilter = array();
if (isset($arParams['user_id'])) {
	$arFilter['USER_ID'] = $arParams['user_id'];
}
if (isset($arParams['job_order_id'])) {
	$arFilter['JOB_ORDER_ID'] = $arParams['job_order_id'];
}
if (isset($arParams['candidate_id'])) {
	$arFilter['CANDIDATE_ID'] = $arParams['candidate_id'];
}

$grid_options = new CGridOptions($arResult["GRID_ID"]);
$arGridFilter = $grid_options->GetFilter($arResult['FILTER']);
$aSort = $grid_options->GetSorting(array("sort"=>array("id"=>"desc"), "vars"=>array("by"=>"by", "order"=>"order")));
$aSortVal = $aSort['sort'];
$sort_order = current($aSortVal);
$sort_by = key($aSortVal);
$arFilter['IS_ACTIVE'] = '1';
$listActivity = CRecruitmentActivity::GetList(array('ID' => 'DESC'), $arFilter, false, array('nTopCount' => 5));
while ($act = $listActivity->Fetch()) {
	$user = CUser::GetByID($act['USER_ID'])->Fetch();
	$act['TYPE'] = htmlspecialcharsbx($act['TYPE']);
	$act['USER'] = htmlspecialcharsbx($user['NAME'] . ' ' . $user['LAST_NAME']);
	$act['DATE'] = date('d/m/Y H:i:s', strtotime($act['CREATED_AT']));
	$act['DESCRIPTION'] = htmlspecialcharsbx($act['DESCRIPTION']);
	$arRow[] = array('data' => $act);
}

$arResult["SORT"] = $aSort["sort"];
$arResult["SORT_VARS"] = $aSort["vars"];
$arResult['ROWS'] = $arRow;
$arResult["ROWS_COUNT"] = $listActivity->NavRecordCount;
$arResult["NAV_OBJECT"] = $listActivity;
$this->IncludeComponentTemplate();
?>