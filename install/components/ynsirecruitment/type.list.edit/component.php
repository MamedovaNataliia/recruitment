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
    if (is_array($arrayPermisstion['LIST']['FULL']['LIST']) && $arrayPermisstion['LIST']['FULL']['LIST']['-'] == BX_HRM_PERM_ALL) {
        $arResult['ACCESS_PERMS'] = BX_HRM_PERM_ALL;
    } else {
        $arResult['ACCESS_PERMS'] = BX_HRM_PERM_NONE;
    }
    if ($arResult['ACCESS_PERMS'] == BX_HRM_PERM_ALL || $USER->IsAdmin()||true) {

        $arResult['CONFIG']['CONTENT_TYPE'] = YNSIRConfig::getListContentType();
        $arResult['TYPE_LIST'] = $arTypeConfig[strtoupper($arParams['entity'])];
        if (isset($_POST['EDIT']) && ($_POST['EDIT'] == "Y") && check_bitrix_sessid()) {
            $APPLICATION->RestartBuffer();
            if (intval($arParams['ELEMENT_ID']) <= 0) {
                $arReturn['SUCCESS'] = false;
                $arReturn['MESSAGE'] = GetMessage('YNSIR_INVALID_ID_SELECT');
            } else {
                $data = $_POST;
                $NAME_KEY_EN = trim($data["NAME_KEY_EN"]);
                $SORT = trim($data["SORT"]);
                $ADDITIONAL_INFO = trim($data["ADDITIONAL_INFO"]);
                $ADDITIONAL_INFO_LABEL_EN = trim($data["ADDITIONAL_INFO_LABEL_EN"]);
                $error = false;
                if (strlen($NAME_KEY_EN) > 0) {
                    $skill_id = -1;
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

                    if (!$error) {
                        //find code in database
                        // check en
                        $arFilter['ENTITY'] = $arParams['entity'];
                        $arFilter['!ID'] = $arParams['ELEMENT_ID'];
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
                            $arKeySkillEdit = array(
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
                            $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('ID' => $arParams['ELEMENT_ID']), false);
                            if ($element_list = $rsLists->GetNext()) {
                                $arResult['ELEMENT'] = $element_list;
                            }
                            if($arResult['ELEMENT'] ['ADDITIONAL_INFO'] > 0){
                                unset($arKeySkillEdit['ADDITIONAL_INFO']);
                            }else{
                                //check type
                                if (!key_exists(intval($ADDITIONAL_INFO), $arResult['CONFIG']['CONTENT_TYPE'])) {
                                    $arKeySkillEdit['ADDITIONAL_INFO_LABEL_EN'] = null;
                                    $arKeySkillEdit['ADDITIONAL_INFO_LABEL_VN'] = null;
                                }
                            }
                            $kid = YNSIRTypelist::Update($arParams['ELEMENT_ID'], $arKeySkillEdit);
                        }
                        //insert new key to dbs if not in dbs
                        if ($kid > 0) {
                            $arReturn['SUCCESS'] = true;
                            $arReturn['MESSAGE'] = GetMessage('YNSIR_ADD_LIST_ITEM_SUCCESSFULL', array("#ENTITY#" => $arResult['TYPE_LIST']['NAME']));
                            //save cache
                            YNSIRCacheHelper::ClearCached(YNSIRConfig::YNSIR_CACHE_LISTS_ID,YNSIRConfig::YNSIR_CACHE_LISTS_PATH);
                            /*
                            $data = array();
                            $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('!ENTITY' => YNSIRConfig::TL_SKILL), false);
                            while ($element_list = $rsLists->GetNext()) {
                                $arData[$element_list['ENTITY']][$element_list['ID']] = $element_list;
                            }
                            YNSIRConfig::hrmSetDataCache($arData, 3600, YNSIRConfig::HRM_CACHE_LISTS_ID, YNSIRConfig::HRM_CACHE_LISTS_PATH);
                            */
                            //end save cache
                        }
                    }

                } else {
                    if (strlen($NAME_KEY_EN) <= 0) {
                        $arReturn['MESSAGE'] = GetMessage('YNSIR_FORGOT_KEY_NULL_EN', array('#FIELD_NAME#'=>GetMessage('YNSIR_LIST_KEY_TITLE_EN')));
                    }
                    $arReturn['SUCCESS'] = false;
                }
                $arReturn['EDIT'] = true;
                echo json_encode($arReturn);
            }

        } else {
            $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('ID' => $arParams['ELEMENT_ID']), false);
            if ($element_list = $rsLists->GetNext()) {
                $arResult['ELEMENT'] = $element_list;
            }
            $this->IncludeComponentTemplate($defaultTemplateName);
        }
        exit();
    } else {
        $arReturn['SUCCESS'] = false;
        $arReturn['MESSAGE'] = GetMessage('YNSIR_ACCESS_DENY');
        echo json_encode($arReturn);
        exit();
    }

}
?>