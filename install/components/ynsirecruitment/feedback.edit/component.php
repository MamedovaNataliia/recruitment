<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->ShowAjaxHead();
if (!CModule::IncludeModule("bizproc")) {
    return false;
}
if (!CModule::IncludeModule('ynsirecruitment')) {
    return;
}
if (isset($_POST['ADD']) && ($_POST['ADD'] == "Y") && check_bitrix_sessid()) {
    $Fields = array('TITLE', 'CANDIDATE_ID', 'JOB_ORDER_ID', 'ROUND_ID', 'DESCRIPTION');
    $data = array();
    $result = array();
    $result['STATUS'] = 'SUCCESS';

    foreach ($Fields as $f) {
        $data[$f] = trim($_POST[$f]);
        switch ($f) {
            case 'CANDIDATE_ID':
            case 'JOB_ORDER_ID':
            case 'ROUND_ID':
                if ($data[$f] <= 0) {
                    $result['STATUS'] = 'FAILED';
                    $result['ERROR'][$f] = array('msg' => GetMessage('YNSIR_FEEDBACK_' . $f) . ' is not specified.');
                }
                break;
            default:
                if (strlen($data[$f]) <= 0) {
                    $result['STATUS'] = 'FAILED';
                    $result['ERROR'][$f] = array('msg' => GetMessage('YNSIR_FEEDBACK_' . $f) . ' is not specified.');
                }
                break;
        }

    }
    if ($result['STATUS'] == 'SUCCESS') {
        //check edit or add
        $arFilter = array(
            'CANDIDATE_ID' => $data['CANDIDATE_ID'],
            'JOB_ORDER_ID' => $data['JOB_ORDER_ID'],
            'ROUND_ID' => $data['ROUND_ID'],
            'CREATED_BY' => $USER->GetID(),
        );
        $obRes = YNSIRFeedback::GetList(
            $arSort,
            $arFilter,
            false,
            false,
            $navListOptions
        );
        if ($rs = $obRes->Fetch()) {
            $ID = YNSIRFeedback::Update($rs['ID'], $data);
            $result['EDIT'] = true;
        } else {
            $ID = YNSIRFeedback::Add($data);
            $result['ADD'] = true;
        }
    }
    $APPLICATION->RestartBuffer();
    echo json_encode($result);
    exit();
}
$sFormatName = CSite::GetNameFormat(false);
$arResult['status'] = true;
$feedbackdata = YNSIRFeedback::getRepareFeedbackData($_GET);
$arResult = array_merge($arResult, $feedbackdata);
if (isset($_GET['candidate_id']) && isset($_GET['job_order_id']) && isset($_GET['round_id'])) {
    $feedbackdata = YNSIRFeedback::getRepareFeedbackData($_GET);
    $arResult = array_merge($arResult, $feedbackdata);
    $arFilter = array(
        'CANDIDATE_ID' => $_GET['candidate_id'],
        'JOB_ORDER_ID' => $_GET['job_order_id'],
        'ROUND_ID' => $_GET['round_id'],
        'CREATED_BY' => $USER->GetID(),
    );
    $obRes = YNSIRFeedback::GetList(
        $arSort,
        $arFilter,
        false,
        false,
        $navListOptions
    );
    if ($rs = $obRes->Fetch()) {
        $arResult['DESCRIPTION'] = $rs['DESCRIPTION'];
    }

} else {
    $arResult['status'] = 'not_data';
}
$this->IncludeComponentTemplate($defaultTemplateName);
?>