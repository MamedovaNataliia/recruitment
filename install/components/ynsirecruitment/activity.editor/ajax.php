<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('ynsirecruitment') || !CModule::IncludeModule('crm'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE_ACTIVITY' - save activity (CALL, MEETING)
 * 'SAVE_EMAIL'
 * 'SET_NOTIFY' - change notification settings
 * 'SET_PRIORITY'
 * 'COMPLETE' - mark activity as completed
 * 'DELETE' - delete activity
 * 'GET_ENTITY_COMMUNICATIONS' - get entity communications
 * 'GET_ACTIVITY_COMMUNICATIONS_PAGE'
 * 'GET_TASK'
 * 'SEARCH_COMMUNICATIONS'
 * 'GET_ACTIVITIES'
 * 'GET_WEBDAV_ELEMENT_INFO'
 * 'PREPARE_MAIL_TEMPLATE'
 */

global $DB, $APPLICATION;

$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
if(!function_exists('__YNSIRActivityEditorEndResonse'))
{
	function __YNSIRActivityEditorEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

function GetYNSIREntityCommunications($entityType, $entityID, $communicationType)
{
	if($entityType === YNSIR_CANDIDATE)
	{
		$candidate = YNSIRCandidate::getList(array(), array('ID'=>$entityID));
		$candidate = $candidate->Fetch();
		if(!$candidate)
		{
			return array('ERROR' => 'Invalid data');
		}

		// Prepare title
        $sFormatName = CSite::GetNameFormat(false);

        $title = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $candidate['FIRST_NAME'],
                "LAST_NAME" => $candidate['LAST_NAME'],
            )
        );
		$data = array(
			'ownerEntityType' => 'RECRUITMENT',
			'ownerEntityId' => $entityID,
			'entityType' => 'RECRUITMENT',
			'entityId' => $entityID,
			'entityTitle' => $title,
			'entityDescription' => '',
			'tabId' => 'main',
			'communications' => array()
		);

		// Try to load entity communications
		if(!YNSIRActivity::CheckReadPermission(YNSIROwnerType::ResolveID($entityType), $entityID))
		{
			return array('ERROR' => GetMessage('YNSIR_PERMISSION_DENIED'));
		}

		if($communicationType !== '')
		{
			$dbResFields = YNSIRCandidate::GetListMultiField(
				array('ID' => 'asc'),
				array('TYPE' => $communicationType, 'CANDIDATE_ID' => $candidate['ID'])
			);

			while($arField = $dbResFields->Fetch())
			{
				if(empty($arField['CONTENT']))
				{
					continue;
				}

				$comm = array('type' => $communicationType, 'value' => $arField['CONTENT']);
				$data['communications'][] = $comm;
			}
		}

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'recruitment',
						'title' => GetMessage('YNSIR_COMMUNICATION_TAB_CANDIDATE'), 'active' => true, 'items' => array($data))
				)
			)
		);
	}

	return array('ERROR' => 'Invalid data');
}

$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if(strlen($action) == 0)
{
	__YNSIRActivityEditorEndResonse(array('ERROR' => 'Invalid data!'));
}

if($action == 'SAVE_EMAIL')
{
	if (!CModule::IncludeModule('subscribe'))
	{
		__YNSIRActivityEditorEndResonse(array('ERROR' => 'Could not load module "subscribe"!'));
	}

	$siteID = !empty($_REQUEST['siteID']) ? $_REQUEST['siteID'] : SITE_ID;

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__YNSIRActivityEditorEndResonse(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;

	$ownerTypeName = isset($data['ownerType']) ? strtoupper(strval($data['ownerType'])) : '';
	if($ownerTypeName === '')
	{
		__YNSIRActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}
//
//	$ownerTypeID = YNSIROwnerType::ResolveID($ownerTypeName);
//	if(!YNSIROwnerType::IsDefined($ownerTypeID))
//	{
//		__YNSIRActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT SUPPORTED!'));
//	}

	$ownerID = isset($data['ownerID']) ? intval($data['ownerID']) : 0;
	if($ownerID <= 0)
	{
		__YNSIRActivityEditorEndResonse(array('ERROR'=>'OWNER ID IS NOT DEFINED!'));
	}

	$userID = $curUser->GetID();
	if($userID <= 0)
	{
		$userID = YNSIROwnerType::GetResponsibleID($ownerTypeID, $ownerID, false);
		if($userID <= 0)
		{
			__YNSIRActivityEditorEndResonse(array('ERROR' => GetMessage('YNSIR_ACTIVITY_RESPONSIBLE_NOT_FOUND')));
		}
	}

	$arErrors = array();

	if (CModule::includeModule('mail'))
	{
		$res = \Bitrix\Mail\MailboxTable::getList(array(
			'select' => array('*', 'LANG_CHARSET' => 'SITE.CULTURE.CHARSET'),
			'filter' => array('LID' => SITE_ID, 'ACTIVE' => 'Y', 'USER_ID' => array($userID, 0)),
			'order'  => array('USER_ID' => 'DESC', 'TIMESTAMP_X' => 'DESC'),
		));

		while ($mailbox = $res->fetch())
		{
			if (!$mailbox['USER_ID'] && $mailbox['SERVER_TYPE'] != 'imap')
				continue;

			if (!empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', $mailbox['OPTIONS']['flags']))
			{
				$userMailbox = $mailbox;

				$email = $mailbox['USER_ID'] > 0 ? $mailbox['LOGIN'] : $mailbox['NAME'];
				if (strpos($email, '@') > 0)
					$crmEmail = $email;

				break;
			}
		}
	}

	if (empty($crmEmail))
		$crmEmail = \CCrmMailHelper::extractEmail(\COption::getOptionString('ynsirecruitment', 'mail', ''));

	$from = isset($data['from']) ? trim(strval($data['from'])) : '';
	if($from === '')
	{
		if($crmEmail !== '')
		{
			$from = $crmEmail;
		}
		else
		{
			$arErrors[] = GetMessage('YNSIR_ACTIVITY_EMAIL_EMPTY_FROM_FIELD');
		}
	}
	else
	{
		$fromAddresses = explode(',', $from);
		foreach($fromAddresses as $fromAddress)
		{
			if(!check_email($fromAddress))
			{
				$arErrors[] = GetMessage('YNSIR_ACTIVITY_INVALID_EMAIL', array('#VALUE#' => $fromAddress));
			}
		}
	}
	$to = array();

	// Bindings & Communications -->
    $ownerTypeName = YNSIR_CANDIDATE;
	$arBindings = array(
		"{$ownerTypeName}_{$ownerID}" => array(
			'OWNER_TYPE_ID' => YNSIROwnerType::ResolveID(YNSIR_CANDIDATE),
			'OWNER_ID' => $ownerID
		)
	);
	$arComms = array();
	$commData = isset($data['communications']) ? $data['communications'] : array();
	foreach($commData as &$commDatum)
	{
		$commID = isset($commData['id']) ? intval($commData['id']) : 0;
		$commEntityType = YNSIR_CANDIDATE;
		$commEntityID = isset($commDatum['entityId']) ? intval($commDatum['entityId']) : 0;

		$commType = isset($commDatum['type']) ? strtoupper(strval($commDatum['type'])) : '';
		if($commType === '')
		{
			$commType = 'EMAIL';
		}
		$commValue = isset($commDatum['value']) ? strval($commDatum['value']) : '';

		if($commType === 'EMAIL' && $commValue !== '')
		{
			if(!check_email($commValue))
			{
				$arErrors[] = GetMessage('YNSIR_ACTIVITY_INVALID_EMAIL', array('#VALUE#' => $commValue));
				continue;
			}

			$to[] = strtolower(trim($commValue));
		}

		$arComms[] = array(
			'ID' => $commID,
			'TYPE' => $commType,
			'VALUE' => $commValue,
			'ENTITY_ID' => $commEntityID,
			'ENTITY_TYPE_ID' => YNSIROwnerType::ResolveID($commEntityType)
		);

		if($commEntityType !== '')
		{
			$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
			if(!isset($arBindings[$bindingKey]))
			{
				$arBindings[$bindingKey] = array(
					'OWNER_TYPE_ID' => YNSIROwnerType::ResolveID($commEntityType),
					'OWNER_ID' => $commEntityID
				);
			}
		}
	}
	unset($commDatum);
	// <-- Bindings & Communications

	if(empty($to))
	{
		__YNSIRActivityEditorEndResonse(array('ERROR' => GetMessage('YNSIR_ACTIVITY_EMAIL_EMPTY_TO_FIELD')));
	}
	elseif(!empty($arErrors))
	{
		__YNSIRActivityEditorEndResonse(array('ERROR' => $arErrors));
	}

	$subject = isset($data['subject']) ? strval($data['subject']) : '';
	$message = isset($data['message']) ? strval($data['message']) : '';

	if($message === '')
	{
		$messageHtml = '';
	}
	else
	{
		//Convert BBCODE to HTML
		$parser = new CTextParser();
		$parser->allow['SMILES'] = 'N';
		$messageHtml = $parser->convertText($message);
	}

	$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID);
	if($subject === '')
	{
		$subject = GetMessage(
			'YNSIR_EMAIL_ACTION_DEFAULT_SUBJECT',
			array('#DATE#'=> $now)
		);
	}

	$parentId = 0;
	if (isset($data['FORWARDED_ID']))
		$parentId = intval($data['FORWARDED_ID']);
	elseif (isset($data['REPLIED_ID']))
		$parentId = intval($data['REPLIED_ID']);

	$description = $message;
	$descriptionHtml = $messageHtml;
	//$description = preg_replace('/<br\s*[^>]*>/i', PHP_EOL, $message);
	//$description = preg_replace('/<(?:\/)?[a-z0-9]+[^>]*>/i', '', $description);

	$arFields = array(
		'OWNER_ID' => $ownerID,
		'OWNER_TYPE_ID' => YNSIROwnerType::ResolveID($ownerTypeName),
		'TYPE_ID' =>  YNSIRActivityType::Email,
		'SUBJECT' => $subject,
		'START_TIME' => $now,
		'END_TIME' => $now,
		'COMPLETED' => 'Y',
		'RESPONSIBLE_ID' => $userID,
		'PRIORITY' => YNSIRActivityPriority::Medium,
		'DESCRIPTION' => $description,
		'DESCRIPTION_TYPE' => CCrmContentType::BBCode,
		'DIRECTION' => YNSIRActivityDirection::Outgoing,
		'LOCATION' => '',
		'NOTIFY_TYPE' => YNSIRActivityNotifyType::None,
		'BINDINGS' => array_values($arBindings),
		'PARENT_ID' => $parentId,
	);
    if(isset($data['job_orderID']) && intval($data['job_orderID']) > 0) {
        $arFields['REFERENCE_ID'] = $data['job_orderID'];
        $arFields['REFERENCE_TYPE_ID'] = YNSIROwnerType::Order;
    }

	if(count($arFields['BINDINGS']) === 1)
	{
		// In single bindind mode override owner data
		$arBinding = $arFields['BINDINGS'][0];
		$arFields['OWNER_TYPE_ID'] = $arBinding['OWNER_TYPE_ID'];
		$arFields['OWNER_ID'] = $arBinding['OWNER_ID'];
	}

	$storageTypeID = isset($data['storageTypeID']) ? intval($data['storageTypeID']) : YNSIRActivityStorageType::Undefined;
	if($storageTypeID === YNSIRActivityStorageType::Undefined
		|| !YNSIRActivityStorageType::IsDefined($storageTypeID))
	{
		if($isNew)
		{
			$storageTypeID = YNSIRActivity::GetDefaultStorageTypeID();
		}
		else
		{
			$storageTypeID = YNSIRActivity::GetStorageTypeID($ID);
			if($storageTypeID === YNSIRActivityStorageType::Undefined)
			{
				$storageTypeID = YNSIRActivity::GetDefaultStorageTypeID();
			}
		}
	}

	$arFields['STORAGE_TYPE_ID'] = $storageTypeID;
	if($storageTypeID === YNSIRActivityStorageType::File)
	{
		$arUserFiles = isset($data['files']) && is_array($data['files']) ? $data['files'] : array();
		if(!empty($arUserFiles) || !$isNew)
		{
			$arPermittedFiles = array();
			$arPreviousFiles = array();
			if(!$isNew)
			{
				$arPreviousFields = $ID > 0 ? YNSIRActivity::GetByID($ID) : array();
				YNSIRActivity::PrepareStorageElementIDs($arPreviousFields);
				$arPreviousFiles = $arPreviousFiles['STORAGE_ELEMENT_IDS'];
				if(is_array($arPreviousFiles) && !empty($arPreviousFiles))
				{
					$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
				}
			}

			$forwardedID = isset($data['FORWARDED_ID']) ? intval($data['FORWARDED_ID']) : 0;
			if($forwardedID > 0)
			{
				$arForwardedFields = YNSIRActivity::GetByID($forwardedID);
				if($arForwardedFields)
				{
					YNSIRActivity::PrepareStorageElementIDs($arForwardedFields);
					$arForwardedFiles = $arForwardedFields['STORAGE_ELEMENT_IDS'];
					if(!empty($arForwardedFiles))
					{
						$arForwardedFiles = array_intersect($arUserFiles, $arForwardedFiles);
					}


					if(!empty($arForwardedFiles))
					{
						foreach($arForwardedFiles as $fileID)
						$arRawFile = CFile::MakeFileArray($fileID);
						if(is_array($arRawFile))
						{
							$fileID = intval(CFile::SaveFile($arRawFile, 'ynsirecruitment'));
							if($fileID > 0)
							{
								$arPermittedFiles[] = $fileID;
							}
						}
					}
				}
			}

			$uploadControlCID = isset($data['uploadControlCID']) ? strval($data['uploadControlCID']) : '';
			if($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
			{
				$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
				if(!empty($uploadedFiles))
				{
					$arPermittedFiles = array_merge(
						array_intersect($arUserFiles, $uploadedFiles),
						$arPermittedFiles
					);
				}
			}

			$arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
		}
	}
	elseif($storageTypeID === YNSIRActivityStorageType::WebDav || $storageTypeID === YNSIRActivityStorageType::Disk)
	{
		$fileKey = $storageTypeID === YNSIRActivityStorageType::Disk ? 'diskfiles' : 'webdavelements';
		$arFileIDs = isset($data[$fileKey]) && is_array($data[$fileKey]) ? $data[$fileKey] : array();
		if(!empty($arFileIDs) || !$isNew)
		{
			$arFields['STORAGE_ELEMENT_IDS'] = Bitrix\YNSIR\Integration\StorageManager::filterFiles($arFileIDs, $storageTypeID, $userID);
		}
	}

	if($isNew)
	{
		if(!($ID = YNSIRActivity::Add($arFields, false, false, array('REGISTER_SONET_EVENT' => true))))
		{
			__YNSIRActivityEditorEndResonse(array('ERROR' => YNSIRActivity::GetLastErrorMessage()));
		}
	}
	else
	{
		if(!YNSIRActivity::Update($ID, $arFields, false, false))
		{
			__YNSIRActivityEditorEndResonse(array('ERROR' => YNSIRActivity::GetLastErrorMessage()));
		}
	}

	$urn = YNSIRActivity::PrepareUrn($arFields);
	if($urn !== '')
	{
		YNSIRActivity::Update($ID, array('URN'=> $urn), false, false, array('REGISTER_SONET_EVENT' => true));
	}

	$messageId = sprintf(
		'<crm.activity.%s@%s>', $urn,
		defined('BX24_HOST_NAME') ? BX24_HOST_NAME : (
			defined('SITE_SERVER_NAME') && SITE_SERVER_NAME
				? SITE_SERVER_NAME : \COption::getOptionString('main', 'server_name', '')
		)
	);

	YNSIRActivity::SaveCommunications($ID, $arComms, $arFields, false, false);
	// Creating Email -->
	//Save user email in settings -->
	if($from !== CUserOptions::GetOption('ynsirecruitment', 'activity_email_addresser', ''))
	{
		CUserOptions::SetOption('ynsirecruitment', 'activity_email_addresser', $from);
	}

	//<-- Save user email in settings
	if(!empty($arErrors))
	{
		__YNSIRActivityEditorEndResonse(array('ERROR' => $arErrors));
	}

	// Try to resolve posting charset -->
	$postingCharset = '';
	$siteCharset = defined('LANG_CHARSET') ? LANG_CHARSET : (defined('SITE_CHARSET') ? SITE_CHARSET : 'windows-1251');
	$arSupportedCharset = explode(',', COption::GetOptionString('subscribe', 'posting_charset'));
	if(count($arSupportedCharset) === 0)
	{
		$postingCharset = $siteCharset;
	}
	else
	{
		foreach($arSupportedCharset as $curCharset)
		{
			if(strcasecmp($curCharset, $siteCharset) === 0)
			{
				$postingCharset = $curCharset;
				break;
			}
		}

		if($postingCharset === '')
		{
			$postingCharset = $arSupportedCharset[0];
		}
	}
	//<-- Try to resolve posting charset

	$postingData = array(
		'STATUS' => 'D',
		'FROM_FIELD' => $from,
		'TO_FIELD' => implode(',', empty($userMailbox) ? array_merge(array($from), $to) : $to),
		//'BCC_FIELD' => implode(',', $to),
		'SUBJECT' => $subject,
		'BODY_TYPE' => 'html',
		'BODY' => $messageHtml !== '' ? $messageHtml : GetMessage('YNSIR_EMAIL_ACTION_DEFAULT_DESCRIPTION'),
		'DIRECT_SEND' => 'Y',
		'SUBSCR_FORMAT' => 'html',
		'CHARSET' => $postingCharset
	);
	$posting = new CPosting();
	$postingID = $posting->Add($postingData);
	if($postingID === false)
	{
		$arErrors[] = GetMessage('YNSIR_ACTIVITY_COULD_NOT_CREATE_POSTING');
		$arErrors[] = $posting->LAST_ERROR;
	}
	else
	{
		// Attaching files -->
		$arRawFiles = isset($arFields['STORAGE_ELEMENT_IDS']) && !empty($arFields['STORAGE_ELEMENT_IDS'])
			? \Bitrix\YNSIR\Integration\StorageManager::makeFileArray($arFields['STORAGE_ELEMENT_IDS'], $storageTypeID)
			: array();


		foreach($arRawFiles as &$arRawFile)
		{
			if(isset($arRawFile['ORIGINAL_NAME']))
			{
				$arRawFile['name'] = $arRawFile['ORIGINAL_NAME'];
			}
			if(!$posting->SaveFile($postingID, $arRawFile))
			{
				$arErrors[] = GetMessage('YNSIR_ACTIVITY_COULD_NOT_SAVE_POSTING_FILE', array('#FILE_NAME#' => $arRawFile['ORIGINAL_NAME']));
				$arErrors[] = $posting->LAST_ERROR;
				break;
			}
		}
		unset($arRawFile);
		// <-- Attaching files
	}
	// <-- Creating Email


	if (!empty($userMailbox))
	{
		$attachments = array();
		foreach ($arRawFiles as $item)
		{
			$attachments[] = array(
				'ID'           => $item['external_id'],
				'NAME'         => $item['ORIGINAL_NAME'] ?: $item['name'],
				'PATH'         => $item['tmp_name'],
				'CONTENT_TYPE' => $item['type'],
			);
		}

		class_exists('Bitrix\Mail\Helper');

		$rcpt = '';
		foreach ($to as $item)
			$rcpt[] = \Bitrix\Mail\DummyMail::encodeHeaderFrom($item, SITE_CHARSET);
		$rcpt = join(', ', $rcpt);

		$outgoing = new \Bitrix\Mail\DummyMail(array(
			'CONTENT_TYPE' => 'html',
			'CHARSET'      => SITE_CHARSET,
			'HEADER'       => array(
				'From'       => $from,
				'To'         => $rcpt,
				'Subject'    => $subject,
				'Message-Id' => $messageId,
			),
			'BODY'         => $messageHtml ?: getMessage('YNSIR_EMAIL_ACTION_DEFAULT_DESCRIPTION'),
			'ATTACHMENT'   => $attachments
		));

		\Bitrix\Mail\Helper::addImapMessage($userMailbox, (string) $outgoing, $error);
	}


	// Sending Email -->
	CPosting::AutoSend($postingID,true);
	if($posting->ChangeStatus($postingID, 'P'))
	{
		$rsAgents = CAgent::GetList(
			array('ID'=>'DESC'),
			array(
				'MODULE_ID' => 'subscribe',
				'NAME' => 'CPosting::AutoSend('.$postingID.',%',
			)
		);

		if(!$rsAgents->Fetch())
		{
			CAgent::AddAgent('CPosting::AutoSend('.$postingID.',true);', 'subscribe', 'N', 0);
		}
	}
	// <-- Sending Email

	$userName = '';
	if($userID > 0)
	{
		$dbResUser = CUser::GetByID($userID);
		$userName = is_array(($user = $dbResUser->Fetch()))
			? CUser::FormatName(CSite::GetNameFormat(false), $user, true, false) : '';
	}

	$nowStr = ConvertTimeStamp(MakeTimeStamp($now), 'FULL', $siteID);

//	$candidate = SIRecruitmentCandidate::getList(array(), array('ID'=>$ownerID));
//	$candidate = $candidate->Fetch();
//	$arActivity = array(
//		'USER_ID' => $USER->GetID(),
//		'JOB_ORDER_ID' => $candidate['ID'],
//		'CANDIDATE_ID' => $candidate['ORDER_ID'],
//		'TYPE' => BX_REC_EVENT_EMAIL,
//		'CREATED_AT' => date('Y-m-d H:i:s'),
//		'DESCRIPTION' => $messageHtml !== '' ? $messageHtml : GetMessage('YNSIR_EMAIL_ACTION_DEFAULT_DESCRIPTION'),
//		'SOURCE' => $postingID
//	);

//	CRecruitmentActivity::Add($arActivity);

	$arFields['STORAGE_ELEMENT_IDS'] = array(109);
	YNSIRActivity::PrepareStorageElementIDs($arFields);
	YNSIRActivity::PrepareStorageElementInfo($arFields);
	$jsonFields = array(
		'ID' => $ID,
		'typeID' => 'Email',
		'ownerID' => $arFields['OWNER_ID'],
		'ownerType' => YNSIROwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
		'ownerTitle' => 'Replace with order title',
		'ownerUrl' => YNSIROwnerType::GetShowUrl($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'subject' => $subject,
		'description' => $description,
		'descriptionHtml' => $messageHtml,
		'location' => '',
		'start' => $nowStr,
		'end' => $nowStr,
		'deadline' => $nowStr,
		'completed' => true,
		'notifyType' => 'None',
		'notifyValue' => 0,
		'priority' => 'Medium',
		'responsibleName' => $userName,
		'responsibleUrl' =>
			CComponentEngine::MakePathFromTemplate(
				'/company/personal/user/#user_id#/',
				array('user_id' => $userID)
			),
		'storageTypeID' => $storageTypeID,
		'files' => isset($arFields['FILES']) ? $arFields['FILES'] : array(),
		'webdavelements' => isset($arFields['WEBDAV_ELEMENTS']) ? $arFields['WEBDAV_ELEMENTS'] : array(),
		'diskfiles' => isset($arFields['DISK_FILES']) ? $arFields['DISK_FILES'] : array(),
		'communications' => $commData
	);
	__YNSIRActivityEditorEndResonse(array('ACTIVITY' => $jsonFields));
}
elseif($action == 'PREPARE_MAIL_TEMPLATE')
{
	$templateID = isset($_POST['TEMPLATE_ID']) ? intval($_POST['TEMPLATE_ID']) : 0;
	$ownerTypeName = isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;
	$JobOrderID = isset($_POST['JOB_ORDER_ID']) ? intval($_POST['JOB_ORDER_ID']) : 0;

	if($templateID <= 0)
	{
		__YNSIRActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	$dbResult = YNSIRMailTemplate::GetList(
		array(),
		array('ID' => $templateID),
		false,
		false,
		array('OWNER_ID', 'ENTITY_TYPE_ID', 'SCOPE', 'EMAIL_FROM', 'SUBJECT', 'BODY')
	);
	$fields = $dbResult->Fetch();
	if(!is_array($fields))
	{
		__YNSIRActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	$templateOwnerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
	$templateScope = isset($fields['SCOPE']) ? intval($fields['SCOPE']) : YNSIRMailTemplateScope::Undefined;

	if($templateScope !== YNSIRMailTemplateScope::Common
		&& $templateOwnerID !== intval($curUser->GetID()))
	{
		__YNSIRActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	$body = isset($fields['BODY']) ? $fields['BODY'] : '';
	if($body !== '')
	{
		$contentTypeID = isset($_POST['CONTENT_TYPE']) ? YNSIRContentType::ResolveTypeID($_POST['CONTENT_TYPE']) : CYNSIRContentType::Undefined;
		if(!YNSIRContentType::IsDefined($contentTypeID))
		{
			$contentTypeID = CYNSIRContentType::PlainText;
		}
		$body = YNSIRTemplateManager::PrepareTemplate($body, YNSIROwnerType::ResolveID($ownerTypeName), $ownerID,$JobOrderID, $contentTypeID);
	}

	__YNSIRActivityEditorEndResonse(
		array(
			'DATA' => array(
				'ID' => $templateID,
				'OWNER_TYPE'=> $ownerTypeName,
				'OWNER_ID' => $ownerID,
				'FROM' => isset($fields['EMAIL_FROM']) ? $fields['EMAIL_FROM'] : '',
				'SUBJECT' => isset($fields['SUBJECT']) ? $fields['SUBJECT'] : '',
				'BODY' => $body
			)
		)
	);
}
elseif($action == 'GET_ENTITY_COMMUNICATIONS')
{
	$entityType = isset($_POST['ENTITY_TYPE']) ? strtoupper(strval($_POST['ENTITY_TYPE'])) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
	$communicationType = isset($_POST['COMMUNICATION_TYPE']) ? strval($_POST['COMMUNICATION_TYPE']) : '';

	if($entityType === '' || $entityID <= 0)
	{
		__YNSIRActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	__YNSIRActivityEditorEndResonse(GetYNSIREntityCommunications($entityType, $entityID, $communicationType));
}
elseif($action == 'GET_ACTIVITY_VIEW_DATA')
{
	$result = array();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$comm = isset($params['ACTIVITY_COMMUNICATIONS']) ? $params['ACTIVITY_COMMUNICATIONS'] : null;
	if(is_array($comm))
	{
		$ID = isset($comm['ID']) ? (int)$comm['ID'] : 0;
		$result['ACTIVITY_COMMUNICATIONS'] = GetYNSIRActivityCommunications($ID);
	}

	$commPage = isset($params['ACTIVITY_COMMUNICATIONS_PAGE']) ? $params['ACTIVITY_COMMUNICATIONS_PAGE'] : null;
	if(is_array($commPage))
	{
		$ID = isset($commPage['ID']) ? (int)$commPage['ID'] : 0;
		$pageSize = isset($commPage['PAGE_SIZE']) ? (int)$commPage['PAGE_SIZE'] : 20;
		$pageNumber = isset($commPage['PAGE_NUMBER']) ? (int)$commPage['PAGE_NUMBER'] : 1;
		$result['ACTIVITY_COMMUNICATIONS_PAGE'] = GetYNSIRActivityCommunicationsPage($ID, $pageSize, $pageNumber);
	}

	$entityComm = isset($params['ENTITY_COMMUNICATIONS']) ? $params['ENTITY_COMMUNICATIONS'] : null;
	if(is_array($entityComm))
	{
		$entityType = isset($entityComm['ENTITY_TYPE']) ? strtoupper($entityComm['ENTITY_TYPE']) : '';
		$entityID = isset($entityComm['ENTITY_ID']) ? (int)$entityComm['ENTITY_ID'] : 0;
		$communicationType = isset($entityComm['COMMUNICATION_TYPE']) ? $entityComm['COMMUNICATION_TYPE'] : '';

		if($entityType === '' || $entityID <= 0)
		{
			$result['ENTITY_COMMUNICATIONS'] = array('ERROR' => 'Invalid data');
		}
		else
		{
			$result['ENTITY_COMMUNICATIONS'] = GetYNSIREntityCommunications($entityType, $entityID, $communicationType);
		}
	}

	__YNSIRActivityEditorEndResonse($result);
}
elseif($action == 'SEARCH_COMMUNICATIONS')
{
    $entityType = isset($_POST['ENTITY_TYPE']) ? strtoupper(strval($_POST['ENTITY_TYPE'])) : '';
    $entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
    $communicationType = isset($_POST['COMMUNICATION_TYPE']) ? strval($_POST['COMMUNICATION_TYPE']) : '';
    $needle = isset($_POST['NEEDLE']) ? strval($_POST['NEEDLE']) : '';
    $arFilter = array (
        'LOGIC' => 'OR',
        '%FIRST_NAME' => $needle,
        '%LAST_NAME' => $needle,
        '%EMAIL' => $needle,
    );
    $arNavParams = array (
        'nPageSize' => 20,
        'bShowAll' => false,
        'nTopCount' => 5,
    );
    $dbResFields = YNSIRCandidate::GetListCandidateForMultiField(
        $arSort,
        $arFilter,
        $arNavParams,
        $navListOptions, $arSelect,false);
    while($result = $dbResFields->Fetch())
    {
        $key = $result['ID'];
        if(!isset($data[$key]))
        {
            $sFormatName = CSite::GetNameFormat(false);

            $title = CUser::FormatName(
                $sFormatName,
                array(
                    "NAME" => $result['FIRST_NAME'],
                    "LAST_NAME" => $result['LAST_NAME'],
                )
            );
            $data[$key] = array(
                'ownerEntityType' => 'RECRUITMENT',
                'ownerEntityId' => $entityType,
                'entityType' => $entityType,
                'entityId' => $entityID,
                'entityTitle' => $title,
                'entityDescription' => '',
                'tabId' => 'main',
            );
        }

        if($result['EMAIL'] !== '' && $result['EMAIL'] !== '')
        {
            $comm = array(
                'type' => $communicationType,
                'value' => $result['EMAIL']
            );

            $data[$key]['communications'][] = $comm;
        }
    }
    unset($result);

    __YNSIRActivityEditorEndResonse(array('DATA' => array('ITEMS' => array_values($data))));
}
elseif($action == 'GET_ACTIVITY')
{
    $ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;

    $arFields = CAllYNSIRActivity::GetByID($ID);
    if(!is_array($arFields))
    {
        __YNSIRActivityEditorEndResonse(array('ERROR' => 'NOT FOUND'));
    }

    $commData = array();
    $communications = CAllYNSIRActivity::GetCommunications($ID);
    foreach($communications as &$arComm)
    {
        CAllYNSIRActivity::PrepareCommunicationInfo($arComm);
        $commData[] = array(
            'type' => $arComm['TYPE'],
            'value' => $arComm['VALUE'],
            'entityId' => $arComm['ENTITY_ID'],
            'entityType' => YNSIROwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
            'entityTitle' => $arComm['TITLE'],
        );
    }
    unset($arComm);

    $storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
        ? intval($arFields['STORAGE_TYPE_ID']) : CAllYNSIRActivityStorageType::Undefined;

    CAllYNSIRActivity::PrepareStorageElementIDs($arFields);
    CAllYNSIRActivity::PrepareStorageElementInfo($arFields);

    __YNSIRActivityEditorEndResonse(
        array(
            'ACTIVITY' => array(
                'ID' => $ID,
                'typeID' => $arFields['TYPE_ID'],
                'associatedEntityID' => isset($arFields['ASSOCIATED_ENTITY_ID']) ? $arFields['ASSOCIATED_ENTITY_ID'] : '0',
                'ownerID' => $arFields['OWNER_ID'],
                'ownerType' => YNSIROwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
                'ownerTitle' => YNSIROwnerType::GetCaption($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
                'ownerUrl' => YNSIROwnerType::GetShowUrl($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
                'subject' => $arFields['SUBJECT'],
                'description' => $arFields['DESCRIPTION'],
                'location' => $arFields['LOCATION'],
                'direction' => intval($arFields['DIRECTION']),
                'start' => $arFields['START_TIME'],
                'end' => $arFields['END_TIME'],
                'completed' => isset($arFields['COMPLETED']) && $arFields['COMPLETED'] === 'Y',
                'notifyType' => intval($arFields['NOTIFY_TYPE']),
                'notifyValue' => intval($arFields['NOTIFY_VALUE']),
                'priority' => intval($arFields['PRIORITY']),
                'responsibleName' => CCrmViewHelper::GetFormattedUserName(
                    isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0
                ),
                'storageTypeID' => $storageTypeID,
                'files' => isset($arFields['FILES']) ? $arFields['FILES'] : array(),
                'webdavelements' => isset($arFields['WEBDAV_ELEMENTS']) ? $arFields['WEBDAV_ELEMENTS'] : array(),
                'diskfiles' => isset($arFields['DISK_FILES']) ? $arFields['DISK_FILES'] : array(),
                'communications' => $commData
            )
        )
    );
}
elseif($action == 'COMPLETE')
{
    $ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

    if($ID <= 0)
    {
        __YNSIRActivityEditorEndResonse(array('ERROR' => 'Invalid data!'));
    }

    $arActivity = YNSIRActivity::GetByID($ID, false);
    if(!$arActivity)
    {
        __YNSIRActivityEditorEndResonse(array('ERROR' => 'Activity not found!'));
    }

    $provider = YNSIRActivity::GetActivityProvider($arActivity);
    if(!$provider)
    {
        __YNSIRActivityEditorEndResonse(array('ERROR' => 'Provider not found!'));
    }

    $ownerTypeID = YNSIROwnerType::ResolveID(isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '');
    $ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

    if(!YNSIROwnerType::IsDefined($ownerTypeID) || $ownerID >= 0 )
    {
        $ownerTypeID = isset($arActivity['OWNER_TYPE_ID']) ? intval($arActivity['OWNER_TYPE_ID']) : YNSIROwnerType::Undefined;
        $ownerID = isset($arActivity['OWNER_ID']) ? intval($arActivity['OWNER_ID']) : 0;
    }

    if($provider::checkOwner() && !YNSIROwnerType::IsDefined($ownerTypeID))
    {
        __YNSIRActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
    }

    if($provider::checkOwner() && $ownerID <= 0)
    {
        __YNSIRActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
    }

    $userPermissions = CCrmPerms::GetCurrentUserPermissions();
    if($provider::checkOwner() && !YNSIRActivity::CheckCompletePermission($ownerTypeID, $ownerID, $userPermissions, array('FIELDS' => $arActivity)))
    {
        __YNSIRActivityEditorEndResonse(array('ERROR' => GetMessage('YNSIR_PERMISSION_DENIED')));
    }


    $completed = (isset($_POST['COMPLETED']) ? intval($_POST['COMPLETED']) : 0) > 0;

    if(YNSIRActivity::Complete($ID, $completed, array('REGISTER_SONET_EVENT' => true)))
    {
        __YNSIRActivityEditorEndResonse(array('ITEM_ID'=> $ID, 'COMPLETED'=> $completed));
    }
    else
    {
        $errorMsg = YNSIRActivity::GetLastErrorMessage();
        if(!isset($errorMsg[0]))
        {
            $errorMsg = "Could not complete activity ('$ID')!";
        }

        __YNSIRActivityEditorEndResonse(array('ERROR' => $errorMsg));
    }
}
elseif($action == 'DELETE')
{
    $ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

    if($ID <= 0)
    {
        __YNSIRActivityEditorEndResonse(array('ERROR' => 'Invalid parameters!'));
    }

    $arActivity = YNSIRActivity::GetByID($ID);
    if(!$arActivity)
    {
        __YNSIRActivityEditorEndResonse(array('ERROR' => 'Activity not found!'));
    }

    $provider = YNSIRActivity::GetActivityProvider($arActivity);
    if(!$provider)
    {
        __YNSIRActivityEditorEndResonse(array('ERROR' => 'Provider not found!'));
    }

    $ownerTypeName = isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '';
    if($provider::checkOwner() && !isset($ownerTypeName[0]))
    {
        __YNSIRActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
    }

    $ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;
    if($provider::checkOwner() && $ownerID <= 0)
    {
        __YNSIRActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
    }

//    if($provider::checkOwner() && !YNSIRActivity::CheckUpdatePermission(CCrmOwnerType::ResolveID($ownerTypeName), $ownerID))
//    {
//        __YNSIRActivityEditorEndResonse(array('ERROR' => GetMessage('YNSIR_PERMISSION_DENIED')));
//    }

    if(YNSIRActivity::Delete($ID))
    {
        __YNSIRActivityEditorEndResonse(array('DELETED_ITEM_ID'=> $ID));
    }
    else
    {
        __YNSIRActivityEditorEndResonse(array('ERROR'=> "Could not delete activity ('$ID')!"));
    }
}
else
{
	__YNSIRActivityEditorEndResonse(array('ERROR' => 'Unknown action'));
}
?>
