    <?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

if (!CModule::IncludeModule('ynsirecruitment')) {
    ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
    return;
}


global $APPLICATION;
$curPageUrl = $APPLICATION->GetCurPage();

$arResult['FORMAT_DB_TIME'] = 'YYYY-MM-DD';
$arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
$arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");


$arParams['PATH_TO_MAIL_TEMPLATE_LIST'] = '/recruitment/config/mailtemplate/';
$arParams['PATH_TO_MAIL_TEMPLATE_EDIT'] = '/recruitment/config/mailtemplate/edit/#element_id#/';
$arResult['EXTERNAL_CONTEXT'] = isset($_REQUEST['external_context']) ? $_REQUEST['external_context'] : '';

$userID = isset($arParams['USER_ID']) ? intval($arParams['USER_ID']) : 0;
if ($userID <= 0) {
    $userID = CCrmPerms::GetCurrentUserID();
}
$arResult['USER_ID'] = $userID;

$elementID = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if ($elementID <= 0) {
    $paramName = isset($arParams['ELEMENT_ID_PARAM_NAME']) ? strval($arParams['ELEMENT_ID_PARAM_NAME']) : '';
    if ($paramName === '') {
        $paramName = 'element_id';
    }

    $elementID = isset($_REQUEST[$paramName]) ? intval($_REQUEST[$paramName]) : 0;
}

$element = array();
if ($elementID > 0) {
    $element = YNSIRMailTemplate::GetByID($elementID);
    if (empty($element) || (!$USER->isAdmin() && $element['SCOPE'] != YNSIRMailTemplateScope::Common && $element['OWNER_ID'] != $userID )) {
        ShowError(GetMessage('YNSIR_MAIL_TEMPLATE_NOT_FOUND'));
        @define('ERROR_404', 'Y');
        if ($arParams['SET_STATUS_404'] === 'Y') {
            CHTTP::SetStatus('404 Not Found');
        }
        return;
    }
    $elementID = $element['ID'];
} else {
    $element['OWNER_ID'] = $userID;
    $element['IS_ACTIVE'] = 'Y';
}

$errors = array();

if (check_bitrix_sessid()) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply']))) {
        $elementID = isset($_POST['element_id']) ? intval($_POST['element_id']) : 0;
        $isNew = $elementID <= 0;
        $element = array();

        $element['TITLE'] = isset($_POST['TITLE']) ? $_POST['TITLE'] : '';
        $element['IS_ACTIVE'] = isset($_POST['IS_ACTIVE']) && $_POST['IS_ACTIVE'] === 'Y' ? 'Y' : 'N';
        $element['SORT'] = isset($_POST['SORT']) ? intval($_POST['SORT']) : 100;
        $element['EMAIL_FROM'] = isset($_POST['EMAIL_FROM']) ? $_POST['EMAIL_FROM'] : '';
        $element['SCOPE'] = $_POST['SCOPE'];
        $element['SUBJECT'] = isset($_POST['SUBJECT']) ? $_POST['SUBJECT'] : '';
        $element['ENTITY_TYPE_ID'] = isset($_POST['ENTITY_TYPE_ID']) ? intval($_POST['ENTITY_TYPE_ID']) : 1;
        $element['BODY'] = isset($_POST['BODY']) ? $_POST['BODY'] : '';

//        $element['CREATED'] = $DB->FormatDate($_POST['CREATED'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_TIME']);
//        $element['LAST_UPDATED'] = $DB->FormatDate($_POST['LAST_UPDATED'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_TIME']);


        if (!$isNew) {
            if (CCrmPerms::IsAdmin()) {
                if (!YNSIRMailTemplate::Exists($elementID)) {
                    $errors[] = GetMessage('YNSIR_MAIL_TEMPLATE_NOT_FOUND');
                }
            } else {
                $dbResult = YNSIRMailTemplate::GetList(array(), array('=ID' => $elementID), false, false, array('OWNER_ID'));
                $curElement = $dbResult->Fetch();
                if (!is_array($curElement)) {
                    $errors[] = GetMessage('YNSIR_MAIL_TEMPLATE_NOT_FOUND');
                }
//                elseif (intval($curElement['OWNER_ID']) !== $userID) {
//                    $errors[] = GetMessage('YNSIR_PERMISSION_DENIED');
//                }
            }

            if (empty($errors) && !YNSIRMailTemplate::Update($elementID, $element)) {
                $errors = YNSIRMailTemplate::GetErrorMessages();
                if (empty($errors)) {
                    $errors[] = GetMessage('YNSIR_MAIL_TEMPLATE_UPDATE_UNKNOWN_ERROR');
                }
            }
        } else {
            $element['OWNER_ID'] = $userID;
            $elementID = YNSIRMailTemplate::Add($element);
            if (!is_int($elementID) || $elementID <= 0) {
                $errors = YNSIRMailTemplate::GetErrorMessages();
                if (empty($errors)) {
                    $errors[] = GetMessage('YNSIR_MAIL_TEMPLATE_ADD_UNKNOWN_ERROR');
                }
            }
        }

        if (!empty($errors)) {
            ShowError(implode("\n", $errors));
        } else {
            if (isset($_POST['apply'])) {
                LocalRedirect(
                    CComponentEngine::MakePathFromTemplate(
                        $arParams['PATH_TO_MAIL_TEMPLATE_EDIT'],
                        array('element_id' => $elementID)
                    )
                );
            } else {
                if (isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '') {
                    $arResult['EXTERNAL_EVENT'] = array(
                        'NAME' => 'onCrmMailTemplateCreate',
                        'PARAMS' => array(
                            'context' => $arResult['EXTERNAL_CONTEXT'],
                            'templateTitle' => htmlspecialcharsbx($element['TITLE']),
                            'entityType' => $element['ENTITY_TYPE_ID'],
                            'templateId' => $elementID
                        )
                    );
                    $this->includeComponentTemplate('event');
                    return;
                } else {
                    LocalRedirect(
                        CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MAIL_TEMPLATE_LIST'])
                    );
                }
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete'])) {
        if (YNSIRMailTemplate::Exists($elementID)
            && !YNSIRMailTemplate::Delete($elementID)) {
            $errors = YNSIRMailTemplate::GetErrorMessages();
            if (empty($errors)) {
                $errors[] = GetMessage('YNSIR_MAIL_TEMPLATE_DELETE_UNKNOWN_ERROR');
            }
            ShowError(implode("\n", $errors));
            return;
        }

        LocalRedirect(
            CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MAIL_TEMPLATE_LIST'])
        );
    }
}

$arResult['ELEMENT_ID'] = $elementID;
$arResult['ELEMENT'] = $element;
$isEditMode = $elementID > 0;

$arResult['FORM_ID'] = $arResult['GRID_ID'] = 'YNSIR_MAIL_TEMPLATE_EDIT';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
    $arParams['PATH_TO_MAIL_TEMPLATE_LIST'],
    array()
);
$arResult['FIELDS']['tab_1'][] = array(
    'ID' => 'TITLE',
    'NAME' => GetMessage('YNSIR_MAIL_TEMPLATE_TITLE'),
    'VALUE' => isset($element['TITLE']) ? htmlspecialcharsbx($element['TITLE']) : '',
    'REQUIRED' => true
);

$arResult['FIELDS']['tab_1'][] = array(
    'ID' => 'SORT',
    'NAME' => GetMessage('YNSIR_MAIL_TEMPLATE_SORT'),
    'VALUE' => isset($element['SORT']) ? intval($element['SORT']) : 100
);

$arResult['FIELDS']['tab_1'][] = array(
    'ID' => 'SCOPE',
    'NAME' => GetMessage('YNSIR_MAIL_TEMPLATE_SCOPE'),
    'VALUE' => isset($element['SCOPE']) ? $element['SCOPE'] : YNSIRMailTemplateScope::Personal,
    'ALL_VALUES' => YNSIRMailTemplateScope::GetAllDescriptions()
);


$arResult['FIELDS']['tab_1'][] = array(
    'ID' => 'IS_ACTIVE',
    'NAME' => GetMessage('YNSIR_MAIL_TEMPLATE_IS_ACTIVE'),
    'VALUE' => isset($element['IS_ACTIVE']) && $element['IS_ACTIVE'] === 'Y' ? 'Y' : 'N'
);

$arResult['FIELDS']['tab_1'][] = array(
    'ID' => 'EMAIL_FROM',
    'NAME' => GetMessage('YNSIR_MAIL_TEMPLATE_EMAIL_FROM'),
    'VALUE' => isset($element['EMAIL_FROM']) ? htmlspecialcharsbx($element['EMAIL_FROM']) : ''
);

$arResult['FIELDS']['tab_1'][] = array(
    'ID' => 'SUBJECT',
    'NAME' => GetMessage('YNSIR_MAIL_TEMPLATE_SUBJECT'),
    'VALUE' => isset($element['SUBJECT']) ? htmlspecialcharsbx($element['SUBJECT']) : ''
);

$ownerTypes = YNSIRMailTemplate::getEntity('OWNER_TYPE');

$arResult['FIELDS']['tab_1'][] = array(
    'ID' => 'ENTITY_TYPE_ID',
    'NAME' => GetMessage('YNSIR_MAIL_ENTITY_TYPE'),
    'VALUE' => isset($element['ENTITY_TYPE_ID']) ? $element['ENTITY_TYPE_ID'] : 1,
    'ALL_VALUES' => $ownerTypes,
    'REQUIRED' => true
);

$arResult['FIELDS']['tab_1'][] = array(
    'ID' => 'BODY',
    'NAME' => GetMessage('YNSIR_MAIL_TEMPLATE_BODY'),
    'VALUE' => isset($element['BODY']) ? $element['BODY'] : ''
);

$this->IncludeComponentTemplate();
