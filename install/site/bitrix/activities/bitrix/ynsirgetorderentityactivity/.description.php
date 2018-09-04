<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('YNSIR_ACTIVITY_GET_DATA_ENTITY_NAME'),
	'DESCRIPTION' => GetMessage('YNSIR_ACTIVITY_GET_DATA_ENTITY_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'YNSIRGetOrderEntityActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'YNSIRecruitment',
		"OWN_NAME" => 'YNSIRecruitment',
	),
    "RETURN" => array(

        /* order job */
        "ID" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OJOB_ORDER_ID"),
            "TYPE" => "int"
        ),
        "TITLE" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OJOB_ORDER_TITLE"),
            "TYPE" => "string"
        ),
        "STATUS" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OJOB_ORDER_STATUS"),
            "TYPE" => "string"
        ),
        "OSupervisor" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OSUPERVISOR"),
            "TYPE" => "user"
        ),
        "ORecruiter" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_ORECRUITER"),
            "TYPE" => "user"
        ),
        "Owner" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OWNER"),
            "TYPE" => "user"
        ),
        "ODetailLink" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_ODETAIL_LINK"),
            "TYPE" => "string"
        ),
        "OIntegrationTime" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OINTEGRATION_TIME"),
            "TYPE" => "string"
        ),
        "ODateTimeFormat" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_ODATE_TIME_FORMAT"),
            "TYPE" => "string"
        ),
        "ORequesterID" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OUSER_REQUEST"),
            "TYPE" => "int"
        ),
        "ORequester" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OUSER_REQUESTER"),
            "TYPE" => "user"
        ),
        "OErrorCode" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OERROR_CODE"),
            "TYPE" => "string"
        ),
        "OProcessStatus" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OPROCESS_STATUS"),
            "TYPE" => "string"
        )
    ),
	'ADDITIONAL_RESULT' => array('EntityFields_')
);
?>
