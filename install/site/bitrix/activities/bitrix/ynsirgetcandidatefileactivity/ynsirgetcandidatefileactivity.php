<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Disk\File;
if (!CModule::IncludeModule("blog"))
{
    return;
}
class CBPYNSIRGetCandidateFileActivity extends CBPActivity
{

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            'Title' => '',
            'CANDIDATE_ID' => null,

            // data result
            'OFullName' => null,
            'ODetailLink' => null,
            'OIntegrationTime' => null,
            'ODateTimeFormat' => null,
            'ORequesterID' => null,
            'ORequester' => null,
            'OErrorCode' => null,
            'OProcessStatus' => null,
        );
        $this->SetPropertiesTypes(
            array(
                'OFullName' => array('Type' => 'string'),
                'ODetailLink' => array('Type' => 'string'),
                'OErrorCode' => array('Type' => 'string'),
                'OIntegrationTime' => array('Type' => 'string'),
                'ODateTimeFormat' => array('Type' => 'string'),
                'ORequesterID' => array('Type' => 'int'),
                'ORequester' => array('Type' => 'user'),
                'OProcessStatus' => array('Type' => 'int'),
                'OFileUrl' => array(
                    'Type' => 'string',
                    'Multiple' => true,
                ),
            ));
    }

    public function Execute()
    {

        if (
            !($this->CANDIDATE_ID) ||
            !CModule::IncludeModule('ynsirecruitment')) {
            return CBPActivityExecutionStatus::Closed;
        }

        $listFields = array();

        foreach ($this->getEntityData() as $fieldId => $fieldValue)
            $listFields[$fieldId] = $fieldValue;

        $this->SetProperties($listFields);

        return CBPActivityExecutionStatus::Closed;
    }

    protected function getEntityData()
    {
        global $USER, $DB;
        $entityId = null;
        $objectResult = null;
        $sFormatDB = 'YYYY-MM-DD HH:MI:SS';

        if (!CModule::IncludeModule('ynsirecruitment'))
            return array();

        if (intval($this->CANDIDATE_ID)) {
            $config = YNSIRConfig::GetListTypeList();
            $config[YNSIRConfig::TL_CANDIDATE_STATUS] = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');
            $entityId = intval($this->CANDIDATE_ID);
            $res = YNSIRCandidate::GetListCandidate(array("ID" => "ASC"), array('ID'=>$entityId));
            if($entityData = $res->Fetch()){
                $entityData['CANDIDATE_STATUS'] = $config['CANDIDATE_STATUS'][$entityData['CANDIDATE_STATUS']];
                //TODO: GET FIELD MULTIPLE
                $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CANDIDATE_ID' => $entityId));
                while ($multiField = $dbMultiField->GetNext()) {
                    switch ($multiField['TYPE']) {
                        case 'CURRENT_JOB_TITLE':
                            $entityData[$multiField['TYPE']] .=  $multiField['CONTENT'] . ' ';
                            break;
                        case 'EMAIL':
                            $m = '<a href="mailto:' . $multiField['CONTENT'] . '">' . htmlspecialcharsbx($multiField['CONTENT']) . '</a>';

                            $entityData[$multiField['TYPE']] .= htmlspecialcharsbx($multiField['CONTENT']). ' ';
                            break;
                        case 'CMOBILE':
                        case 'PHONE':
                            $entityData[$multiField['TYPE']] .= "<a  title='" . $multiField['CONTENT'] . "' href='callto://" . $multiField['CONTENT'] . "'
                                                onclick='if(typeof(top.BXIM) !== 'undefined') { top.BXIM.phoneTo('" . $multiField['CONTENT'] . "', {&quot;ENTITY_TYPE&quot;:&quot;YNSIR_LEAD&quot;,&quot;ENTITY_ID&quot;:4}); return BX.PreventDefault(event); }'>" . $multiField['CONTENT'] . "</a>";
                            break;
                        default:
                            $lable = $config[$multiField['TYPE']][$multiField['CONTENT']]['ADDITIONAL_INFO_LABEL_EN'];
                            $content = $config[$multiField['TYPE']][$multiField['CONTENT']]['NAME_' . strtoupper(LANGUAGE_ID)];
                            if ($multiField['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                                $multiField['ADDITIONAL_VALUE'] = $DB->FormatDate($multiField['ADDITIONAL_VALUE'], $arResult['FORMAT_DB_TIME'], $arResult['FORMAT_DB_BX_FULL']);
                                $multiField['ADDITIONAL_VALUE'] = FormatDateEx($multiField['ADDITIONAL_VALUE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['DATE_TIME_FORMAT']);
                            }
                            if ($multiField['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_USER) {

                                $arOWNERTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($multiField['ADDITIONAL_VALUE'], 'M'.$multiField['ID']);;
                                $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER_ID'] = $entityData['CANDIDATE_OWNER'];
                                $photo = '<div class = "crm-client-photo-wrapper">
                                            <div class="crm-client-user-def-pic">
                                                <img alt="Author Photo" src="'.$arOWNERTOOLTIP['PHOTO_URL'].'"/>
                                            </div>
                                        </div>';
                                $multiField['ADDITIONAL_VALUE'] = $arOWNERTOOLTIP['TOOLTIP'];

                            }
                            $lable = strlen($lable)>0?$lable.': ':'';
                            $additional_value = $multiField['ADDITIONAL_VALUE'] != '' ? ' (' . $lable  . $multiField['ADDITIONAL_VALUE'] . ')' : '';
                            $entityData[$multiField['TYPE']] .= $content . $additional_value . '  ';

                    }
                }

            }
            $arUploadfile = YNSIRFile::getListById($entityId);
            foreach ($arUploadfile as $arfile ) {
                foreach ($arfile as $idFile => $fitem) {
                    $file = File::loadById($idFile, array('STORAGE'));
                    if (!$file) continue;

                    $arFileInfo = CFile::GetByID($file->getFileId());

                    // id storage
                    $iIdStorageTmp = $file->getParentId();
                    if ($iIdStorageTmp > 0) {
                        $object = \Bitrix\Disk\BaseObject::loadById((int)$iIdStorageTmp, array('STORAGE'));
                        if (!$object) {
                            $idStorage = 0;
                            continue;
                        }
                    }
                    // end

                    $arDetailIFile = $arFileInfo->arResult;
                    $arPhaseName = '';
                    if (!empty($arDetailIFile)) {
                        $url = '<br><a href="'.'/upload/' . $arDetailIFile[0]['SUBDIR'] . '/' . $arDetailIFile[0]['FILE_NAME'].'" target="_blank" rel="nofollow">'.$arDetailIFile[0]['ORIGINAL_NAME'].'</a>';
                        $entityData['OFileUrl'][] = $url;
                    }
                }
            }
        }

        $currentUserID = YNSIRSecurityHelper::GetCurrentUserID();
        if (empty($entityData)) {
            $this->WriteToTrackingService(
                GetMessage('YNSIR_CACTIVITY_ERROR_ENTITY_ID_NOT_FOUND', array('#ENTITY_ID#' => $entityId)),
                $currentUserID
            );
            $entityData['OProcessStatus'] = 0;
        } else {
            $sFormatName = CSite::GetNameFormat(false);
            $entityData['OFullName'] = CUser::FormatName(
                $sFormatName,
                array(
                    "NAME" => $entityData['FIRST_NAME'],
                    "LAST_NAME" => $entityData['LAST_NAME'],
                )
            );
            $entityData['ODetailLink'] = "/recruitment/candidate/detail/".$entityId."/";
            $entityData['OProcessStatus'] = 1;
        }
        $entityData['ORequesterID'] = $currentUserID;
        $entityData['ORequester'] = 'user_' . $entityData['ORequesterID'];
        $entityData['ODateTimeFormat'] = CSite::GetDateFormat();
        $entityData['OIntegrationTime'] = $DB->FormatDate(date("Y-m-d H:i:s"), $sFormatDB, $entityData['ODateTimeFormat']);

        if ($entityData) {
            return $entityData;
        } else {
            return array();
        }
    }
    protected static function getEventParameters($task, $request)
    {
        global $USER;
        $result = array(
            "JOB_ORDER_ID" => isset($request["ynsirc_job_order"]) ? trim($request["ynsirc_job_order"]) : '',
            "COMMENT" => isset($request["comment"]) ? trim($request["comment"]) : '',
        );
        if (empty($request['cancel'])) {
            $request['task'] = $task;
            $result['RESPONCE'] = static::getTaskResponse($request);
        } else
            $result['CANCEL'] = true;

        return $result;
    }
    public static function ValidateProperties($testProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $errors = array();

        try {
            CBPHelper::ParseDocumentId($testProperties['DocumentType']);
        } catch (Exception $e) {
            $errors[] = array(
                'code' => 'NotExist',
                'parameter' => 'DocumentType',
                'message' => GetMessage('YNSIR_CACTIVITY_ERROR_DT')
            );
        }

        return array_merge($errors, parent::ValidateProperties($testProperties, $user));
    }

    //Gender Form config BP
    public static function GetPropertiesDialog(
        $documentType, $activityName, $workflowTemplate, $workflowParameters,
        $workflowVariables, $currentValues = null, $formName = '')
    {
        if (!is_array($workflowParameters))
            $workflowParameters = array();
        if (!is_array($workflowVariables))
            $workflowVariables = array();
        $renderEntityFields = '';

        if (!is_array($currentValues)) {
            $currentValues = array(
                'CANDIDATE_ID' => null,
            );
            $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
            if (is_array($currentActivity['Properties'])) {
                $currentValues['CANDIDATE_ID'] = $currentActivity['Properties']['CANDIDATE_ID'];
            }
        }


        $runtime = CBPRuntime::GetRuntime();
        return $runtime->ExecuteResourceFile(
            __FILE__,
            'properties_dialog.php',
            array(
                'documentType' => $documentType,
                'currentValues' => $currentValues,
                'formName' => $formName,
            )
        );
    }

    //Validate and Get Value when save config BP activity
    public static function GetPropertiesDialogValues(
        $documentType, $activityName, &$workflowTemplate, &$workflowParameters,
        &$workflowVariables, $currentValues, &$errors)
    {
        $errors = array();

        if (empty($currentValues['CANDIDATE_ID'])) {
            $errors[] = array(
                'code' => 'emptyRequiredField',
                'message' => str_replace('#FIELD#',
                    GetMessage("YNSIR_CACTIVITY_ERROR_ENTITY_ID") . ', ' . GetMessage("YNSIR_CACTIVITY_ERROR_ENTITY_TYPE")
                    , GetMessage("YNSIR_CACTIVITY_ERROR_FIELD_REQUIED")),
            );
            return false;
        }

        $properties = array('DocumentType' => $documentType);
        $properties['CANDIDATE_ID'] = $currentValues['CANDIDATE_ID'];

        if (!empty($errors))
            return false;

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
        $currentActivity['Properties'] = $properties;

        return true;
    }
    function getFileInfo($arData = array())
    {
        $sResult = '<div id="bx-disk-filepage-' . $arData['ID'] . '" class="bx-disk-filepage-' . $arData['TYPE'] . '">
            <a href="#" data-bx-viewer="iframe" data-bx-download="/disk/downloadFile/' . $arData['ID'] . '/?&amp;ncc=1&amp;filename=' . $arData['NAME'] . '" data-bx-title="' . $arData['NAME'] . '" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=' . $arData['ID'] . '&amp;service=gvdrive&amp; bx-attach-file-id="' . $arData['ID'] . '"="" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId=' . $arData['ID'] . '&amp;service=gdrive&amp;action=' . $arData['ID'] . '">
            ' . $arData['NAME'] . '                                             </a>
        </div>';
        $sResult .= "<script type=\"text/javascript\">BX.viewElementBind('bx-disk-filepage-" . $arData['ID'] . "',{showTitle: true},{attr: 'data-bx-viewer'});</script>";
        $p = new blogTextParser();
        $text = $p->convert($sResult);
        return $text;
    }
}