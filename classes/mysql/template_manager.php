<?php

class YNSIRTemplateManager
{
    private static $ADAPTERS = null;

    private static function PrepareAdapters()
    {
        if (self::$ADAPTERS !== null) {
            return self::$ADAPTERS;
        }

        self::$ADAPTERS = array(
            new CCrmTemplateAdapter()
        );

        return self::$ADAPTERS;
    }

    public static function GetAllMaps()
    {
        $result = array();
        $adapters = self::PrepareAdapters();
        foreach ($adapters as $adapter) {
            $types = $adapter->GetSupportedTypes();
            foreach ($types as $typeID) {
                $map = $adapter->GetTypeMap($typeID);
                if ($map) {
                    $result[] = &$map;
                }
                unset($map);
            }
        }
        return $result;
    }

    private static function ResolveMapper($entityTypeID, $entityID)
    {
        $adapters = self::PrepareAdapters();
        foreach ($adapters as $adapter) {
            if ($adapter->IsTypeSupported($entityTypeID)) {
                return $adapter->CreateMapper($entityTypeID, $entityID);
            }
        }
        return null;
    }

    public static function PrepareTemplate($template, $entityTypeID, $entityCandidateID, $entityJobOrderID = 0, $contentTypeID = 0)
    {
        $template = strval($template);
        if ($template === '') {
            return '';
        }

        $entityTypeName = YNSIROwnerType::ResolveName($entityTypeID);
        $entityCandidateID = intval($entityCandidateID);
        if ($entityTypeName === '' || $entityCandidateID <= 0) {
            return $template;
        }

        $matches = null;
        $result_candidate = preg_match_all('/#' . YNSIR_CANDIDATE . '\.[^#]+#/i', $template, $matches_candidate, PREG_OFFSET_CAPTURE);
        $result_order = preg_match_all('/#' . YNSIR_JOB_ORDER . '\.[^#]+#/i', $template, $matches_order, PREG_OFFSET_CAPTURE);
        if (!(is_int($result_candidate) && $result_candidate > 0)) {
            return $template;
        }
        //get candidate
        $dbResFields = YNSIRCandidate::GetListCandidate(
            array(),
            array('ID' => $entityCandidateID),
            array(),
            $arOptions,
            $arSelect
        );
        $entity = YNSIRMailTemplate::getEntity();
        $arCandidate = array();
        foreach ($entity as $key => $value) {
            foreach ($value['fields'] as $k => $v) {
//                $arCandidate['#' . $value['typeName'] . '.' . $v['id'] . '#'] = '';
            }
        }
        while ($candidate = $dbResFields->Fetch()) {
            $arResult['CANDIDATE'] = $candidate;
            foreach ($candidate as $k => $v) {
                if (strlen($v) > 0){
                    $arCandidate['#' . YNSIR_CANDIDATE . '.' . $k . '#'] .= $v . ' ';
                }

            }
        }
        $sFormatName = CSite::GetNameFormat(false);

        $arCandidate['#' . YNSIR_CANDIDATE . '.FULL_NAME#'] = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $arResult['CANDIDATE']['FIRST_NAME'],
                "LAST_NAME" => $arResult['CANDIDATE']['LAST_NAME'],
            )
        );

        $list_status = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');
        $arResult['CONFIG'] = YNSIRConfig::GetListTypeList();

        $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CANDIDATE_ID' => $entityCandidateID));
        while ($multiField = $dbMultiField->GetNext()) {
            switch ($multiField['TYPE']) {
                case 'CURRENT_JOB_TITLE':
                    $arCandidate['#' . YNSIR_CANDIDATE . '.' . $multiField['TYPE'] . '#'] .= $multiField['CONTENT'] . ' ';
                    break;
                case 'WORK_POSITION':
                    $content = $arResult['CONFIG'][$multiField['TYPE']][$multiField['CONTENT']]['NAME_' . strtoupper(LANGUAGE_ID)];
                    $arCandidate['#' . YNSIR_CANDIDATE . '.APPLY_POSITION#'] .= $content . ' ';
                    break;
                case 'EMAIL':
                case 'CMOBILE':
                case 'PHONE':
                    $arCandidate['#' . YNSIR_CANDIDATE . '.' . $multiField['TYPE'] . '#'] .= $multiField['CONTENT'] . ' ';
                    break;
                default:
                    $content = $arResult['CONFIG'][$multiField['TYPE']][$multiField['CONTENT']]['NAME_' . strtoupper(LANGUAGE_ID)];
                    $arCandidate['#' . YNSIR_CANDIDATE . '.' . $multiField['TYPE'] . '#'] .= $content . ' ';
                    break;
            }
        }
        if($arCandidate['#CANDIDATE.CANDIDATE_STATUS#'] != ''){
            $arCandidate['#CANDIDATE.CANDIDATE_STATUS#'] = $list_status[trim($arCandidate['#CANDIDATE.CANDIDATE_STATUS#'])];
        }

        //End TODO: GET FIELD MULTIPLE

        //get job order
        if (!CModule::IncludeModule("blog")) {
            return;
        }
        $arJobOrder = YNSIRJobOrder::getById($entityJobOrderID, false);

        foreach ($arJobOrder as $k => $v) {
            $k = '#' . YNSIR_JOB_ORDER . '.' . $k . '#';
            if (strlen($v) > 0){
                $template = str_replace($k, $v, $template);
            }
        }
        //get config job order
        $order_config = YNSIRInterview::getListDetail(array(), array('JOB_ORDER' => $entityJobOrderID), false, false, array());
        $obRes = YNSIRAssociateJob::GetList(array(), array('ORDER_JOB_ID' => $entityJobOrderID, 'CANDIDATE_ID' => $entityCandidateID), false, false, array());
        if ($arAssociate = $obRes->Fetch()) {
            $arAsso = $arAssociate;
            $arCandidate['#CANDIDATE.CANDIDATE_STATUS#'] = $list_status[trim($arAssociate['STATUS_ID'])];
            foreach ($arCandidate as $k => $v) {
                $template = str_replace($k, $v, $template);
            }

        }else{
            foreach ($arCandidate as $k => $v) {
                $template = str_replace($k, $v, $template);
            }
            return $template;
        }

        $index = $order_config[$entityJobOrderID][$arAsso['STATUS_ROUND_ID']]['ROUND_INDEX'];
        if($index > 0){
            $round = GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $index));
            $template = str_replace('#' . YNSIR_JOB_ORDER . '.ROUND_STATUS#', $round, $template);
        }


        return $template;
    }
}