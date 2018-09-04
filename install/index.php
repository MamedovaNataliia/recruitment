<?
IncludeModuleLangFile(__FILE__);

class ynsirecruitment extends CModule
{
    var $MODULE_ID = "ynsirecruitment";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    function ynsirecruitment()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path . "/version.php");
        if (is_array($arModuleVersion) && array_key_exists("YNSIR_VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["YNSIR_VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["YNSIR_VERSION_DATE"];
        } else {
            $this->MODULE_VERSION = '1.0.1';
            $this->MODULE_VERSION_DATE = '2017-07-11 00:00:00';
        }
        $this->MODULE_NAME = GetMessage("YNSIR_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("YNSIR_MODULE_DESC");
    }

    function DoInstall()
    {
        global $APPLICATION;
        $GLOBALS["errors"] = false;
        if (IsModuleInstalled($this->MODULE_ID))
            return false;
        if (!check_bitrix_sessid())
            return false;
        RegisterModule($this->MODULE_ID);
        RegisterModuleDependences('tasks', 'OnBeforeTaskAdd', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnBeforeTaskAdd');
        RegisterModuleDependences('tasks', 'OnTaskAdd', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnTaskAdd');
        RegisterModuleDependences('tasks', 'OnTaskUpdate', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnTaskUpdate');
        RegisterModuleDependences('tasks', 'OnTaskDelete', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnTaskDelete');

//        RegisterModuleDependences('main', 'OnPageStart', 'ynsirecruitment', 'YNSIRTaskEvent', 'pageStartForTask');
        RegisterModuleDependences('main', 'OnEndBufferContent', 'ynsirecruitment', 'YNSIRTaskEvent', 'changeContent');

        RegisterModuleDependences('calendar', 'OnAfterCalendarEventEdit', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnCalendarEventEdit');
        RegisterModuleDependences('calendar', 'OnAfterCalendarEventDelete', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnCalendarEventDelete');
        RegisterModuleDependences("im", "OnBeforeConfirmNotify", "ynsirecruitment", "YNSIRGeneral", "OnBeforeConfirmNotify");

        RegisterModuleDependences('ynsirecruitment', 'OnUpdateStatusOssociate', 'ynsirecruitment', 'YNSIRAssociateJob','OnUpdateStatusOssociate');
        RegisterModuleDependences('ynsirecruitment', 'OnAfterJobOrderUpdate', 'ynsirecruitment', 'YNSIRJobOrder','OnAfterJobOrderUpdate');
        RegisterModuleDependences("im", "OnGetNotifySchema", "ynsirecruitment", "YNSIRNotifierSchemeType", "PrepareNotificationSchemes");

        $this->InstallDB();
        $this->InstallFiles();
        $this->AddRewriteUrl();
        $APPLICATION->IncludeAdminFile(GetMessage('YNSIR_FORM_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/do_install1.php');
    }

    function DoUninstall()
    {
        if (!check_bitrix_sessid())
            return false;
        $GLOBALS["errors"] = false;
        $step = IntVal($_REQUEST["step"]);
        if ($step < 2)
            $GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("YNSIR_FORM_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/do_uninstall1.php");
        else {

            UnRegisterModuleDependences('tasks', 'OnBeforeTaskAdd', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnBeforeTaskAdd');
            UnRegisterModuleDependences('tasks', 'OnTaskAdd', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnTaskAdd');
            UnRegisterModuleDependences('tasks', 'OnTaskUpdate', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnTaskUpdate');
            UnRegisterModuleDependences('tasks', 'OnTaskDelete', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnTaskDelete');
            UnRegisterModuleDependences('main', 'OnEndBufferContent', 'ynsirecruitment', 'YNSIRTaskEvent', 'changeContent');

            UnRegisterModuleDependences('calendar', 'OnAfterCalendarEventEdit', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnCalendarEventEdit');
            UnRegisterModuleDependences('calendar', 'OnAfterCalendarEventDelete', 'ynsirecruitment', 'CAllYNSIRActivity', 'OnCalendarEventDelete');
            UnRegisterModuleDependences("im", "OnBeforeConfirmNotify", "ynsirecruitment", "YNSIRGeneral", "OnBeforeConfirmNotify");
            UnRegisterModuleDependences('ynsirecruitment', 'OnUpdateStatusOssociate', 'ynsirecruitment', 'YNSIRAssociateJob','OnUpdateStatusOssociate');
            UnRegisterModuleDependences('ynsirecruitment', 'OnAfterJobOrderUpdate', 'ynsirecruitment', 'YNSIRJobOrder','OnAfterJobOrderUpdate');
            UnRegisterModuleDependences("im", "OnGetNotifySchema", "ynsirecruitment", "YNSIRNotifierSchemeType", "PrepareNotificationSchemes");

            $this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
            $this->MODULE_CONFIG = $CONFIG;
            $this->UnInstallFiles();
            $this->DeleteRewriteUrl();
            $m = new CModule;
            $m->MODULE_ID = $this->MODULE_ID;
            $m->Remove();

            $GLOBALS["CACHE_MANAGER"]->CleanAll();
            $GLOBALS["stackCacheManager"]->CleanAll();
            $GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("YNSIR_FORM_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/do_uninstall2.php");
        }
    }

    function InstallDB()
    {
        global $APPLICATION, $DB, $errors;
        $errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/" . strtolower($DB->type) . "/install.sql");
        $this->installList();
        if (!empty($errors)) {
            $APPLICATION->ThrowException(implode("", $errors));
            return false;
        }
    }

    function UnInstallDB($arData = array())
    {
        global $APPLICATION, $DB, $errors;
        if ($arData['savedata'] != 'Y') {
            if (!CModule::IncludeModule('hrm')) {

                $DB->Query("DROP TABLE IF EXISTS b_hrm_type_list");
            }
            $errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/" . strtolower($DB->type) . "/uninstall.sql");
            
            COption::RemoveOption('ynsirecruitment');

            $obCache = new CPHPCache;
            $obCache->CleanDir('/younetsi/recruitment', "cache");
            if (!empty($errors)) {
                $APPLICATION->ThrowException(implode("", $errors));
                return false;
            }

            //biz
            if (CModule::IncludeModule('bizproc'))
            {
//                $documentType = ‌Array(
//                    $this->MODULE_ID,
//                    'YNSIRDocumentJobOrder',
//                    YNSIR_JOB_ORDER
//                    );
//                //delete workflow
//                $db_res = CBPWorkflowTemplateLoader::GetList(
//                    array(),
//                    array(
//                        "DOCUMENT_TYPE" => $documentType,
//                    ),
//                    false,
//                    false,
//                    array("ID", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "AUTO_EXECUTE", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN", "ACTIVE", "USER_SECOND_NAME")
//                    );
//                if ($db_res)
//
//                {
//                    while($arRes = $db_res->Fetch()) {
//                        CBPWorkflowTemplateLoader::Delete($arRes["ID"]);
//                    }
//                }

//                $arDocumentStates = CBPDocument::GetDocumentStates(
//                    $arParams["DOCUMENT_TYPE"],
//                    $arParams["DOCUMENT_ID"]);
//
//                CBPTaskService::DeleteByWorkflow($_REQUEST["id"]);
//                CBPTrackingService::DeleteByWorkflow($_REQUEST["id"]);
//                CBPStateService::DeleteWorkflow($_REQUEST["id"]);
                //delete document


            }
        }
    }

    function InstallFiles()
    {
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/' . $this->MODULE_ID . '/install/site', $_SERVER["DOCUMENT_ROOT"]."/", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/' . $this->MODULE_ID . '/install/components', $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/", true, true);
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/components/" . $this->MODULE_ID . "/");
        DeleteDirFilesEx("/recruitment/");
        DeleteDirFilesEx("/bitrix/js/" . $this->MODULE_ID . "/");

//        Activity
        DeleteDirFilesEx("/bitrix/activities/bitrix/ynsirapprovedorderjobactivity/");
        DeleteDirFilesEx("/bitrix/activities/bitrix/ynsireventaddactivity/");
        DeleteDirFilesEx("/bitrix/activities/bitrix/ynsirgetcandidatefileactivity/");
        DeleteDirFilesEx("/bitrix/activities/bitrix/ynsirgetorderentityactivity/");
        DeleteDirFilesEx("/bitrix/activities/bitrix/ynsirrequestforapproval/");


//        File in Admin
        DeleteDirFilesEx("/bitrix/admin/ynsirecruitment_bizproc_activity_settings.php");
        DeleteDirFilesEx("/bitrix/admin/ynsirecruitment_bizproc_selector.php");
        DeleteDirFilesEx("/bitrix/admin/ynsirecruitment_bizproc_wf_settings.php");


        DeleteDirFilesEx("/bitrix/js/" . $this->MODULE_ID . "/");
        return true;
    }

    function AddRewriteUrl()
    {
        CUrlRewriter::Add(
            array(
                "CONDITION" => "#^/recruitment/job-order/#",
                "RULE" => "",
                "ID" => "ynsirecruitment:job_order",
                "PATH" => "/recruitment/job_order.php",
            )
        );
        CUrlRewriter::Add(
            array(
                "CONDITION" => "#^/recruitment/candidate/#",
                "RULE" => "",
                "ID" => "ynsirecruitment:candidate",
                "PATH" => "/recruitment/candidate.php",
            )
        );
        CUrlRewriter::Add(
            array(
                "CONDITION" => "#^/recruitment/feedback/#",
                "RULE" => "",
                "ID" => "ynsirecruitment:feedback",
                "PATH" => "/recruitment/feedback.php",
            )
        );
        
        CUrlRewriter::Add(
            array(
                "CONDITION" => "#^/recruitment/configs/bp#",
                "RULE" => "",
                "ID" => "ynsirecruitment:configbp",
                "PATH" => "/recruitment/config/bp/index.php",
            )
        );
        CUrlRewriter::Add(
            array(
                "CONDITION" => "#^/recruitment/config/#",
                "RULE" => "",
                "ID" => "ynsirecruitment:config",
                "PATH" => "/recruitment/config/index.php",
            )
        );
        return true;
    }

    function DeleteRewriteUrl()
    {
        CUrlRewriter::Delete(
            array("ID" => "ynsirecruitment:candidate"),
            array("ID" => "ynsirecruitment:job_order"),
            array("ID" => "ynsirecruitment:config")
        );
        return true;
    }

    function installList()
    {
        global $DB, $APPLICATION,$USER;
        $strQuery = "SELECT COUNT(*) as TOTAL FROM `b_hrm_type_list`";
        $res = $DB->Query($strQuery);
        $arRes = $res->Fetch();
        $isInnitRole = intval($arRes['TOTAL']) > 0;
        if (!$isInnitRole){
            $errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/" . strtolower($DB->type) . "/initializeDB.sql");
            if (!CModule::IncludeModule('hrm')) {
                $DB->Query("UPDATE b_hrm_type_list SET MODIFIED_DATE = now(), CREATED_DATE = now(), CREATED_BY = '".$USER->GetID()."', MODIFIED_BY = '".$USER->GetID()."'");
            }    
        }
        if (!empty($errors)) {
            $APPLICATION->ThrowException(implode("", $errors));
            return false;
        } else {
            return true;
        }
    }

}