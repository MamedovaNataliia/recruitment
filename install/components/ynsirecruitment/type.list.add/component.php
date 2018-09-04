<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->ShowAjaxHead();
if (!CModule::IncludeModule("bizproc"))
{
	return false;
}
if (CModule::IncludeModule('ynsirecruitment')) {
    $arTypeConfig = YNSIRConfig::getTypeList();
    $defaultTemplateName = '';
    $arRequied = array('NAME_KEY_EN','KEY_CODE');


//    $arrayPermisstion = YNSIRRole::GetUserPerms($USER->GetID());
    if (is_array($arrayPermisstion['LIST']['FULL']['LIST']) && $arrayPermisstion['LIST']['FULL']['LIST']['-'] == BX_HRM_PERM_ALL) {
        $arResult['ACCESS_PERMS'] = BX_HRM_PERM_ALL;
    } else {
        $arResult['ACCESS_PERMS'] = BX_HRM_PERM_NONE;
    }
    if ($arResult['ACCESS_PERMS'] == BX_HRM_PERM_ALL || $USER->IsAdmin()||true) {
        $arResult['CONFIG']['CONTENT_TYPE'] = YNSIRConfig::getListContentType();
        $arResult['TYPE_LIST'] = $arTypeConfig[strtoupper($arParams['entity'])];
        if ($arParams['DELETE'] == 'Y') {
            //Delete order
            if ($_SERVER["REQUEST_METHOD"] == "POST" && strlen($arParams['ELEMENT_ID']) > 0 && check_bitrix_sessid()) {
                $APPLICATION->RestartBuffer();
//                $k_id = HRMUserprofile::DeleteSourceID($arParams['ELEMENT_ID']);
                $od_id = YNSIRTypelist::Delete($arParams['ELEMENT_ID']);
                if ($od_id > 0) {
                    $arReturn['SUCCESS'] = true;
                    //save cache
                    YNSIRCacheHelper::ClearCached(YNSIRConfig::YNSIR_CACHE_LISTS_ID,YNSIRConfig::YNSIR_CACHE_LISTS_PATH);
//                    $data = array();
//                    $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('!ENTITY' => HRMConfig::TL_SKILL), false);
//                    while ($element_list = $rsLists->GetNext()) {
//                        $arData[$element_list['ENTITY']][$element_list['ID']] = $element_list;
//                    }
//                    HRMConfig::hrmSetDataCache($arData, 3600, HRMConfig::HRM_CACHE_LISTS_ID, HRMConfig::HRM_CACHE_LISTS_PATH);
                    //end save cache
                } else {
                    $arReturn['SUCCESS'] = false;
                    $arReturn['MESSAGE'] = GetMessage('YNSIR_UNKNOW_ERROR');
                }
                echo json_encode($arReturn);
            } else {
                $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('ID' => $arParams['ELEMENT_ID']), false);
                if ($element_list = $rsLists->GetNext()) {
                    $arResult['ELEMENT'] = $element_list;
                } else {
                    exit();
                };
                $this->IncludeComponentTemplate('deleteform');
            }
            exit();
            //check permission
        } else {

        if (isset($_POST['ADD']) && ($_POST['ADD'] == "Y") && check_bitrix_sessid()) {
            $APPLICATION->RestartBuffer();
            $data = $_POST;
            $NAME_KEY_EN = trim($data["NAME_KEY_EN"]);
            $ADDITIONAL_INFO = trim($data["ADDITIONAL_INFO"]);
            $ADDITIONAL_INFO_LABEL_EN = trim($data["ADDITIONAL_INFO_LABEL_EN"]);
            $error = false;
            if (strlen($NAME_KEY_EN) > 0) {
                if ($arResult['ACCESS_PERMS'] == BX_HRM_PERM_ALL || true) {
                    $SORT = trim($data['SORT']);
                    if(isset($_POST['KEY_CODE'])) {
                        $CODE = trim($data['KEY_CODE']);

                        if (strlen($CODE) <= 0) {
                            $arReturn['LANG_EN'] = true;
                            $arReturn['MESSAGE'] = GetMessage('YNSIR_FORGOT_KEY_NULL_EN', array('#FIELD_NAME#'=>GetMessage('YNSIR_LIST_KEY_CODE')));
                            $error = true;
                        }
                    } else {
                        $CODE = YNSIRTypelist::getSlug($NAME_KEY_EN);
                        if (strlen($CODE) <= 0) {
                            $arReturn['LANG_EN'] = true;
                            $arReturn['MESSAGE'] = GetMessage('YNSIR_FORGOT_KEY_NULL_EN', array('#FIELD_NAME#'=>GetMessage('YNSIR_LIST_KEY_TITLE_EN')));
                            $error = true;
                        }
                    }
                    //check type
                    if (!key_exists(intval($ADDITIONAL_INFO), $arResult['CONFIG']['CONTENT_TYPE'])) {
                        $ADDITIONAL_INFO = null;
                        $ADDITIONAL_INFO_LABEL_EN = null;
                        $ADDITIONAL_INFO_LABEL_VN = null;
                    }
                    if (!$error) {
                        //find code in database
                        // check en

                        $arFilter['ENTITY'] = $arParams['entity'];
                        $t = $arFilter;
                        $f['__INNER_FILTER_ID_CODE'] = array(
                            'LOGIC' => 'OR',
                            'NAME_EN' =>  $NAME_KEY_EN,
                            'CODE' => $CODE,);
                        $arFilter = array('LOGIC' => 'AND',
                            '__INNER_FILTER_ID1' => $t,
                            '__INNER_FILTER_ID2' => $f);
                        $rsLists = YNSIRTypelist::GetList(array(), $arFilter);
                        $kid = 0;
                        if ($key_en = $rsLists->Fetch()) {
                            $arReturn['LANG_EN'] = true;
                            $error = true;
                            $arReturn['SUCCESS'] = false;
                            if(!$arResult['SHOW_CODE']) {
                                $arReturn['MESSAGE'] = GetMessage('YNSIR_EXISTED_SKILL_EN', array('#KEY_NAME#' => $NAME_KEY_EN));
                            }
                            else {
                                if($NAME_KEY_EN == $key_en['NAME_EN']) {
                                    $arReturn['MESSAGE'] = GetMessage('YNSIR_EXISTED_SKILL_EN', array('#KEY_NAME#' => $NAME_KEY_EN));
                                } else {
                                    $arReturn['MESSAGE'] = GetMessage('YNSIR_EXISTED_LIST_ERROR', array('#FIELD_NAME#' =>GetMessage('YNSIR_LIST_KEY_CODE') ,'#KEY_NAME#' => $CODE));
                                }
                            }
                        }

                        if (!$error) {
                            $arKeySkillInsert = array(
                                'NAME_VN' => $NAME_KEY_EN,
                                'NAME_EN' => $NAME_KEY_EN,
                                'CODE' => $CODE,
                                'SORT' => $SORT,
                                'ENTITY' => strtoupper($arParams['entity']),
                                'ADDITIONAL_INFO' => $ADDITIONAL_INFO,
                                'ADDITIONAL_INFO_LABEL_EN' => $ADDITIONAL_INFO_LABEL_EN,
                                'ADDITIONAL_INFO_LABEL_VN' => $ADDITIONAL_INFO_LABEL_EN,
                                'IN_SUGGEST' => 1
                            );
                            $kid = YNSIRTypelist::Add($arKeySkillInsert);
                        }
                        //insert new key to dbs if not in dbs
                        if ($kid > 0) {
                            $arReturn['SUCCESS'] = true;
                            $arReturn['MESSAGE'] = GetMessage('YNSIR_ADD_LIST_ITEM_SUCCESSFULL', array("#ENTITY#" => $arResult['TYPE_LIST']['NAME']));
                            //save cache
                            YNSIRCacheHelper::ClearCached(YNSIRConfig::YNSIR_CACHE_LISTS_ID,YNSIRConfig::YNSIR_CACHE_LISTS_PATH);
                            /*
                            $data = array();
                            $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('!ENTITY' => HRMConfig::TL_SKILL), false);
                            while ($element_list = $rsLists->GetNext()) {
                                $arData[$element_list['ENTITY']][$element_list['ID']] = $element_list;
                            }
                            HRMConfig::hrmSetDataCache($arData, 3600, HRMConfig::HRM_CACHE_LISTS_ID, HRMConfig::HRM_CACHE_LISTS_PATH);
                            */
                            //end save cache
                        }
                    }
                } else {
                    $arReturn['SUCCESS'] = false;
                    $arReturn['MESSAGE'] = GetMessage('YNSIR_ACCESS_DENY');
                }
            } else {
                if (strlen($NAME_KEY_EN) <= 0) {
                    $arReturn['MESSAGE'] = GetMessage('YNSIR_FORGOT_KEY_NULL_EN', array('#FIELD_NAME#'=>GetMessage('YNSIR_LIST_KEY_TITLE_EN')));
                }
                $arReturn['SUCCESS'] = false;
            }
            $arReturn['ADD'] = true;
            echo json_encode($arReturn);
        } else {
            $this->IncludeComponentTemplate($defaultTemplateName);
        }
        exit();
        }
    } else {
        $arReturn['SUCCESS'] = false;
        $arReturn['MESSAGE'] = GetMessage('YNSIR_ACCESS_DENY');
        echo json_encode($arReturn);
        exit();
    }
}
?>