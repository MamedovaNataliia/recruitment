<?php
IncludeModuleLangFile(__FILE__);
CModule::IncludeModule('lists');
CModule::IncludeModule('bizproc');
class YNSIRBizProc {

    public static function prepareDataAndCheck($iIdJO = 0){
        $arResult = array('ERROR' => array());
        // check config
        $arConfig = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_bizproc_config'));
        if(!empty($arConfig)){
            $iIdWorkFlow = intval($arConfig['BIZ_SECTION_ID']);
            $arOrder = array('SORT' => 'ASC', 'NAME' => 'ASC');
            $arFilter = array(
                'ACTIVE' => 'Y',
                'TYPE' => 'bitrix_processes',
                'CHECK_PERMISSIONS' => 'N',
                'SITE_ID' => 's1',
                'ID' => $iIdWorkFlow
            );
            $rsLists = CIBlock::GetList($arOrder, $arFilter);
            $arInfoWF = $rsLists->GetNext();
            if(!empty($arInfoWF)){
                $arResult['DATA']['WORKFLOW_ID'] = intval($arInfoWF['ID']);
                // check job order
                $arJobOrder = YNSIRJobOrder::getById(intval($iIdJO));
                if(!empty($arJobOrder)){
                    $arResult['DATA']['JOB_ORDER_ID'] = intval($arJobOrder['ID']);
                    $arResult['DATA']['TITLE'] = '[' . $arResult['DATA']['JOB_ORDER_ID'] . ']' . $arJobOrder['TITLE'];
                    $arResult['DATA']['WF_ELEMENT'] = intval($arJobOrder['WF_ELEMENT']);
                }
                else {
                    $arResult['ERROR'][] = GetMessage("YNSIR_CGBP_JO_NOT_FOUND");
                }
            }
            else {
                $arResult['ERROR'][] = GetMessage("YNSIR_CGBP_WF_NOT_FOUND");
            }
        }
        else {
            $arResult['ERROR'][] = GetMessage("YNSIR_CGBP_CONFIG_WF");
        }
        return $arResult;
    }

    public static function autoStart($iIdJobOrder = 0){
        global $USER;
        $arPrepareData = static::prepareDataAndCheck($iIdJobOrder);
        $arError = $arPrepareData['ERROR'];
        if(empty($arError)){
            $sTypeBlockId = "bitrix_processes";
            $iIdIBlockList = $arPrepareData['DATA']['WORKFLOW_ID'];
            $documentType = BizProcDocument::generateDocumentComplexType($sTypeBlockId, $iIdIBlockList);
            $arDocumentStates = CBPDocument::GetDocumentStates($documentType, null, "Y");
            $arCurrentUserGroups = $USER->GetUserGroupArray();
            $arCurrentUserGroups[] = "author";
            $canWrite = CBPDocument::CanUserOperateDocumentType(
                CBPCanUserOperateOperation::WriteDocument,
                $USER->GetID(),
                $documentType,
                array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
            );
            if(!$canWrite)
                $arError[] = 'No write';
            if(empty($arError))
            {
                $arBizProcParametersValues = array();
                foreach ($arDocumentStates as $arDocumentState)
                {
                    if(strlen($arDocumentState["ID"]) <= 0)
                    {
                        $arErrorsTmp = array();

                        $arBizProcParametersValues[$arDocumentState["TEMPLATE_ID"]] = CBPDocument::StartWorkflowParametersValidate(
                            $arDocumentState["TEMPLATE_ID"],
                            $arDocumentState["TEMPLATE_PARAMETERS"],
                            $documentType,
                            $arErrorsTmp
                        );

                        /*foreach($arErrorsTmp as $e)
                            $arError[] = $e["message"];*/
                    }
                }
            }

            $iIdElement = $arPrepareData['DATA']['WF_ELEMENT'];
            $arElement = array (
                'IBLOCK_ID' => $iIdIBlockList,
                'IBLOCK_SECTION_ID' => '',
                'NAME' => $arPrepareData['DATA']['TITLE'],
                'MODIFIED_BY' => $USER->GetID(),
                'RIGHTS' => array (),
            );
            if($iIdElement <= 0){
                $obElement = new CIBlockElement;
                $iIdElement = $obElement->Add($arElement, false, true, true);
                $iIdElement = intval($iIdElement);
                YNSIRJobOrder::Update(
                    $arPrepareData['DATA']['JOB_ORDER_ID'],
                    array('WF_ELEMENT' => $iIdElement),
                    array('WF_ELEMENT' => 0),
                    false
                );
            }

            if($iIdElement > 0){
                $arBizProcWorkflowId = array();
                if(array_key_exists('JOB_ORDER_ID', $arBizProcParametersValues[$arDocumentState["TEMPLATE_ID"]])){
                    $arBizProcParametersValues[$arDocumentState["TEMPLATE_ID"]]['JOB_ORDER_ID'] = $arPrepareData['DATA']['JOB_ORDER_ID'];
                }
                foreach($arDocumentStates as $arDocumentState)
                {
                    if(strlen($arDocumentState["ID"]) <= 0)
                    {
                        $arErrorsTmp = array();
                        $arBizProcWorkflowId[$arDocumentState["TEMPLATE_ID"]] = CBPDocument::StartWorkflow(
                            $arDocumentState["TEMPLATE_ID"],
                            BizProcDocument::getDocumentComplexId($sTypeBlockId, $iIdElement),
                            array_merge($arBizProcParametersValues[$arDocumentState["TEMPLATE_ID"]], array(
                                CBPDocument::PARAM_TAGRET_USER => "user_".intval($GLOBALS["USER"]->GetID()),
                                CBPDocument::PARAM_MODIFIED_DOCUMENT_FIELDS => array()
                            )),
                            $arErrorsTmp
                        );
                        foreach($arErrorsTmp as $e)
                            $arError[] = $e["message"];
                    }
                }

                CBPDocument::AddDocumentToHistory(
                    BizProcDocument::getDocumentComplexId($sTypeBlockId, $iIdElement),
                    $arElement['NAME'],
                    $GLOBALS["USER"]->GetID()
                );
            }
            else {
                $arError[] = GetMessage("YNSIR_CGBP_ERROR_NEW_ELEMENT");
            }
            
        }
        return empty($arError) ? array('SUCCESS' => 1) : array('SUCCESS' => 0, 'ERROR' => $arError);
    }
}
?>