<?php
class YNSIRJobOrderTemplate {

    const TABLE_JOB_ORDER_TEMPLATE = 'b_ynsir_job_order_template';

    public static function addOrUpdate($arData = array(), $iId = 0){
        global $DB, $USER;
        $iResult = 0;
        $iId = intval($iId);
        $iIdUser = $USER->GetID();
        $arData['NAME_TEMPLATE'] = "'" . $DB->ForSql($arData['NAME_TEMPLATE']) . "'";
        $arData['CONTENT_TEMPLATE'] = "'" . $DB->ForSql($arData['CONTENT_TEMPLATE']) . "'";
        $arData['CATEGORY'] = "'" . $DB->ForSql($arData['CATEGORY']) . "'";
        if(!empty($arData)){
            if($iId > 0){
                $arData['MODIFIED_BY'] = $iIdUser;
                $iResult = $iId;
                $DB->Update(static::TABLE_JOB_ORDER_TEMPLATE, $arData, "WHERE ID='" . $iId . "'");
            }
            else {
                $arData['CREATED_BY'] = $arData['MODIFIED_BY'] = $iIdUser;
                $iResult = $DB->Insert(static::TABLE_JOB_ORDER_TEMPLATE, $arData);
            }
        }
        return intval($iResult);
    }

    public static function getList($arFilters = array(), $bIndexId = false){
        global $DB;
        $arResult = array();
        $sWhereFilter = '';
        $arFields = array("ID", "CREATED_BY", "MODIFIED_BY", "CONTENT_TEMPLATE", "NAME_TEMPLATE", "ACTIVE", "CATEGORY");
        if(!empty($arFilters)){
            foreach ($arFilters as $sKey => $dataFilter){
                if(in_array($sKey, $arFields, true)){
                    if(is_array($dataFilter) && !empty($dataFilter)){
                        $sFTemp = '';
                        foreach ($dataFilter as $item){
                            $sFTemp .= "'" . $DB->ForSql($item) . "', ";
                        }
                        $sFTemp = rtrim($sFTemp, ", ");
                        $sWhereFilter .= $sKey . " IN(" . $sFTemp . ") AND ";
                    }
                    else {
                        $sWhereFilter .= $sKey . " = '" . $DB->ForSql((string)$dataFilter) . "' AND ";
                    }
                }
            }
        }
        $sWhereFilter = rtrim($sWhereFilter, 'AND ');
        $sWhereFilter = strlen($sWhereFilter) > 0 ? ' WHERE ' . $sWhereFilter : '';

        $strSql = "
            SELECT * FROM ". static::TABLE_JOB_ORDER_TEMPLATE . " {$sWhereFilter}
        ";
        $res = $DB->Query($strSql);
        $p = new blogTextParser();
        while($arData = $res->Fetch()){
            $arData['ID'] = intval($arData['ID']);
            $arData['ACTIVE'] = intval($arData['ACTIVE']);
            $arData['CONTENT_HTML'] = $p->convert($arData['CONTENT_TEMPLATE']);
            if($bIndexId == true)
                $arResult[$arData['ID']] = $arData;
            else
                $arResult[] = $arData;
        }
        return $arResult;
    }

    public static function getListAll($arFilters = array()){
        global $DB;
        $arResult = array();
        $strSql = "
            SELECT * FROM ". static::TABLE_JOB_ORDER_TEMPLATE . "
        ";
        $res = $DB->Query($strSql);
        $p = new blogTextParser();
        while($arData = $res->Fetch()){
            $arData['ID'] = intval($arData['ID']);
            $arData['ACTIVE'] = intval($arData['ACTIVE']);
            $arData['CONTENT_HTML'] = $p->convert($arData['CONTENT_TEMPLATE']);
            $arResult[$arData['ID']] = $arData['NAME_TEMPLATE'];
        }
        return $arResult;
    }

    public static function delete($iId = 0){
        global $DB, $USER;
        $iId = intval($iId);
        if($iId > 0){
            $arInfoTemplate = static::getListAll(array('ID' => $iId));
            $strSql = "
                DELETE FROM ". static::TABLE_JOB_ORDER_TEMPLATE . " WHERE ID = {$iId}
            ";
            $DB->Query($strSql);
            // notify
            $res = $DB->Query("SELECT COUNT(*) AS TOTAL FROM b_im_message WHERE NOTIFY_TAG='" . YNSIR_MODULE_ID . '|JOB_ORDER_TEMPLATE' . '|ADD|' . $iId . "'");
            $arTotalMessage = $res->Fetch();
            if(!empty($arTotalMessage) && intval($arTotalMessage['TOTAL']) > 0){
                CIMNotify::DeleteByTag(YNSIR_MODULE_ID . '|JOB_ORDER_TEMPLATE' . '|' . $iId);
                $iIdUser = $USER->GetID();
                $iIdHRManager = YNSIRGeneral::getHRManager();
                if($iIdUser != $iIdHRManager){
                    //$arInfoUser = static::getUserInfo(array($iIdUser));
                    CIMNotify::Add(Array(
                        'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
                        'NOTIFY_MESSAGE' => GetMessage(
                            'YNSIR_JOT_DELETE_TEMPLATE',
                            array(
                                '#NAME_TEMPLATE#' => $arInfoTemplate[$iId],
                                '#ID#' => $iId,
                            )
                        ),
                        'NOTIFY_MESSAGE_OUT' => 'Notification for recruitment module',
                        'NOTIFY_MODULE' => 'main',
                        'NOTIFY_EVENT' => 'send_notify',
                        'TO_USER_ID' => $iIdHRManager,
                        'NOTIFY_TAG' => YNSIR_MODULE_ID . '|JOB_ORDER_TEMPLATE' . '|DELETE|' . $iId,
                    ));
                }
            }
        }
        return 1;
    }

    public static function deleteByCategory($iId = 0){
        global $DB;
        $iId = intval($iId);
        if($iId > 0){
            $strSql = "
                DELETE FROM ". static::TABLE_JOB_ORDER_TEMPLATE . " WHERE CATEGORY = {$iId}
            ";
            $DB->Query($strSql);
        }
    }
}
?>