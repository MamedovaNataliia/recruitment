<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arActivityDescription = array(
    "NAME" => GetMessage("BPRIOCF__DESCR_NAME"),
    "DESCRIPTION" => GetMessage("BPRIOCF__DESCR_DESCR"),
    "TYPE" => "activity",
    "CLASS" => "YnsirgetConfigActivity",
    'JSCLASS' => 'BizProcActivity',
    "CATEGORY" => array(
        "ID" => 'YNSIRecruitment',
        'OWN_ID' => 'YNSIRecruitment',
        'OWN_NAME' => GetMessage('BPRIOCF__DESCR_TASKS')
    ),
    "RETURN" => array(
        "OHMUser" => array(
            "NAME" => GetMessage("YNSIT_LABLE_OHMUSER"),
            'TYPE' => 'int',
            'Multiple' => true,
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
