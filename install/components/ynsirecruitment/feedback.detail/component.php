<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->ShowAjaxHead();
if (!CModule::IncludeModule("bizproc")) {
    return false;
}
if (!CModule::IncludeModule('ynsirecruitment')) {
    return;
}
if (!CModule::IncludeModule('ynsirecruitment')) {
    return;
}
$p = new blogTextParser();
$sFormatName = CSite::GetNameFormat(false);
$arResult['status'] = true;
if (isset($arParams['feedback_id']) && $arParams['feedback_id'] > 0) {

    $arFilter = array(
        'ID' => $arParams['feedback_id']
    );
    $obRes = YNSIRFeedback::GetList(
        $arSort,
        $arFilter,
        false,
        false,
        $navListOptions
    );
    if ($rs = $obRes->Fetch()) {
        $arResult = $rs;
        $arResult['DESCRIPTION'] = $p->convert($arResult['DESCRIPTION']);
        $arResult['CANDIDATE_NAME'] = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $rs['FIRST_NAME'],
                "LAST_NAME" => $rs['LAST_NAME'],
            )
        );
    }else{
        $arResult['status'] = 'not_data';
    }

} else {
    $arResult['status'] = 'not_data';
}
$arResult['CONFIG']['ROUND'] = YNSIRInterview::getListDetail(array(), array(), false, false, array());
$this->IncludeComponentTemplate($defaultTemplateName);
?>