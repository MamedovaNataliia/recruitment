<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
    "NAME" => GetMessage("BPRIORA_DESCR_NAME"),
    "DESCRIPTION" => GetMessage("BPRIORA_DESCR_DESCR"),
    "TYPE" => "activity",
    "CLASS" => "YnsirOnboardingActivity",
    'JSCLASS' => 'BizProcActivity',
    "CATEGORY" => array(
        "ID" => 'YNSIRecruitment',
        'OWN_ID' => 'YNSIRecruitment',
        'OWN_NAME' => GetMessage('BPRIORA_DESCR_TASKS')
    ),
    "RETURN" => array(
        "OComment" => array(
            "NAME" => GetMessage("YNSIT_LABLE_OCOMMENT"),
            "TYPE" => "string"
        ),
        "ODetailLink" => array(
            "NAME" => GetMessage("YNSIT_LABLE_ODETAIL_LINK"),
            "TYPE" => "string"
        ),
        "OIntegrationTime" => array(
            "NAME" => GetMessage("YNSIT_LABLE_OINTEGRATION_TIME"),
            "TYPE" => "string"
        ),
        "ODateTimeFormat" => array(
            "NAME" => GetMessage("YNSIT_LABLE_ODATE_TIME_FORMAT"),
            "TYPE" => "string"
        ),
        "ORequesterID" => array(
            "NAME" => GetMessage("YNSIT_LABLE_OUSER_REQUEST"),
            "TYPE" => "int"
        ),
        "ORequester" => array(
            "NAME" => GetMessage("YNSIT_LABLE_OUSER_REQUESTER"),
            "TYPE" => "user"
        ),
        "OErrorCode" => array(
            "NAME" => GetMessage("YNSIT_LABLE_OERROR_CODE"),
            "TYPE" => "string"
        ),
        "OProcessStatus" => array(
            "NAME" => GetMessage("YNSIT_LABLE_OPROCESS_STATUS"),
            "TYPE" => "string"
        )
    ),
	'ADDITIONAL_RESULT' => array('EntityFields_')
);
?>
