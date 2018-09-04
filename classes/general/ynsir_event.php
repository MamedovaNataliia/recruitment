<?php

IncludeModuleLangFile(__FILE__);
use \Bitrix\Main\Type\Date;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Crm\Settings\HistorySettings;

class YNSIREvent
{
	protected $cdb = null;
	protected $currentUserID = 0;

	const TYPE_USER = 0;
	const TYPE_CHANGE = 1;
	const TYPE_EMAIL = 2;
	const TYPE_VIEW = 3;
	const TYPE_EXPORT = 4;
	const TYPE_DELETE = 5;

	const TYPE_MEETING = 101;
	const TYPE_CALL = 102;
	const TYPE_TASK = 103;
	const TYPE_ASSOCIATE = 105;
	const TYPE_ACTIVITY = 106;

	const arYNSIRTypeEvent = array(YNSIR_CANDIDATE,YNSIR_JOB_ORDER, 'SYSTEM');

	/** @var array  */
	private static $eventTypes = null;
	function __construct()
	{
		global $DB;
		$this->cdb = $DB;

		global $USER;
		$currentUser = (isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser))) ? $USER : (new CUser());
		$this->currentUserID = $currentUser->GetId();
	}

	public function getEntityType() {
		return array(
			YNSIR_CANDIDATE => GetMessage('YNSIR_EVENT_YNSIR_CANDIDATE'),
            YNSIR_JOB_ORDER => GetMessage('YNSIR_EVENT_YNSIR_ORDER'),
		);
	}

	public function Add($arFields, $bPermCheck = true)
	{
		$err_mess = (self::err_mess()).'<br />Function: Add<br />Line: ';
		$db_events = GetModuleEvents('ynsirecruitment', 'OnBeforeYNSIRAddEvent');
		while($arEvent = $db_events->Fetch())
			$arFields = ExecuteModuleEventEx($arEvent, array($arFields));

		if (isset($arFields['ENTITY']) && is_array($arFields['ENTITY']))
		{
			foreach($arFields['ENTITY'] as $key => $arEntity)
				if (!(isset($arEntity['ENTITY_TYPE']) && isset($arEntity['ENTITY_ID'])))
					unset($arEntity['ENTITY'][$key]);
		}
		else if (isset($arFields['ENTITY_TYPE']) && isset($arFields['ENTITY_ID']))
		{
			$arFields['ENTITY'] = array(
				array(
					'ENTITY_TYPE' => $arFields['ENTITY_TYPE'],
					'ENTITY_ID' => $arFields['ENTITY_ID'],
					'ENTITY_FIELD' => isset($arFields['ENTITY_FIELD']) ? $arFields['ENTITY_FIELD'] : '',
					'USER_ID' => (int)(isset($arFields['USER_ID']) ? intval($arFields['USER_ID']) : $this->currentUserID)
				)
			);
		}
		else
			return false;

		if (isset($arFields['EVENT_ID']))
		{
			$CCrmStatus = new CCrmStatus('EVENT_TYPE');
			$ar = $CCrmStatus->GetStatusByStatusId($arFields['EVENT_ID']);
			$arFields['EVENT_NAME'] = isset($ar['NAME'])? $ar['NAME']: '';
		}

		if (!$this->CheckFields($arFields))
			return false;

		if (!isset($arFields['EVENT_TYPE']))
			$arFields['EVENT_TYPE'] = 0;

		$arFiles = Array();
		if (isset($arFields['FILES']) && !empty($arFields['FILES']))
		{
			$arFields['~FILES'] = Array();
			if (isset($arFields['FILES'][0]))
				$arFields['~FILES'] = $arFields['FILES'];
			else
			{
				foreach($arFields['FILES'] as $type => $ar)
					foreach($ar as $key => $value)
						$arFields['~FILES'][$key][$type] = $value;
			}

			foreach($arFields['~FILES'] as &$arFile)
			{
				$arFile['del'] = 'N';
				$arFile['MODULE_ID'] = 'ynsirecruitment';
				$fid = intval(CFile::SaveFile($arFile, 'ynsirecruitment'));

				if ($fid > 0)
				{
					$arFiles[] = $fid;
				}
			}
			unset($arFile);
		}


		$arFields_i = Array(
			'ASSIGNED_BY_ID'=> (int)(isset($arFields['USER_ID']) ? intval($arFields['USER_ID']) : $this->currentUserID),
			'CREATED_BY_ID'	=> (int)(isset($arFields['USER_ID']) ? intval($arFields['USER_ID']) : $this->currentUserID),
			'EVENT_ID' 		=> isset($arFields['EVENT_ID'])? $arFields['EVENT_ID']: '',
			'EVENT_NAME' 	=> $arFields['EVENT_NAME'],
			'EVENT_TYPE' 	=> intval($arFields['EVENT_TYPE']),
			'EVENT_TEXT_1'  => isset($arFields['EVENT_TEXT_1'])? $arFields['EVENT_TEXT_1']: '',
			'EVENT_TEXT_2'  => isset($arFields['EVENT_TEXT_2'])? $arFields['EVENT_TEXT_2']: '',
			'FILES' 		=> serialize($arFiles),
		);

		//Validate DATE_CREATE
		if (isset($arFields['DATE_CREATE']))
		{
			$sqlTime = CDatabase::FormatDate($arFields['DATE_CREATE'], CLang::GetDateFormat('FULL', false), 'YYYY-MM-DD HH:MI:SS');
			if (!(is_string($sqlTime) && $sqlTime !== ''))
			{
				unset($arFields['DATE_CREATE']);
			}
		}

		if (isset($arFields['DATE_CREATE']))
		{
			$arFields_i['DATE_CREATE'] = $arFields['DATE_CREATE'];
		}
		else
		{
			$arFields_i['~DATE_CREATE'] = $this->cdb->GetNowFunction();
		}

		$EVENT_ID = $this->cdb->Add('b_ynsir_event', $arFields_i, array("FILES"), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		$this->AddRelation($EVENT_ID, $arFields['ENTITY'], $bPermCheck);

		$db_events = GetModuleEvents('ynsirecruitment', 'OnAfterYNSIRAddEvent');
		while($arEvent = $db_events->Fetch())
			ExecuteModuleEventEx($arEvent, array($EVENT_ID, $arFields));

		return $EVENT_ID;
	}
	public function Share($srcEntity, $dstEntities, $typeName)
	{
		$typeName = strtoupper(strval($typeName));
		if($typeName === '')
		{
			return;
		}

		global $DB;
		$srcEntityType = isset($srcEntity['ENTITY_TYPE']) ? $DB->ForSql($srcEntity['ENTITY_TYPE']) : '';
		$srcEntityID = isset($srcEntity['ENTITY_ID']) ? intval($srcEntity['ENTITY_ID']) : 0;

		if($srcEntityType === '' || $srcEntityID <= 0)
		{
			return;
		}

		$dbResult = null;
		if($typeName === 'MESSAGE')
		{
			$dbResult = $DB->Query("SELECT ID FROM b_ynsir_event WHERE ID IN (SELECT EVENT_ID FROM b_ynsir_event_relations WHERE ENTITY_TYPE = '{$srcEntityType}' AND ENTITY_ID = {$srcEntityID}) AND (EVENT_TYPE = 2 OR (EVENT_TYPE = 0 AND EVENT_ID = 'MESSAGE'))");
		}

		if($dbResult)
		{
			while($arResult = $dbResult->Fetch())
			{
				self::AddRelation($arResult['ID'], $dstEntities, false);
			}
		}
	}
	public function AddRelation($EVENT_ID, $arFields, $bPermCheck = true)
	{
		$EVENT_ID = intval($EVENT_ID);
		$REL_ID = 0;
		foreach ($arFields as $arRel)
		{
			$entityType = $arRel['ENTITY_TYPE'];
			$entityID = (int)$arRel['ENTITY_ID'];
			if($entityType !== YNSIROwnerType::SystemName)
			{
				if ($bPermCheck)
				{
//					$CrmPerms = new CCrmPerms($this->currentUserID);
//					if ($CrmPerms->HavePerm($entityType, BX_YNSIR_PERM_NONE))
//						continue;
				}

				if (!in_array($entityType, self::arYNSIRTypeEvent))
					continue;
			}

			$arRel_i = Array(
				'ENTITY_TYPE'	=> $entityType,
				'ENTITY_ID'	 	=> $entityID,
				'ENTITY_FIELD'  => isset($arRel['ENTITY_FIELD']) ? $arRel['ENTITY_FIELD'] : '',
				'EVENT_ID' 		=> $EVENT_ID,
				'ASSIGNED_BY_ID'=> isset($arRel['USER_ID']) ? intval($arRel['USER_ID']) : $this->currentUserID,
			);

			$REL_ID = $this->cdb->Add('b_ynsir_event_relations', $arRel_i, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		}
		return $REL_ID; //?
	}
	public function RemoveRelation($RELATION_ID, $ENTITY_TYPE, $bPermCheck = true)
	{
		$RELATION_ID = intval($RELATION_ID);

		if (!in_array($ENTITY_TYPE, self::arYNSIRTypeEvent))
			return false;

		if ($bPermCheck)
		{
//			$CrmPerms = new CCrmPerms($this->currentUserID);
//			if ($CrmPerms->HavePerm($ENTITY_TYPE, BX_YNSIR_PERM_NONE))
//				return false;
		}

		$sSql = "DELETE FROM b_ynsir_event_relations WHERE ID = $RELATION_ID";
		$this->cdb->Query($sSql, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		return true;
	}
	public function CheckFields($arFields)
	{
		$aMsg = array();

		if(!is_set($arFields, 'EVENT_NAME') || trim($arFields['EVENT_NAME'])=='')
			$aMsg[] = array('id'=>'EVENT_NAME', 'text'=>GetMessage('YNSIR_EVENT_ERR_ENTITY_NAME'));

		if(isset($arFields['DATE_CREATE'])
			&& !empty($arFields['DATE_CREATE'])
			&& !CheckDateTime($arFields['DATE_CREATE'], FORMAT_DATETIME))
		{
			$aMsg[] = array('id'=>'EVENT_DATE', 'text'=>GetMessage('YNSIR_EVENT_ERR_ENTITY_DATE_NOT_VALID'));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		return true;
	}
	public static function GetFields()
	{
		$relationJoin = 'INNER JOIN b_ynsir_event_relations CER ON CE.ID = CER.EVENT_ID';
		$createdByJoin = 'LEFT JOIN b_user U ON CE.CREATED_BY_ID = U.ID';

		$result = array(
			'ID' => array('FIELD' => 'CER.ID', 'TYPE' => 'int', 'FROM' => $relationJoin),

			'ENTITY_TYPE' => array('FIELD' => 'CER.ENTITY_TYPE', 'TYPE' => 'string', 'FROM' => $relationJoin),
			'ENTITY_ID' => array('FIELD' => 'CER.ENTITY_ID', 'TYPE' => 'int', 'FROM' => $relationJoin),
			'ENTITY_FIELD' => array('FIELD' => 'CER.ENTITY_FIELD', 'TYPE' => 'string', 'FROM' => $relationJoin),

			'EVENT_REL_ID' => array('FIELD' => 'CER.EVENT_ID', 'TYPE' => 'string', 'FROM' => $relationJoin),
			'EVENT_ID' => array('FIELD' => 'CE.EVENT_ID', 'TYPE' => 'string'),
			'EVENT_TYPE' => array('FIELD' => 'CE.EVENT_TYPE', 'TYPE' => 'string'),
			'EVENT_NAME' => array('FIELD' => 'CE.EVENT_NAME', 'TYPE' => 'string'),
			'EVENT_TEXT_1' => array('FIELD' => 'CE.EVENT_TEXT_1', 'TYPE' => 'string'),
			'EVENT_TEXT_2' => array('FIELD' => 'CE.EVENT_TEXT_2', 'TYPE' => 'string'),
			'FILES' => array('FIELD' => 'CE.FILES', 'TYPE' => 'string'),

			'CREATED_BY_ID' => array('FIELD' => 'CE.CREATED_BY_ID', 'TYPE' => 'int'),
			'CREATED_BY_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
			'CREATED_BY_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
			'CREATED_BY_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
			'CREATED_BY_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
			'CREATED_BY_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'int', 'FROM'=> $createdByJoin),

			'DATE_CREATE' => array('FIELD' => 'CE.DATE_CREATE', 'TYPE' => 'datetime'),
			'ASSIGNED_BY_ID' => array('FIELD' => 'CER.ASSIGNED_BY_ID', 'TYPE' => 'int', 'FROM' => $relationJoin)
		);
		return $result;
	}
	static public function BuildPermSql($aliasPrefix = 'CE', $permType = 'READ')
	{
		//no permission build
	}
	// GetList with navigation support
	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		if (isset($arFilter['ENTITY']))
		{
			if(is_string($arFilter['ENTITY']) && $arFilter['ENTITY'] !== '')
			{
				$ary = explode('_', $arFilter['ENTITY']);
				if(count($ary) === 2)
				{
					$arFilter['ENTITY_TYPE'] = CUserTypeCrm::GetLongEntityType($ary[0]);
					$arFilter['ENTITY_ID'] = intval($ary[1]);
				}
			}
			unset($arFilter['ENTITY']);
		}

		global $DBType;
		$lb = new YNSIRSQLHelper(
			$DBType,
			'b_ynsir_event',
			'CE',
			self::GetFields(),
			'',
			'',
			array()
		);
		//HACK:: override user fields data for unserialize file IDs
		$lb->SetUserFields(array('FILES' => array('MULTIPLE' => 'Y')));
		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}
	public function DeleteByElement($entityTypeName, $entityID)
	{
		$err_mess = (self::err_mess()).'<br>Function: DeleteByElement<br>Line: ';
		$entityID = (int)$entityID;

		$db_events = GetModuleEvents('ynsirecruitment', 'OnBeforeYNSIREventDeleteByElement');
		while($arEvent = $db_events->Fetch())
			ExecuteModuleEventEx($arEvent, array($entityTypeName, $entityID));

		if ($entityTypeName == '' || $entityID == 0)
		{
			return false;
		}

		// check unrelated events
		$entityTypeName = $this->cdb->ForSql($entityTypeName);
		$sql = "SELECT EVENT_ID, COUNT(ID) as CNT
			FROM b_ynsir_event_relations
			WHERE EVENT_ID IN(SELECT EVENT_ID FROM b_ynsir_event_relations WHERE ENTITY_TYPE = '{$entityTypeName}' AND ENTITY_ID = {$entityID})
			GROUP BY EVENT_ID";
		$dbRelationResult = $this->cdb->Query($sql, false, $err_mess.__LINE__);
		while($relationFields = $dbRelationResult->Fetch())
		{
			if($relationFields['CNT'] > 1)
			{
				continue;
			}

			$eventID = $relationFields['EVENT_ID'];
			$dbItemResult = $this->cdb->Query("SELECT ID, FILES FROM b_ynsir_event WHERE ID = {$eventID}", false, $err_mess.__LINE__);
			if($itemFields = $dbItemResult->Fetch())
			{
				$arFiles = isset($itemFields['FILES']) ? unserialize($itemFields['FILES']) : null;
				if(!is_array($arFiles))
				{
					continue;
				}

				foreach($arFiles as $iFileId)
				{
					CFile::Delete((int)$iFileId);
				}
				$this->cdb->Query("DELETE FROM b_ynsir_event WHERE ID = {$eventID}", false, $err_mess.__LINE__);
			}
		}
		// delete event relations
		$res = $this->cdb->Query("DELETE FROM b_ynsir_event_relations WHERE ENTITY_TYPE = '{$entityTypeName}' AND ENTITY_ID = {$entityID}", false, $err_mess.__LINE__);
		return $res;
	}
	public function Delete($ID)
	{
		global $USER;
		$err_mess = (self::err_mess()).'<br>Function: Delete<br>Line: ';

		$ID = IntVal($ID);

		$db_events = GetModuleEvents('ynsirecruitment', 'OnBeforeYNSIREventDelete');
		while($arEvent = $db_events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		$sqlWhere = '';
		// if not admin - delete only self items
		if (!$USER->IsAdmin())
		{
			$sql = "SELECT CER.ID
					FROM
						b_ynsir_event_relations CER,
						b_ynsir_event CE
					WHERE
						CE.ID = CER.EVENT_ID
					AND CER.ID = '$ID'
					AND CER.ASSIGNED_BY_ID = '".$USER->GetId()."' AND CE.EVENT_TYPE = 0";
			$res = $this->cdb->Query($sql, false, $err_mess.__LINE__);
			if (!$res->Fetch())
				return false;
		}

		// check unrelated events
		$sql = "SELECT EVENT_ID, COUNT(ID) as CNT
				FROM b_ynsir_event_relations
				WHERE EVENT_ID = (SELECT EVENT_ID FROM b_ynsir_event_relations WHERE ID = '$ID')
				GROUP BY EVENT_ID";
		$res = $this->cdb->Query($sql, false, $err_mess.__LINE__);
		if ($row = $res->Fetch())
		{
			// delete event
			if ($row['CNT'] == 1)
			{
				$obRes = $this->cdb->Query("SELECT ID, FILES FROM b_ynsir_event WHERE ID = '$row[EVENT_ID]'", false, $err_mess.__LINE__);
				if (($aRow = $obRes->Fetch()) !== false)
				{
					if (($arFiles = unserialize($aRow['FILES'])) !== false)
					{
						foreach ($arFiles as $iFileId)
							CFile::Delete((int) $iFileId);
					}
					$this->cdb->Query("DELETE FROM b_ynsir_event WHERE ID = '$row[EVENT_ID]'", false, $err_mess.__LINE__);
				}
			}
		}
		// delete event relation
		$res = $this->cdb->Query("DELETE FROM b_ynsir_event_relations WHERE ID = '$ID'", false, $err_mess.__LINE__);

		return $res;
	}
	static public function SetAssignedByElement($assignedId, $entityType, $entityId)
	{
		global $DB;

		$err_mess = (self::err_mess()).'<br>Function: SetAssignedByElement<br>Line: ';

		$assignedId = IntVal($assignedId);
		$entityId = IntVal($entityId);

		if ($entityType == '' || $entityId == 0)
			return false;

		$res = $DB->Query("UPDATE b_ynsir_event_relations SET ASSIGNED_BY_ID = $assignedId WHERE ENTITY_TYPE = '".$DB->ForSql($entityType)."' AND ENTITY_ID = '$entityId'", false, $err_mess.__LINE__);

		return $res;
	}
	static public function Rebind($entityTypeID, $srcEntityID, $dstEntityID)
	{
		$entityTypeName = YNSIROwnerType::ResolveName($entityTypeID);
		$srcEntityID = (int)$srcEntityID;
		$dstEntityID = (int)$dstEntityID;

		$sql = "SELECT R.EVENT_ID FROM b_ynsir_event_relations R
		INNER JOIN b_ynsir_event E ON R.EVENT_ID = E.ID
			AND R.ENTITY_TYPE = '{$entityTypeName}'
			AND R.ENTITY_ID = {$srcEntityID}
			AND E.EVENT_TYPE IN (0, 2)";

		global $DB;
		$err_mess = (self::err_mess()).'<br>Function: Rebind<br>Line: ';
		$dbResult = $DB->Query($sql, false, $err_mess.__LINE__);
		if(!is_object($dbResult))
		{
			return;
		}

		$IDs = array();
		while($fields = $dbResult->Fetch())
		{
			if(isset($fields['EVENT_ID']))
			{
				$IDs[] = (int)$fields['EVENT_ID'];
			}
		}

		if(!empty($IDs))
		{
			$sql = 'UPDATE b_ynsir_event_relations SET ENTITY_ID = '.$dstEntityID.' WHERE EVENT_ID IN('.implode(',', $IDs).')';
			$DB->Query($sql, false, $err_mess.__LINE__);
		}
	}
	/**
	 * @return array
	*/
	static public function GetEventTypes()
	{
		if(self::$eventTypes === null)
		{
			self::$eventTypes = array(
//				self::TYPE_USER => GetMessage('YNSIR_EVENT_TYPE_USER'),
				self::TYPE_CHANGE => GetMessage('YNSIR_EVENT_TYPE_CHANGE'),
//				self::TYPE_EMAIL => GetMessage('YNSIR_EVENT_TYPE_SNS'),
				self::TYPE_VIEW => GetMessage('YNSIR_EVENT_TYPE_VIEW'),
//				self::TYPE_EXPORT => GetMessage('YNSIR_EVENT_TYPE_EXPORT'),
				self::TYPE_DELETE => GetMessage('YNSIR_EVENT_TYPE_DELETE'),
				self::TYPE_MEETING => GetMessage('YNSIR_EVENT_TYPE_INTERVIEW'),
				self::TYPE_ASSOCIATE => GetMessage('YNSIR_EVENT_TYPE_ASSOCIATE'),
				self::TYPE_ACTIVITY => GetMessage('YNSIR_EVENT_TYPE_ACTIVITY'),

				self::TYPE_CALL => GetMessage('YNSIR_EVENT_TYPE_CALL'),
				self::TYPE_TASK => GetMessage('YNSIR_EVENT_TYPE_TASK')
			);
		}
		return self::$eventTypes;
	}
	/**
	 * @return string
	*/
	static public function GetEventTypeName($eventType)
	{
		$types = self::GetEventTypes();
		return isset($types[$eventType]) ? $types[$eventType] : '';
	}
	static public function RegisterViewEvent($entityTypeID, $entityID, $userID = 0)
	{
		global $USER;
		$entityTypeName = YNSIROwnerType::ResolveName($entityTypeID);

		if(is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($userID <= 0)
		{
			$userID = $USER->GetId();
		}

		if($userID <= 0)
		{
			return false;
		}

		$timestamp = time() + CTimeZone::GetOffset();
		//Event grouping interval in seconds
		$interval = HistorySettings::getCurrent()->getViewEventGroupingInterval() * 60;

		$query = new Bitrix\Main\Entity\Query(Bitrix\YNSIR\EventTable::getEntity());
		$query->addSelect('DATE_CREATE');
		$query->addFilter('=EVENT_TYPE', YNSIREvent::TYPE_VIEW);
		$query->addFilter('>=DATE_CREATE', ConvertTimeStamp(($timestamp - $interval), 'FULL'));

		$subQuery = new Bitrix\Main\Entity\Query(Bitrix\YNSIR\EventRelationsTable::getEntity());
		$subQuery->addSelect('EVENT_ID');
		$subQuery->addFilter('=ENTITY_TYPE', $entityTypeName);
		$subQuery->addFilter('=ENTITY_ID', $entityID);
		$query->addFilter('@ID', new Bitrix\Main\DB\SqlExpression($subQuery->getQuery()));

		$query->addOrder('DATE_CREATE', 'DESC');
		$query->setLimit(1);

		$dbResult = $query->exec();
		if(is_array($dbResult->fetch()))
		{
			return false;
		}

		$entity = new YNSIREvent();
		$entity->Add(
			array(
				'USER_ID' => $userID,
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE' => $entityTypeName,
				'EVENT_TYPE' => YNSIREvent::TYPE_VIEW,
				'EVENT_NAME' => YNSIREvent::GetEventTypeName(YNSIREvent::TYPE_VIEW),
				'DATE_CREATE' => ConvertTimeStamp($timestamp, 'FULL', SITE_ID)
			),
			false
		);
		return true;
	}
	static public function RegisterExportEvent($entityTypeID, $entityID, $userID = 0)
	{
        global $USER;
		if($userID <= 0)
		{
			$userID = $USER->GetId();
			if($userID <= 0)
			{
				return false;
			}
		}

		$eventType = YNSIREvent::TYPE_EXPORT;
		$timestamp = time() + CTimeZone::GetOffset();
		$entityTypeName = YNSIROwnerType::ResolveName($entityTypeID);

		$entity = new YNSIREvent();
		$entity->Add(
			array(
				'USER_ID' => $userID,
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE' => $entityTypeName,
				'EVENT_TYPE' => $eventType,
				'EVENT_NAME' => YNSIREvent::GetEventTypeName($eventType),
				'DATE_CREATE' => ConvertTimeStamp($timestamp, 'FULL', SITE_ID)
			),
			false
		);

		return true;
	}
	static public function RegisterDeleteEvent($entityTypeID, $entityID, $userID = 0, array $options = null)
	{
		global $USER;
		if($userID <= 0)
		{
			$userID = $USER->GetId();
			if($userID <= 0)
			{
				return false;
			}
		}

		$timestamp = time() + CTimeZone::GetOffset();
		$entityTypeCaption = YNSIROwnerType::GetDescription($entityTypeID);
		$caption = YNSIROwnerType::GetCaption($entityTypeID, $entityID, false, $options);

		$entity = new YNSIREvent();
		return (
			$entity->Add(
				array(
					'USER_ID' => $userID,
					'ENTITY_ID' => 0,
					'ENTITY_TYPE' => YNSIROwnerType::SystemName,
					'EVENT_TYPE' => YNSIREvent::TYPE_DELETE,
					'EVENT_NAME' => YNSIREvent::GetEventTypeName(YNSIREvent::TYPE_DELETE),
					'DATE_CREATE' => ConvertTimeStamp($timestamp, 'FULL', SITE_ID),
					'EVENT_TEXT_1' => "{$entityTypeCaption}: [{$entityID}] {$caption}"
				),
				false
			)
		);
	}
	static public function GetEventType($ID)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			return -1;
		}

		$dbResult = self::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('EVENT_TYPE')
		);
		$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		return is_array($arFields) && isset($arFields['EVENT_TYPE']) ? (int)$arFields['EVENT_TYPE'] : YNSIREvent::TYPE_USER;
	}
	private static function err_mess()
	{
		return '<br />Class: YNSIREvent<br />File: '.__FILE__;
	}
	public static function resolveEventType($entityType) {
		switch ($entityType) {
			case YNSIRActivityType::Meeting:
                return self::TYPE_MEETING;
			case YNSIRActivityType::Call:
                return self::TYPE_CALL;
			case YNSIRActivityType::Task:
                return self::TYPE_TASK;
		}
	}
}

?>
