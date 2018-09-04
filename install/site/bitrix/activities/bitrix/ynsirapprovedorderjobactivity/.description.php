<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arActivityDescription = array(
	"NAME" => GetMessage("BPRIORA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPRIORA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "YnsirApprovedOrderJobActivity",
	"JSCLASS" => "YnsirApprovedOrderJobActivity",
	"CATEGORY" => array(
		"ID" => 'YNSIRecruitment',
		'OWN_ID' => 'YNSIRecruitment',
		'OWN_NAME' => GetMessage('BPRIORA_DESCR_TASKS')
	),
	"RETURN" => array(

		/* order job */
		"OOrderId" => array(
			"NAME" => GetMessage("YNSIT_LABLE_OJOB_ORDER_ID"),
			"TYPE" => "int"
		),
		"OTitle" => array(
			"NAME" => GetMessage("YNSIT_LABLE_OTITLE"),
			"TYPE" => "string"
		),
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
//		"OUserIdRequest" => array(
//			"NAME" => GetMessage("YNSIT_LABLE_OUSER_REQUEST"),
//			"TYPE" => "string"
//		),
        "OLastApprover" => array(
            "NAME" => GetMessage("YNSIT_LABLE_OLAST_APPROVER"),
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
);
?>