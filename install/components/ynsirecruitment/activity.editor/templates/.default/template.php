<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/tools/clock.php');

global $APPLICATION, $USER;

$APPLICATION->SetAdditionalCSS('/bitrix/js/ynsirecruitment/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

$jsCoreInit = array('date', 'popup', 'ajax', 'ynsir_activity_planner', 'crm_visit_tracker');

if($arResult['ENABLE_DISK'])
{
	$jsCoreInit[] = 'uploader';
	$jsCoreInit[] = 'file_dialog';
}
CJSCore::Init($jsCoreInit);
//
//CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/activity.js');
//CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/crm.js');
//CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/communication_search.js');
//CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/common.js');
//
//if($arResult['ENABLE_WEBDAV'])
//{
//	$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
//	$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav.user.field/templates/.default/style.css');
//	$APPLICATION->SetAdditionalCSS('/bitrix/js/webdav/css/file_dialog.css');
//
//	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/core/core_dd.js');
//	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/file_upload_agent.js');
//	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/webdav/file_dialog.js');
//	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/webdav_uploader.js');
//}
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/activity.js');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/communication_search.js');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/ynsirecruitment/common.js');

if($arResult['ENABLE_DISK'])
{
    CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/disk_uploader.js');
    $APPLICATION->SetAdditionalCSS('/bitrix/js/disk/css/legacy_uf_common.css');
}
if($arResult['ENABLE_WEBDAV'])
{
    $APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
    $APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav.user.field/templates/.default/style.css');
    $APPLICATION->SetAdditionalCSS('/bitrix/js/webdav/css/file_dialog.css');

    CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/core/core_dd.js');
    CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/file_upload_agent.js');
    CCrmComponentHelper::RegisterScriptLink('/bitrix/js/webdav/file_dialog.js');
    CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/webdav_uploader.js');
}


$prefixUpper = strtoupper($arResult['PREFIX']);
$prefixLower = strtolower($arResult['PREFIX']);

$containerID = $arResult['CONTAINER_ID'];
$hasOwnContainer = false;
if($containerID === '')
{
	$containerID = $prefixLower.'_container';
	$hasOwnContainer = true;
}

$editorID = $arResult['EDITOR_ID'];
$type = $arResult['EDITOR_TYPE'];
if($type === '')
{
	$type = 'MIXED';
}

$toolbarID = $arResult['TOOLBAR_ID'];
$hasOwnToolbar = false;
$enableToolbar = !$arResult['READ_ONLY'] && $arResult['ENABLE_TOOLBAR'];
if($enableToolbar && $toolbarID === '')
{
	$toolbarID = $editorID.'_toolbar';
	$hasOwnToolbar = true;
}

$toolbarID = strtolower($toolbarID);

$userFullName = '';
$curUser = CCrmSecurityHelper::GetCurrentUser();

$mailTemplateData = array();
if($arResult['OWNER_TYPE_ID'] !== YNSIROwnerType::Undefined)
{

	 $mailTemplateResult = YNSIRMailTemplate::GetList(
	 	array('SORT' => 'ASC', 'TITLE'=> 'ASC'),
	 	array(
	 		'LOGIC' => 'OR',
	 		'__INNER_FILTER_PERSONAL' => array(
	 			'LOGIC' => 'AND',
	 			'OWNER_ID' => $curUser->GetID(),
//	 			'ENTITY_TYPE_ID' => $arResult['OWNER_TYPE_ID'],
	 			'SCOPE' => YNSIRMailTemplateScope::Personal,
	 			'IS_ACTIVE' => 'Y'
	 		),
	 		'__INNER_FILTER_COMMON' => array(
	 			'LOGIC' => 'AND',
//	 			'ENTITY_TYPE_ID' => $arResult['OWNER_TYPE_ID'],
	 			'SCOPE' => YNSIRMailTemplateScope::Common,
	 			'IS_ACTIVE' => 'Y'
	 		)
	 	),
	 	false,
	 	false,
	 	array('TITLE', 'SCOPE', 'ENTITY_TYPE_ID')
	 );
//	$mailTemplateResult = YNSIRMailTemplate::GetList(
//		array('SORT' => 'ASC', 'TITLE'=> 'ASC'),
//		array(
//			 'OWNER_ID' => $curUser->GetID(),
//			 'ENTITY_TYPE_ID' => $arResult['OWNER_TYPE_ID'],
//			 'SCOPE' => YNSIRMailTemplateScope::Personal,
//			 'IS_ACTIVE' => 'Y'
//		),
//		false,
//		false,
//		array('ID', 'TITLE', 'SCOPE', 'ENTITY_TYPE_ID')
//	);

	while($mailTemplateFields = $mailTemplateResult->Fetch())
	{
		$mailTemplateData[] = array(
			'id' => $mailTemplateFields['ID'],
			'title' => $mailTemplateFields['TITLE'],
			'scope' => $mailTemplateFields['SCOPE'],
			'entityType' => $arResult['OWNER_TYPE']
		);
	}
}

$editorCfg = array(
	'type' => $type,
	'serviceUrl' => '/bitrix/components/ynsirecruitment/activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'enableUI' => $arResult['ENABLE_UI'],
	'enableToolbar' => $enableToolbar,
	'toolbarID' => $toolbarID,
	'readOnly' => $arResult['READ_ONLY'],
	'enableTaskTracing' => $arResult['ENABLE_TASK_TRACING'],
	'enableTasks' => $arResult['ENABLE_TASK_ADD'],
	'enableCalendarEvents' => $arResult['ENABLE_CALENDAR_EVENT_ADD'],
	'enableEmails' => $arResult['ENABLE_EMAIL_ADD'],
	'ownerType' => $arResult['OWNER_TYPE'],
	'ownerID' => $arResult['OWNER_ID'],
	'ownerTitle' => YNSIROwnerType::GetCaption($arResult['OWNER_TYPE_ID'], $arResult['OWNER_ID']),
	'ownerUrl' => YNSIROwnerType::GetShowUrl($arResult['OWNER_TYPE_ID'], $arResult['OWNER_ID']),
	'prefix' => $arResult['PREFIX'],
	'containerID' => $containerID,
	'uploadID' => $prefixLower.'_upload_container',
	'uploadControlID' => $prefixLower.'_activity_uploader',
	'uploadInputID' => $prefixLower.'_activity_saved_file',
	'callClockID' => $prefixLower.'_call_clock_container',
	'callClockInputID' => $prefixLower.'_call_time',
	'meetingClockID' => $prefixLower.'_meeting_clock_container',
	'meetingClockInputID' => $prefixLower.'_meeting_time',
	'emailLheContainerID' => $prefixLower.'_email_lhe_container',
	'emailLheID' => $prefixLower.'_email_editor',
	'emailLheJsName' => $prefixLower.'_email_editor',
	'emailUploadContainerID' => $prefixLower.'_email_upload_container',
	'emailUploadControlID' => $prefixLower.'_activity_email_uploader',
	'emailUploadInputID' => $prefixLower.'_activity_email_saved_file',
	'userID' => $curUser->GetID(),
	'userFullName'=> $userFullName,
	'userEmail' => $curUser->getEmail(),
	'userEmail2' => $arResult['USER_YNSIR_EMAIL'],
	'crmEmail' => $arResult['SHARED_YNSIR_EMAIL'],
	'lastUsedEmail' => CUserOptions::GetOption('crm', 'activity_email_addresser', ''),
	//'lastUsedMailTemplateID' => CYNSIRMailTemplate::GetLastUsedTemplateID($arResult['OWNER_TYPE_ID'], $curUser->GetID()),
	'lastUsedMailTemplateID' => 0,
	'serverTime' => time() + CTimeZone::GetOffset(),
	'imagePath' => $this->GetFolder().'/images/',
	'defaultStorageTypeId' => $arResult['STORAGE_TYPE_ID'],
	'enableDisk' => $arResult['ENABLE_DISK'],
	'enableWebDav' => $arResult['ENABLE_WEBDAV'],
	'webDavSelectUrl' => $arResult['WEBDAV_SELECT_URL'],
	'webDavUploadUrl' => $arResult['WEBDAV_UPLOAD_URL'],
	'webDavShowUrl' => $arResult['WEBDAV_SHOW_URL'],
	'buttonID' => $arResult['BUTTON_ID'],
	'serviceContainerID' => $prefixLower.'_service_container',
	'userSearchJsName' => $prefixLower.'_USER_SEARCH',
	'ownershipSelectorData' => array(
		'items' => CCrmEntitySelectorHelper::PreparePopupItems(
			'DEAL',
			false,
			$arResult['NAME_TEMPLATE']
		),
		'messages' => CCrmEntitySelectorHelper::PrepareCommonMessages()
	),
	'callToFormat' => CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix),
	'mailTemplateData' => $mailTemplateData,
	'disableStorageEdit' => $arResult['DISABLE_STORAGE_EDIT'],
	'addEventUrl' => $arResult['CREATE_EVENT_URL'],
	'formId' => $arResult['FORM_ID'],
	'eventTabId' => $arResult['EVENT_VIEW_TAB_ID']
);

if($enableToolbar && $hasOwnToolbar):
	if($hasOwnContainer):
		?><div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-view-actions-wrapper"><?
	endif;
	?><div class="crm-view-message" style="<?= isset($arResult['EDITOR_ITEMS']) && count($arResult['EDITOR_ITEMS']) > 0 ? 'display: none;' : '' ?>"><?=htmlspecialcharsbx(GetMessage('YNSIR_ACTIVITY_EDITOR_NO_ITEMS'))?></div><?

	$toolbarButtons = array();
	if($editorCfg['enableTasks'])
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('YNSIR_ACTIVITY_EDITOR_ADD_TASK_SHORT'),
			'TITLE' => GetMessage('YNSIR_ACTIVITY_EDITOR_ADD_TASK'),
			'ICON' => 'btn-new crm-activity-command-add-task',
		);
	}

	if($editorCfg['enableCalendarEvents'])
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('YNSIR_ACTIVITY_EDITOR_ADD_CALL_SHORT'),
			'TITLE' => GetMessage('YNSIR_ACTIVITY_EDITOR_ADD_CALL'),
			'ICON' => 'btn-new crm-activity-command-add-call',
		);

		$toolbarButtons[] = array(
			'TEXT' => GetMessage('YNSIR_ACTIVITY_EDITOR_ADD_MEETING_SHORT'),
			'TITLE' => GetMessage('YNSIR_ACTIVITY_EDITOR_ADD_MEETING'),
			'ICON' => 'btn-new crm-activity-command-add-meeting',
		);
	}

	if($editorCfg['enableEmails'])
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('YNSIR_ACTIVITY_EDITOR_ADD_EMAIL_SHORT'),
			'TITLE' => GetMessage('YNSIR_ACTIVITY_EDITOR_ADD_EMAIL'),
			'ICON' => 'btn-new crm-activity-command-add-email',
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => $toolbarID,
			'BUTTONS' => $toolbarButtons
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
	if($hasOwnContainer):
		?></div><?
	endif;
endif;
?>
<?if(!$arResult['ENABLE_WEBDAV'] && !$arResult['ENABLE_DISK']):
?><!--Hidden container is used in dialogs-->
<div id="<?= $editorCfg['uploadID'] ?>" style="display:none;"><?
	$APPLICATION->IncludeComponent(
		'bitrix:main.file.input',
		'',
		array(
			'MODULE_ID' => 'crm',
			'MAX_FILE_SIZE' => 20971520,
			'ALLOW_UPLOAD' => 'A',
			'CONTROL_ID' => $editorCfg['uploadControlID'],
			'INPUT_NAME' => $editorCfg['uploadInputID'],
			'INPUT_NAME_UNSAVED' => $prefixLower.'_activity_new_file'
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
?></div>
<?endif;?>
<!--Hidden container is used in dialogs-->
<div id="<?= $editorCfg['callClockID'] ?>" style="display:none;">
<script type="text/javascript">
	(function()
		{
			var id = "bxClock_" + "<?=$editorCfg['callClockInputID']?>";
			if(window[id])
			{
				delete window[id];
			}
		}
	)();
</script>
<?CClock::Show(
		array(
			'view' => 'label',
			'inputId' => $editorCfg['callClockInputID'],
			'inputTitle' => GetMessage('YNSIR_ACTION_SET_TIME'),
			'zIndex' => 1500
		)
	);
?></div>
<!--Hidden container is used in dialogs-->
<div id="<?= $editorCfg['meetingClockID'] ?>" style="display:none;">
<script type="text/javascript">
	(function()
		{
			var id = "bxClock_" + "<?=$editorCfg['meetingClockInputID']?>";
			if(window[id])
			{
				delete window[id];
			}
		}
	)();
</script>
<?CClock::Show(
		array(
			'view' => 'label',
			'inputId' => $editorCfg['meetingClockInputID'],
			'inputTitle' => GetMessage('YNSIR_ACTION_SET_TIME'),
			'zIndex' => 1500
		)
	);
?></div>
<!--Hidden container is used in dialogs-->
<div id="<?=$editorCfg['emailLheContainerID'] ?>" style="display:none;">
    <?
    $emailEditor = new CHTMLEditor;
    $emailEditor->show(array(
        'name'                => $editorCfg['emailLheJsName'],
        'id'                  => $editorCfg['emailLheJsName'],
        'siteId'              => SITE_ID,
        'width'               => 606,
        'minBodyWidth'        => 606,
        'normalBodyWidth'     => 606,
        'height'              => 198,
        'minBodyHeight'       => 198,
        'showTaskbars'        => false,
        'showNodeNavi'        => false,
        'autoResize'          => true,
        'autoResizeOffset'    => 40,
        'bbCode'              => true,
        'saveOnBlur'          => false,
        'bAllowPhp'           => false,
        'limitPhpAccess'      => false,
        'setFocusAfterShow'   => false,
        'askBeforeUnloadPage' => true,
        'controlsMap'         => array(
            array('id' => 'Bold',  'compact' => true, 'sort' => 10),
            array('id' => 'Italic',  'compact' => true, 'sort' => 20),
            array('id' => 'Underline',  'compact' => true, 'sort' => 30),
            array('id' => 'Strikeout',  'compact' => true, 'sort' => 40),
            array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 50),
            array('id' => 'Color',  'compact' => true, 'sort' => 60),
            array('id' => 'FontSelector',  'compact' => false, 'sort' => 70),
            array('id' => 'FontSize',  'compact' => false, 'sort' => 80),
            array('separator' => true, 'compact' => false, 'sort' => 90),
            array('id' => 'OrderedList',  'compact' => true, 'sort' => 100),
            array('id' => 'UnorderedList',  'compact' => true, 'sort' => 110),
            array('id' => 'AlignList', 'compact' => false, 'sort' => 120),
            array('separator' => true, 'compact' => false, 'sort' => 130),
            array('id' => 'InsertLink',  'compact' => true, 'sort' => 140),
            array('id' => 'InsertImage',  'compact' => false, 'sort' => 150),
            array('id' => 'InsertTable',  'compact' => false, 'sort' => 170),
            array('id' => 'Code',  'compact' => true, 'sort' => 180),
            array('id' => 'Quote',  'compact' => true, 'sort' => 190),
            array('separator' => true, 'compact' => false, 'sort' => 200),
            array('id' => 'Fullscreen',  'compact' => false, 'sort' => 210),
            array('id' => 'BbCode',  'compact' => true, 'sort' => 220),
            array('id' => 'More',  'compact' => true, 'sort' => 400)
        ),
    ));
    ?></div>
<?if(!$arResult['ENABLE_WEBDAV'] && !$arResult['ENABLE_DISK']):
?><!--Hidden container is used in dialogs-->
<div id="<?= $editorCfg['emailUploadContainerID'] ?>" style="display:none;"><?
	$APPLICATION->IncludeComponent(
		'bitrix:main.file.input',
		'',
		array(
			'MODULE_ID' => 'ynsirecruitment',
			'MAX_FILE_SIZE' => 20971520,
			'ALLOW_UPLOAD' => 'A',
			'CONTROL_ID' => $editorCfg['emailUploadControlID'],
			'INPUT_NAME' => $editorCfg['emailUploadInputID'],
			'INPUT_NAME_UNSAVED' => $prefixLower.'_activity_email_new_file'
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
?></div>
<?endif;?>
<?
if($arResult['ENABLE_TASK_ADD']):
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.iframe.popup',
		'.default',
		array(
			'ON_BEFORE_HIDE' => $arResult['ENABLE_TASK_TRACING'] ? 'BX.YNSIRActivityEditor.onBeforeHide' : 'BX.DoNothing',
			'ON_AFTER_HIDE' => $arResult['ENABLE_TASK_TRACING'] ? 'BX.YNSIRActivityEditor.onAfterHide' : 'BX.DoNothing',
			'ON_BEFORE_SHOW' => $arResult['ENABLE_TASK_TRACING'] ? 'BX.YNSIRActivityEditor.onBeforeShow' : 'BX.DoNothing',
			'ON_AFTER_SHOW' => $arResult['ENABLE_TASK_TRACING'] ? 'BX.YNSIRActivityEditor.onAfterShow' : 'BX.DoNothing',
			'ON_TASK_ADDED' => $arResult['ENABLE_TASK_TRACING'] ? 'BX.YNSIRActivityEditor.onPopupTaskAdded' : 'BX.DoNothing',
			'ON_TASK_CHANGED' => $arResult['ENABLE_TASK_TRACING'] ? 'BX.YNSIRActivityEditor.onPopupTaskChanged' : 'BX.DoNothing',
			'ON_TASK_DELETED' => $arResult['ENABLE_TASK_TRACING'] ? 'BX.YNSIRActivityEditor.onPopupTaskDeleted' : 'BX.DoNothing'
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
endif;
$APPLICATION->IncludeComponent(
	'bitrix:intranet.user.selector.new',
	'',
	array(
		'MULTIPLE' => 'N',
		'NAME' => $editorCfg['userSearchJsName'],
		'INPUT_NAME' => uniqid(),
		'SHOW_EXTRANET_USERS' => 'NONE',
		'POPUP' => 'Y',
		'SITE_ID' => SITE_ID,
		'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE']
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
?><script type="text/javascript">
	BX.ready(
		function()
		{
			var editor = BX.YNSIRActivityEditor.create(
			'<?= CUtil::JSEscape($editorID) ?>',
			<?= CUtil::PhpToJSObject($editorCfg) ?>,
			<?= CUtil::PhpToJSObject(isset($arResult['EDITOR_ITEMS']) ? $arResult['EDITOR_ITEMS'] : array()) ?>
			);

			if(typeof(BX.YNSIRActivityEditor.messages) === 'undefined')
			{
				BX.YNSIRActivityEditor.messages =
				{
					"yes": "<?= GetMessage('MAIN_YES') ?>",
					"no": "<?= GetMessage('MAIN_NO') ?>",
					"deletionConfirm": "<?= GetMessageJS('YNSIR_ACTIVITY_LIST_DELETION_CONFIRM') ?>",
					"editButtonTitle": "<?= GetMessageJS('YNSIR_ACTIVITY_LIST_EDIT_BTN_TTL')?>",
					"deleteButtonTitle": "<?= GetMessageJS('YNSIR_ACTIVITY_LIST_DEL_BTN_TTL')?>",
					"saveDlgButton": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_BTN_SAVE')?>",
					"cancelShortDlgButton": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_BTN_CANCEL_SHORT')?>",
					"editDlgButton": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_BTN_EDIT')?>",
					"closeDlgButton": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_BTN_CLOSE')?>",
					"sendDlgButton": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_BTN_SEND')?>",
					"replyDlgButton": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_BTN_REPLY')?>",
					"forwardDlgButton": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_BTN_FORWARD')?>",
					"invalidEmailError": "<?= GetMessageJS('YNSIR_ACTIVITY_ERROR_INVALID_EMAIL')?>",
					"invalidPhoneError": "<?= GetMessageJS('YNSIR_ACTIVITY_ERROR_INVALID_PHONE')?>",
					"addresseeIsEmpty": "<?= GetMessageJS('YNSIR_ACTIVITY_EMAIL_EMPTY_FROM_FIELD')?>",
					"addresserIsEmpty": "<?= GetMessageJS('YNSIR_ACTIVITY_EMAIL_EMPTY_TO_FIELD')?>",

					"dataLoading" : "<?= GetMessageJS('YNSIR_ACTIVITY_DATA_LOADING')?>",
					"showAllCommunication" : "<?= GetMessageJS('YNSIR_ACTIVITY_SHOW_ALL_COMMUNICATIONS')?>",
					"prevPage" : "<?= GetMessageJS('YNSIR_ACTIVITY_PREV_PAGE')?>",
					"nextPage" : "<?= GetMessageJS('YNSIR_ACTIVITY_NEXT_PAGE')?>"
				};

				<?if($arResult['ENABLE_DISK']):?>
					BX.YNSIRActivityEditor.messages["diskAttachFiles"] = "<?= GetMessageJS('YNSIR_ACTIVITY_DISK_ATTACH_FILE')?>";
					BX.YNSIRActivityEditor.messages["diskAttachedFiles"] = "<?= GetMessageJS('YNSIR_ACTIVITY_DISK_ATTACHED_FILES')?>";
					BX.YNSIRActivityEditor.messages["diskSelectFile"] = "<?= GetMessageJS('YNSIR_ACTIVITY_DISK_SELECT_FILE')?>";
					BX.YNSIRActivityEditor.messages["diskSelectFileLegend"] = "<?= GetMessageJS('YNSIR_ACTIVITY_DISK_SELECT_FILE_LEGEND')?>";
					BX.YNSIRActivityEditor.messages["diskUploadFile"] = "<?= GetMessageJS('YNSIR_ACTIVITY_DISK_UPLOAD_FILE')?>";
					BX.YNSIRActivityEditor.messages["diskUploadFileLegend"] = "<?= GetMessageJS('YNSIR_ACTIVITY_DISK_UPLOAD_FILE_LEGEND')?>";
				<?endif;?>

				<?if($arResult['ENABLE_WEBDAV']):?>
					BX.YNSIRActivityEditor.messages["webdavFileLoading"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_FILE_LOADING')?>";
					BX.YNSIRActivityEditor.messages["webdavFileAlreadyExists"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_FILE_ALREADY_EXISTS')?>";
					BX.YNSIRActivityEditor.messages["webdavFileAccessDenied"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_FILE_ACCESS_DENIED')?>";
					BX.YNSIRActivityEditor.messages["webdavAttachFile"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_ATTACH_FILE')?>";
					BX.YNSIRActivityEditor.messages["webdavTitle"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_TITLE')?>";
					BX.YNSIRActivityEditor.messages["webdavDragFile"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_DRAG_FILE')?>";
					BX.YNSIRActivityEditor.messages["webdavSelectFile"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_SELECT_FILE')?>";
					BX.YNSIRActivityEditor.messages["webdavSelectFromLib"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_SELECT_FROM_LIB')?>";
					BX.YNSIRActivityEditor.messages["webdavLoadFiles"] = "<?= GetMessageJS('YNSIR_ACTIVITY_WEBDAV_LOAD_FILES')?>";
				<?endif;?>
			}

			if(typeof(BX.YNSIRActivityEditor.flashPlayerUrl) === 'undefined')
			{
				BX.YNSIRActivityEditor.flashPlayerUrl = "<?=CUtil::JSEscape($arResult['FLASH_PLAYER_URL'])?>";
			}

			if(typeof(BX.YNSIRActivityEditor.flashPlayerApiUrl) === 'undefined')
			{
				BX.YNSIRActivityEditor.flashPlayerApiUrl = "<?=CUtil::JSEscape($arResult['FLASH_PLAYER_API_URL'])?>";
			}

			if(typeof(BX.YNSIRCommunicationSearch.messages) === 'undefined')
			{
				BX.YNSIRCommunicationSearch.messages =
				{
					"SearchTab": "<?= GetMessageJS('YNSIR_ACTIVITY_LIST_COMMUNICATION_SEARCH_TAB')?>",
					"NoData": "<?= GetMessageJS('YNSIR_ACTIVITY_LIST_COMMUNICATION_SEARCH_NO_DATA')?>"
				}
			}

			if(typeof(BX.YNSIRActivityCalEvent.messages) === 'undefined')
			{
				BX.YNSIRActivityCalEvent.messages =
				{
					"addMeetingDlgTitle": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_TTL_ADD_MEETING')?>",
					"addCallDlgTitle": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_TTL_ADD_CALL')?>",
					"editDlgTitle": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_TTL_EDIT')?>",
					"viewDlgTitle": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_TTL_VIEW')?>",
					"activity": "<?= GetMessageJS('YNSIR_ACTIVITY_TYPE_ACTIVITY')?>",
					"meeting": "<?= GetMessageJS('YNSIR_ACTIVITY_TYPE_MEETING')?>",
					"call": "<?= GetMessageJS('YNSIR_ACTIVITY_TYPE_CALL')?>",
					"subject": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_SUBJECT')?>",
					"meetingDescrHint": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_MEETING_DESCR_HINT')?>",
					"callDescrHint": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_CALL_DESCR_HINT')?>",
					"datetime": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_DATETIME')?>",
					"setDate": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_SET_DATE')?>",
					"enableNotification": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_SET_REMINDER')?>",
					"location": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_LOCATION')?>",
					"direction": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_DIRECTION')?>",
					"partner": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_PARTNER')?>",
					"meetingSubject": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_MEETING_DEFAULT_SUBJECT')?>",
					"callSubject": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_CALL_DEFAULT_SUBJECT')?>",
					"meetingSubjectHint": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_MEETING_SUBJECT_HINT')?>",
					"callSubjectHint": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_CALL_SUBJECT_HINT')?>",
					"status": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_STATUS')?>",
					"priority": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_PRIORITY')?>",
					"type": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_TYPE')?>",
					"description": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_DESCRIPTION')?>",
					"responsible": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_RESPONSIBLE')?>",
					"undefinedType": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_UNDEFINED_TYPE')?>",
					"change": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_CHANGE_OWNER')?>",
					"owner": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_OWNER')?>",
					"ownerNotDefined": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_CAL_EVENT_OWNER_NOT_DEFINED')?>",
					"files": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_FILES')?>",
					"records": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_RECORDS')?>",
					"download": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_DOWNLOAD')?>"
				};
			}

			if(typeof(BX.YNSIRActivityEmail.messages) === 'undefined')
			{
				BX.YNSIRActivityEmail.messages =
				{
					"addEmailDlgTitle": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_TTL_ADD_EMAIL')?>",
					"viewDlgTitle": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_TTL_VIEW')?>",
					"email": "<?= GetMessageJS('YNSIR_ACTIVITY_TYPE_EMAIL')?>",
					"to": "<?= GetMessageJS('YNSIR_ACTIVITY_EMAIL_TO')?>",
					"from": "<?= GetMessageJS('YNSIR_ACTIVITY_EMAIL_FROM')?>",
					"subject": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_SUBJECT')?>",
					"template": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_TEMPLATE')?>",
					"description": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_DESCRIPTION')?>",
					"direction": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_DIRECTION')?>",
					"addresser": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_EMAIL_FIELD_ADDRESSER')?>",
					"addressee": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_EMAIL_FIELD_ADDRESSEE')?>",
					"datetime": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_DATETIME')?>",
					"change": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_CHANGE_OWNER')?>",
					"owner": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_FIELD_OWNER')?>",
					"ownerNotDefined": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_EMAIL_OWNER_NOT_DEFINED')?>",
					"noTemplate": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_NO_EMAIL_TEMPLATE')?>"
				};
			}

			if(typeof(BX.YNSIRActivityMenu.messages) === 'undefined')
			{
				BX.YNSIRActivityMenu.messages =
				{
					"task": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_MENU_TASK')?>",
					"call": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_MENU_CALL')?>",
					"meeting": "<?= GetMessageJS('YNSIR_ACTIVITY_DLG_MENU_MEETING')?>"
				};
			}

			BX.YNSIRActivityNotifyType.descrTemplate = "<?= CUtil::JSEscape(GetMessage('YNSIR_ACTIVITY_NOTIFY_DESCR')) ?>";

			BX.YNSIRActivityType.setListItems(<?= CUtil::PhpToJSObject(YNSIRActivityType::PrepareListItems()) ?>);
			BX.YNSIRActivityStatus.setListItems(
				{
					<?= YNSIRActivityType::Activity ?>: <?= CUtil::PhpToJSObject(YNSIRActivityStatus::PrepareListItems(YNSIRActivityType::Activity)) ?>,
					<?= YNSIRActivityType::Meeting ?>: <?= CUtil::PhpToJSObject(YNSIRActivityStatus::PrepareListItems(YNSIRActivityType::Meeting)) ?>,
					<?= YNSIRActivityType::Call ?>: <?= CUtil::PhpToJSObject(YNSIRActivityStatus::PrepareListItems(YNSIRActivityType::Call)) ?>
				}
			);

			BX.YNSIRActivityNotifyType.setListItems(<?= CUtil::PhpToJSObject(YNSIRActivityNotifyType::PrepareListItems()) ?>);
			BX.YNSIRActivityPriority.setListItems(<?= CUtil::PhpToJSObject(YNSIRActivityPriority::PrepareListItems()) ?>);
			BX.YNSIRActivityDirection.setListItems(
				{
					<?= YNSIRActivityType::Call ?>: <?= CUtil::PhpToJSObject(YNSIRActivityDirection::PrepareListItems(YNSIRActivityType::Call)) ?>,
					<?= YNSIRActivityType::Email ?>: <?= CUtil::PhpToJSObject(YNSIRActivityDirection::PrepareListItems(YNSIRActivityType::Email)) ?>
				}
			);

			BX.YNSIRSipManager.getCurrent().setServiceUrl(
				"YNSIR_<?=CUtil::JSEscape(YNSIROwnerType::Candidate)?>",
				"/bitrix/components/bitrix/ynsirecuitment/ajax.php?<?=bitrix_sessid_get()?>"
			);

//			BX.YNSIRSipManager.getCurrent().setServiceUrl(
//				"YNSIR_<?//=CUtil::JSEscape(YNSIROwnerType::ContactName)?>//",
//				"/bitrix/components/bitrix/crm.contact.show/ajax.php?<?//=bitrix_sessid_get()?>//"
//			);
//
//			BX.YNSIRSipManager.getCurrent().setServiceUrl(
//				"YNSIR_<?//=CUtil::JSEscape(YNSIROwnerType::CompanyName)?>//",
//				"/bitrix/components/bitrix/crm.company.show/ajax.php?<?//=bitrix_sessid_get()?>//"
//			);

			if(typeof(BX.YNSIRSipManager.messages) === 'undefined')
			{
				BX.YNSIRSipManager.messages =
				{
					"unknownRecipient": "<?= GetMessageJS('YNSIR_SIP_MGR_UNKNOWN_RECIPIENT')?>",
					"makeCall": "<?= GetMessageJS('YNSIR_SIP_MGR_MAKE_CALL')?>"
				};
			}
		}
	);
</script>
