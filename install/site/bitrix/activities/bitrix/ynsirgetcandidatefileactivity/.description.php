<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('YNSIR_ACTIVITY_GET_DATA_ENTITY_NAME'),
	'DESCRIPTION' => GetMessage('YNSIR_ACTIVITY_GET_DATA_ENTITY_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'YNSIRGetCandidateFileActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'YNSIRecruitment',
		"OWN_NAME" => 'YNSIRecruitment',
	),
    "RETURN" => array(

        /* order job */
        "ID" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OCANDIDATE_ID"),
            "TYPE" => "int"
        ),
        "OFullName" => array(
            "NAME" => GetMessage("YNSIR_ACTIVITY_LABEL_OFULL_NAME"),
            "TYPE" => "string"
        ),
        'DOB' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_DOB'),
            "TYPE" => "string",
        ),
        'GENDER' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_GENDER'),
            "TYPE" => "string",
        ),
        'WEBSITE' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_WEBSITE'),
            "TYPE" => "string",
        ),
        'EXPERIENCE' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_EXPERIENCE'),
            "TYPE" => "string",
        ),
        'CURRENT_EMPLOYER' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_CURRENT_EMPLOYER'),
            "TYPE" => "string",
        ),
        'CANDIDATE_STATUS' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_CANDIDATE_STATUS'),
            "TYPE" => "string",
        ),
        'EXPECTED_SALARY' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_EXPECTED_SALARY'),
            "TYPE" => "string",
        ),
        'CURRENT_SALARY' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_CURRENT_SALARY'),
            "TYPE" => "string",
        ),
        'SKILL_SET' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_SKILL_SET'),
            "TYPE" => "string",
        ),
        'ADDITIONAL_INFO' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_ADDITIONAL_INFO'),
            "TYPE" => "string",
        ),
        'SKYPE_ID' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_SKYPE_ID'),
            "TYPE" => "string",
        ),
        'WORK_POSITION' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_WORK_POSITION'),
            "TYPE" => "string",
        ),
        'CURRENT_JOB_TITLE' => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_CURRENT_JOB_TITLE'),
            "TYPE" => "string",
        ),
        "OFileUrl" => array(
            "NAME" => GetMessage('YNSIR_ACTIVITY_DESCR_DOWNLOAD_URL'),
            "TYPE" => "string",
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
