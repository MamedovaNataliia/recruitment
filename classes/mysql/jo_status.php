<?php

if (!defined('CACHED_b_jo_status')) define('CACHED_b_jo_status', 360000);

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Localization\Loc;

//use Bitrix\Crm\Category\DealCategory;

class JOStatus
{
    const JOSTATUS_NEW = 'NEW';
    const JOSTATUS_WAITING = 'WAITING';
    const JOSTATUS_APPROVAL = 'APPROVAL';
    const JOSTATUS_REJECT = 'REJECT';
    const JOSTATUS_CLOSED = 'CLOSED';

    const CDTATUS_NEW = 'NEW';
    const CDTATUS_HIRED = 'HIRED';
    const CDTATUS_REJECT = 'REJECT';

    protected $entityId = '';
    private static $FIELD_INFOS = null;
    private static $STATUSES = array();
    private static $SETTINGS = null;

    private $LAST_ERROR = '';

    function __construct($entityId)
    {
        $this->entityId = $entityId;
    }

    public static function GetEntityTypes($entity)
    {
        switch ($entity) {
            case 'JO_STATUS':
                $arEntityType = array(
                    'JO_STATUS' => array(
                        'ID' => 'JO_STATUS',
                        'NAME' => 'JOStatus',
                        'SEMANTIC_INFO' => self::GetJOStatusSemanticInfo()
                    )
                );
                break;
            case 'CANDIDATE_STATUS':
                $arEntityType = array(
                    'CANDIDATE_STATUS' => array(
                        'ID' => 'CANDIDATE_STATUS',
                        'NAME' => 'JOStatus',
                        'SEMANTIC_INFO' => self::GetCandidateStatusSemanticInfo()
                    )
                );

                break;
            default:
                break;
        }

        return $arEntityType;
    }

    public static function GetFieldExtraTypeInfo()
    {
        return array(
            'SEMANTICS' => array('TYPE' => 'string', 'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)),
            'COLOR' => array('TYPE' => 'string')
        );
    }

    private static function GetCachedStatuses($entityId)
    {
        return isset(self::$STATUSES[$entityId]) ? self::$STATUSES[$entityId] : null;
    }

    private static function SetCachedStatuses($entityId, $items)
    {
        self::$STATUSES[$entityId] = $items;
    }

    private static function ClearCachedStatuses($entityId)
    {
        unset(self::$STATUSES[$entityId]);
    }

    public function Add($arFields, $bCheckStatusId = true)
    {
        $this->LAST_ERROR = '';

        if (!$this->CheckFields($arFields, $bCheckStatusId))
            return false;

        if (!is_set($arFields['SORT']) ||
            (is_set($arFields['SORT']) && !intval($arFields['SORT']) > 0))
            $arFields['SORT'] = 10;

        if (!is_set($arFields, 'SYSTEM'))
            $arFields['SYSTEM'] = 'N';

        if (!is_set($arFields, 'STATUS_ID'))
            $arFields['STATUS_ID'] = '';

        $statusID = $arFields['STATUS_ID'];
        if ($statusID === '') {
            $statusID = !empty($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : $this->GetNextStatusId();
        }

        $arFields_i = Array(
            'ENTITY_ID' => $this->entityId,
            'STATUS_ID' => $statusID,
            'NAME' => $arFields['NAME'],
            'NAME_INIT' => $arFields['SYSTEM'] == 'Y' ? $arFields['NAME'] : '',
            'SORT' => IntVal($arFields['SORT']),
            'SYSTEM' => $arFields['SYSTEM'] == 'Y' ? 'Y' : 'N',
        );

        global $DB;
        $ID = $DB->Add('b_ynsir_jo_status', $arFields_i, array(), 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
        self::ClearCachedStatuses($this->entityId);
        return $ID;
    }

    public function Update($ID, $arFields, $arOptions = array())
    {
        $this->LAST_ERROR = '';

        if (!$this->CheckFields($arFields))
            return false;

        $ID = IntVal($ID);

        if (!is_set($arFields['SORT']) ||
            (is_set($arFields['SORT']) && !intval($arFields['SORT']) > 0))
            $arFields['SORT'] = 10;

        $arFields_u = Array(
            'NAME' => $arFields['NAME'],
            'SORT' => IntVal($arFields['SORT']),
        );
        if (is_set($arFields, 'SYSTEM'))
            $arFields_u['SYSTEM'] == 'Y' ? 'Y' : 'N';

        if (is_array($arOptions)
            && isset($arOptions['ENABLE_STATUS_ID'])
            && $arOptions['ENABLE_STATUS_ID']
            && isset($arFields['STATUS_ID'])) {
            $arFields_u['STATUS_ID'] = $arFields['STATUS_ID'];
        }

        global $DB;
        $strUpdate = $DB->PrepareUpdate('b_ynsir_jo_status', $arFields_u);
        if (!$DB->Query('UPDATE b_ynsir_jo_status SET ' . $strUpdate . ' WHERE ID=' . $ID, false, array(), 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__))
            return false;

        self::ClearCachedStatuses($this->entityId);
        return $ID;
    }

    public function Delete($ID)
    {
        $this->LAST_ERROR = '';
        $ID = IntVal($ID);

        global $DB;
        $res = $DB->Query("DELETE FROM b_ynsir_jo_status WHERE ID=$ID", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
        self::ClearCachedStatuses($this->entityId);
        return $res;
    }

    public static function GetList($arSort = array(), $arFilter = Array())
    {
        global $DB;
        $arSqlSearch = Array();
        if (is_array($arFilter)) {
            $filter_keys = array_keys($arFilter);
            for ($i = 0, $ic = count($filter_keys); $i < $ic; $i++) {
                $val = $arFilter[$filter_keys[$i]];
                if (strlen($val) <= 0 || $val == 'NOT_REF') continue;
                switch (strtoupper($filter_keys[$i])) {
                    case 'ID':
                        $arSqlSearch[] = "CS.ID = '" . $DB->ForSql($val) . "'";
                        break;
                    case 'ENTITY_ID':
                        $arSqlSearch[] = "CS.ENTITY_ID = '" . $DB->ForSql($val) . "'";
                        break;
                    case 'STATUS_ID':
                        $arSqlSearch[] = "CS.STATUS_ID = '" . $DB->ForSql($val) . "'";
                        break;
                    case 'NAME':
                        $arSqlSearch[] = GetFilterQuery('CS.NAME', $val);
                        break;
                    case 'SORT':
                        $arSqlSearch[] = "CS.SORT = '" . $DB->ForSql($val) . "'";
                        break;
                    case 'SYSTEM':
                        $arSqlSearch[] = ($val == 'Y') ? "CS.SYSTEM='Y'" : "CS.SYSTEM='N'";
                        break;
                }
            }
        }

        $sOrder = '';
        foreach ($arSort as $key => $val) {
            $ord = (strtoupper($val) <> 'ASC' ? 'DESC' : 'ASC');
            switch (strtoupper($key)) {
                case 'ID':
                    $sOrder .= ', CS.ID ' . $ord;
                    break;
                case 'ENTITY_ID':
                    $sOrder .= ', CS.ENTITY_ID ' . $ord;
                    break;
                case 'STATUS_ID':
                    $sOrder .= ', CS.STATUS_ID ' . $ord;
                    break;
                case 'NAME':
                    $sOrder .= ', CS.NAME ' . $ord;
                    break;
                case 'SORT':
                    $sOrder .= ', CS.SORT ' . $ord;
                    break;
                case 'SYSTEM':
                    $sOrder .= ', CS.SYSTEM ' . $ord;
                    break;
            }
        }

        if (strlen($sOrder) <= 0)
            $sOrder = 'CS.ID DESC';

        $strSqlOrder = ' ORDER BY ' . TrimEx($sOrder, ',');

        $strSqlSearch = GetFilterSqlSearch($arSqlSearch);
        $strSql = "
			SELECT
				CS.ID, CS.ENTITY_ID, CS.STATUS_ID, CS.NAME, CS.NAME_INIT, CS.SORT, CS.SYSTEM
			FROM
				b_ynsir_jo_status CS
			WHERE
			$strSqlSearch
			$strSqlOrder";
        $res = $DB->Query($strSql, false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);

        return $res;
    }

    public function CheckStatusId($statusId)
    {
        global $DB;
        $res = $DB->Query("SELECT ID FROM b_ynsir_jo_status WHERE ENTITY_ID='{$DB->ForSql($this->entityId)}' AND STATUS_ID ='{$DB->ForSql($statusId)}'", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
        $fields = is_object($res) ? $res->Fetch() : array();
        return isset($fields['ID']);
    }


    public static function GetStatus($entityId, $internalOnly = false)
    {
        if (!is_string($entityId)) {
            return array();
        }

        global $DB;
        $arStatus = array();


        if (CACHED_b_jo_status === false) {
            $squery = "
				SELECT *
				FROM b_ynsir_jo_status
				WHERE ENTITY_ID = '" . $DB->ForSql($entityId) . "'
				ORDER BY SORT ASC
			";
            $res = $DB->Query($squery, false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
            while ($row = $res->Fetch()) {
                $arStatus[$row['STATUS_ID']] = $row;
            }
            return $arStatus;
        } else {
            $cached = self::GetCachedStatuses($entityId);
            if ($cached !== null) {
                $arStatus = $cached;
            } else {
                $squery = "
					SELECT *
					FROM b_ynsir_jo_status
					WHERE ENTITY_ID = '" . $DB->ForSql($entityId) . "'
					ORDER BY SORT ASC
				";
                $res = $DB->Query($squery, false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
                while ($row = $res->Fetch()) {
                    $arStatus[$row['STATUS_ID']] = $row;
                }
                self::SetCachedStatuses($entityId, $arStatus);
            }
            return $arStatus;
        }
    }

    public static function GetStatusList($entityId, $internalOnly = false)
    {
        $arStatusList = Array();
        $ar = self::GetStatus($entityId, $internalOnly);
        if (is_array($ar)) {
            foreach ($ar as $arStatus) {
                $arStatusList[$arStatus['STATUS_ID']] = $arStatus['NAME'];
            }
        }

        return $arStatusList;
    }

    public static function GetStatusListEx($entityId)
    {
        $arStatusList = Array();
        $ar = self::GetStatus($entityId);
        foreach ($ar as $arStatus)
            $arStatusList[$arStatus['STATUS_ID']] = htmlspecialcharsbx($arStatus['NAME']);

        return $arStatusList;
    }

    public function GetStatusById($ID)
    {
        if (!is_int($ID)) {
            $ID = (int)$ID;
        }

        $arStatus = self::GetStatus($this->entityId);
        foreach ($arStatus as $item) {
            $currentID = isset($item['ID']) ? (int)$item['ID'] : 0;
            if ($currentID === $ID) {
                return $item;
            }
        }
        return false;
    }

    public function GetStatusByStatusId($statusId)
    {
        $arStatus = self::GetStatus($this->entityId);
        return isset($arStatus[$statusId]) ? $arStatus[$statusId] : false;
    }

    private function CheckFields($arFields, $bCheckStatusId = true)
    {

        return true;
    }

    public function GetLastError()
    {
        return $this->LAST_ERROR;
    }


    public function GetNextStatusId()
    {
        global $DB, $DBType;
        $dbTypeUC = strtoupper($DBType);

        if ($dbTypeUC === 'MYSQL') {
            $sql = "SELECT STATUS_ID AS MAX_STATUS_ID FROM b_ynsir_jo_status WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND CAST(STATUS_ID AS UNSIGNED) > 0 ORDER BY CAST(STATUS_ID AS UNSIGNED) DESC LIMIT 1";
        } else {
            return 0;
        }

        $res = $DB->Query($sql, false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
        $fields = is_object($res) ? $res->Fetch() : array();
        return (isset($fields['MAX_STATUS_ID']) ? intval($fields['MAX_STATUS_ID']) : 0) + 1;
    }

    public static function GetJOStatusSemanticInfo()
    {
        return array(
            'START_FIELD' => 'NEW',
            'WAITING_FIELD' => 'WAITING',
            'FINAL_SUCCESS_FIELD' => 'APPROVAL',
            'FINAL_UNSUCCESS_FIELD' => 'REJECT',
            'FINAL_CLOSED_FIELD' => 'CLOSED',
            'FINAL_SORT' => 0
        );
    }

    public static function GetCandidateStatusSemanticInfo()
    {
        return array(
            'START_FIELD' => 'NEW',
            'FINAL_SUCCESS_FIELD' => 'HIRED',
            'FINAL_UNSUCCESS_FIELD' => 'REJECT',
            'FINAL_SORT' => 0
        );
    }
}

?>
