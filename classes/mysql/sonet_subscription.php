<?php
/*
 * YNSIRSonetRelation
 */
class YNSIRSonetSubscription extends CAllYNSIRSonetSubscription
{
	const TABLE_NAME = 'b_ynsir_sl_subscr';
	const DB_TYPE = 'MYSQL';

	public function Register($entityTypeID, $entityID, $typeID, $userID)
	{
		if(!YNSIROwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = intval($userID);
		$entityID = intval($entityID);
		if($userID <= 0 || $entityID <= 0)
		{
			return false;
		}

		$typeID = intval($typeID);
		if(!YNSIRSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = YNSIRSonetSubscriptionType::Observation;
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(YNSIRLiveFeedEntity::GetByEntityTypeID($entityTypeID));

		if($typeID === YNSIRSonetSubscriptionType::Responsibility)
		{
			// Multiple responsibility is not allowed
			$deleteSql = "DELETE FROM {$tableName} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}";
			$DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		}

		$insertSql = "INSERT INTO {$tableName}(USER_ID, SL_ENTITY_TYPE, ENTITY_ID, TYPE_ID)
			VALUES({$userID}, '{$slEntityType}', {$entityID}, {$typeID})
			ON DUPLICATE KEY UPDATE USER_ID = {$userID}, SL_ENTITY_TYPE = '{$slEntityType}', ENTITY_ID = {$entityID}, TYPE_ID = {$typeID}";
		$dbResult = $DB->Query($insertSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UpdateByEntity($entityTypeID, $entityID, $typeID, $userID)
	{
		if(!YNSIROwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = intval($userID);
		$entityID = intval($entityID);
		if($userID <= 0 || $entityID <= 0)
		{
			return false;
		}

		$typeID = intval($typeID);
		if(!YNSIRSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = YNSIRSonetSubscriptionType::Observation;
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(YNSIRLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$updateSql = "UPDATE {$tableName} SET USER_ID = {$userID} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID} LIMIT 1";
		$dbResult = $DB->Query($updateSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UnRegister($entityTypeID, $entityID, $typeID, $userID, $options = array())
	{
		if(!YNSIROwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = intval($userID);
		$entityID = intval($entityID);
		if($userID <= 0 || $entityID <= 0)
		{
			return false;
		}

		$typeID = intval($typeID);
		if(!YNSIRSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = YNSIRSonetSubscriptionType::Observation;
		}

		$modifiers = '';
		if(is_array($options) && isset($options['QUICK']) && $options['QUICK'] === true)
		{
			$modifiers = ' QUICK';
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(YNSIRLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$deleteSql = "DELETE{$modifiers} FROM {$tableName} WHERE USER_ID = $userID AND SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}";
		$dbResult = $DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UnRegisterByEntity($entityTypeID, $entityID)
	{
		if(!YNSIROwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$entityID = intval($entityID);
		if($entityID <= 0)
		{
			return false;
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(YNSIRLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$deleteSql = "DELETE FROM {$tableName} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID}";
		$dbResult = $DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UnRegisterByType($entityTypeID, $entityID, $typeID)
	{
		if(!YNSIROwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$entityID = intval($entityID);
		if($entityID <= 0)
		{
			return false;
		}

		$typeID = intval($typeID);
		if(!YNSIRSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = YNSIRSonetSubscriptionType::Observation;
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(YNSIRLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$deleteSql = "DELETE FROM {$tableName} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}";
		$dbResult = $DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function ImportResponsibility($entityTypeID, $userID, $top)
	{
		if(!YNSIROwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = max(intval($userID), 0);
		$top = max(intval($top), 0);
		$typeID = YNSIRSonetSubscriptionType::Responsibility;

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(YNSIRLiveFeedEntity::GetByEntityTypeID($entityTypeID));

		$selectSql = '';
		if($entityTypeID === YNSIROwnerType::Lead
			|| $entityTypeID === YNSIROwnerType::Contact
			|| $entityTypeID === YNSIROwnerType::Company
			|| $entityTypeID === YNSIROwnerType::Deal
			|| $entityTypeID === YNSIROwnerType::Activity)
		{
			if($entityTypeID === YNSIROwnerType::Lead)
			{
				$selectTableName = YNSIRLead::TABLE_NAME;
				$userFieldName = 'ASSIGNED_BY_ID';
			}
			elseif($entityTypeID === YNSIROwnerType::Contact)
			{
				$selectTableName = YNSIRContact::TABLE_NAME;
				$userFieldName = 'ASSIGNED_BY_ID';
			}
			elseif($entityTypeID === YNSIROwnerType::Company)
			{
				$selectTableName = YNSIRCompany::TABLE_NAME;
				$userFieldName = 'ASSIGNED_BY_ID';
			}
			elseif($entityTypeID === YNSIROwnerType::Deal)
			{
				$selectTableName = YNSIRDeal::TABLE_NAME;
				$userFieldName = 'ASSIGNED_BY_ID';
			}
			else //($entityTypeID === YNSIROwnerType::Activity
			{
				$selectTableName = YNSIRActivity::TABLE_NAME;
				$userFieldName = 'RESPONSIBLE_ID';
			}

			$userFieldCondition = $userID > 0 ? " = {$userID}" : ' > 0';
			$selectSql = "SELECT {$userFieldName}, '{$slEntityType}', ID, $typeID FROM {$selectTableName} WHERE {$userFieldName}{$userFieldCondition} ORDER BY ID DESC";
		}

		if($selectSql === '')
		{
			return false;
		}

		if($top > 0)
		{
			CSqlUtil::PrepareSelectTop($selectSql, $top, self::DB_TYPE);
		}

		$deleteSql = "DELETE QUICK FROM {$tableName} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND TYPE_ID = $typeID";
		if($userID > 0)
		{
			$deleteSql .= " AND USER_ID = {$userID}";
		}
		$DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);

		$insertSql = "INSERT INTO {$tableName}(USER_ID, SL_ENTITY_TYPE, ENTITY_ID, TYPE_ID) ".$selectSql;
		$dbResult = $DB->Query($insertSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult);
	}
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new YNSIREntityListBuilder(
			self::DB_TYPE,
			self::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(),
			'',
			'',
			null
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}
}