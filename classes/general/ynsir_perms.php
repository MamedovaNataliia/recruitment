<?php

IncludeModuleLangFile(__FILE__);

//use Bitrix\Crm\Category\DealCategory;
class YNSIRPerms_
{
	const PERM_NONE = YNSIR_PERM_NONE;
	const PERM_SELF = YNSIR_PERM_SELF;
	const PERM_DEPARTMENT = YNSIR_PERM_DEPARTMENT;
	const PERM_SUBDEPARTMENT = YNSIR_PERM_SUBDEPARTMENT;
	const PERM_OPEN = BX_CRM_PERM_OPEN;
	const PERM_ALL = YNSIR_PERM_ALL;
	const PERM_CONFIG = YNSIR_PERM_CONFIG;

	const ATTR_READ_ALL = 'RA';

	private static $ENTITY_ATTRS = array();
	private static $INSTANCES = array();
	private static $USER_ADMIN_FLAGS = array();
	protected $cdb = null;
	protected $userId = 0;
	protected $arUserPerms = array();

	function __construct($userId)
	{
		global $DB;
		$this->cdb = $DB;

		$this->userId = intval($userId);
        $arUserPerm = YNSIRRole::GetUserPerms($this->userId);
        self::normalizeUserPerm($arUserPerm);
        $this->arUserPerms = $arUserPerm;
	}
    protected static function normalizeUserPerm(&$arPerms)
    {
        $arSectionCandidatePerm = YNSIRConfig::getSectionCandidatePerms();
        if (!empty($arPerms)) {
            foreach ($arPerms as $KEYNAME => &$eachPermType) {
                switch ($KEYNAME) {
                    case YNSIR_PERM_ENTITY_CANDIDATE:
                        foreach ($eachPermType as &$TYPE_PERM):
                            if (count($TYPE_PERM) >= 1) {
                                $defaultPerm = $TYPE_PERM['-'];
                                $max_perms_tmp = "";
                                foreach ($arSectionCandidatePerm[$KEYNAME]['FIELDS'] as $FIELD => $NAME_FIELD):
                                    //update Permission Inherit
                                    if (!isset($TYPE_PERM['FIELDS'][$FIELD])) {
                                        $TYPE_PERM['FIELDS'][$FIELD] = $defaultPerm;
                                    }
                                    //update max permission.
                                    if ($TYPE_PERM['FIELDS'][$FIELD] > $max_perms_tmp) {
                                        $max_perms_tmp = $TYPE_PERM['FIELDS'][$FIELD];
                                    }
                                endforeach;
                                $TYPE_PERM['-'] = $max_perms_tmp;
                            }
                        endforeach;
                        break;
                    case YNSIR_PERM_ENTITY_ORDER:
                        foreach ($eachPermType as &$TYPE_PERM):
                            if (count($TYPE_PERM) >= 1) {
                                $defaultPerm = $TYPE_PERM['-'];
                                $max_perms_tmp = "";
                                foreach ($arSectionCandidatePerm[$KEYNAME]['FIELDS'] as $FIELD => $NAME_FIELD):
                                    //update Permission Inherit
                                    if (!isset($TYPE_PERM['FIELDS'][$FIELD])) {
                                        $TYPE_PERM['FIELDS'][$FIELD] = $defaultPerm;
                                    }
                                    //update max permission.
                                    if (($TYPE_PERM['FIELDS'][$FIELD] > $max_perms_tmp) && $FIELD != YNSIRConfig::OS_APPROVE) {
                                        $max_perms_tmp = $TYPE_PERM['FIELDS'][$FIELD];
                                    }
                                endforeach;
                                $TYPE_PERM['-'] = $max_perms_tmp;
                            }
                        endforeach;
                        break;
                    default:
                        break;
                }
            }
        }
    }

	/**
	 * Get current user permissions
	 * @return \YNSIRPerms_
	 */
	public static function GetCurrentUserPermissions()
	{
		$userID = YNSIRSecurityHelper::GetCurrentUserID();
		if(!isset(self::$INSTANCES[$userID]))
		{
			self::$INSTANCES[$userID] = new YNSIRPerms_($userID);
		}
		return self::$INSTANCES[$userID];
	}

	/**
	 * Get specified user permissions
	 * @param int $userID User ID.
	 * @return \YNSIRPerms_
	 */
	public static function GetUserPermissions($userID)
	{
		if(!is_int($userID))
		{
			$userID = intval($userID);
		}

		if($userID <= 0)
		{
			$userID = YNSIRSecurityHelper::GetCurrentUserID();
		}

		if(!isset(self::$INSTANCES[$userID]))
		{
			self::$INSTANCES[$userID] = new YNSIRPerms_($userID);
		}
		return self::$INSTANCES[$userID];
	}

	public static function GetCurrentUserID()
	{
		return YNSIRSecurityHelper::GetCurrentUserID();
	}

	public static function IsAdmin($userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = is_numeric($userID) ? (int)$userID : 0;
		}

		$result = false;
		if($userID <= 0)
		{
			$user = YNSIRSecurityHelper::GetCurrentUser();
			$userID =  $user->GetID();

			if($userID <= 0)
			{
				false;
			}

			if(isset(self::$USER_ADMIN_FLAGS[$userID]))
			{
				return self::$USER_ADMIN_FLAGS[$userID];
			}

			$result = $user->IsAdmin();
			if($result)
			{
				self::$USER_ADMIN_FLAGS[$userID] = true;
				return true;
			}

			try
			{
				if(\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
					&& CModule::IncludeModule('bitrix24'))
				{
					if(class_exists('CBitrix24')
						&& method_exists('CBitrix24', 'IsPortalAdmin'))
					{
						// New style check
						$result = CBitrix24::IsPortalAdmin($userID);
					}
					else
					{
						// Check user group 1 ('Portal admins')
						$arGroups = $user->GetUserGroup($userID);
						$result = in_array(1, $arGroups);
					}
				}
			}
			catch(Exception $e)
			{
			}
		}
		else
		{
			if(isset(self::$USER_ADMIN_FLAGS[$userID]))
			{
				return self::$USER_ADMIN_FLAGS[$userID];
			}

			try
			{
				if(IsModuleInstalled('bitrix24')
					&& CModule::IncludeModule('bitrix24')
					&& class_exists('CBitrix24')
					&& method_exists('CBitrix24', 'IsPortalAdmin'))
				{
					// Bitrix24 context new style check
					$result = CBitrix24::IsPortalAdmin($userID);
				}
				else
				{
					//Check user group 1 ('Admins')
					$user = new CUser();
					$arGroups = $user->GetUserGroup($userID);
					$result = in_array(1, $arGroups);
				}
			}
			catch(Exception $e)
			{
			}
		}
		self::$USER_ADMIN_FLAGS[$userID] = $result;
		return $result;
	}

	public static function IsAuthorized()
	{
		return YNSIRSecurityHelper::GetCurrentUser()->IsAuthorized();
	}

	static public function GetUserAttr($iUserID)
	{
		static $arResult = array();
		if (!empty($arResult[$iUserID]))
		{
			return $arResult[$iUserID];
		}

		$iUserID = (int) $iUserID;

		$arResult[$iUserID] = array();

		$obRes = CAccess::GetUserCodes($iUserID);
		while($arCode = $obRes->Fetch())
			if (strpos($arCode['ACCESS_CODE'], 'DR') !== 0)
				$arResult[$iUserID][strtoupper($arCode['PROVIDER_ID'])][] = $arCode['ACCESS_CODE'];

		if (!empty($arResult[$iUserID]['INTRANET']) && Bitrix\Main\Loader::includeModule('intranet'))
		{
			foreach ($arResult[$iUserID]['INTRANET'] as $iDepartment)
			{
				if(substr($iDepartment, 0, 1) === 'D')
				{
					$arTree = CIntranetUtils::GetDeparmentsTree(substr($iDepartment, 1), true);
					foreach ($arTree as $iSubDepartment)
					{
						$arResult[$iUserID]['SUBINTRANET'][] = 'D'.$iSubDepartment;
					}
				}
			}
		}

		return $arResult[$iUserID];
	}

	static public function BuildUserEntityAttr($userID)
	{
		$result = array('INTRANET' => array());
		$userID = intval($userID);
		$arUserAttrs = $userID > 0 ? self::GetUserAttr($userID) : array();
		if(!empty($arUserAttrs['INTRANET']))
		{
			//HACK: Removing intranet subordination relations, otherwise staff will get access to boss's entities
			foreach($arUserAttrs['INTRANET'] as $code)
			{
				if(strpos($code, 'IU') !== 0)
				{
					$result['INTRANET'][] = $code;
				}
			}
			$result['INTRANET'][] = "IU{$userID}";
		}
		return $result;
	}

	static public function GetCurrentUserAttr()
	{
		return self::GetUserAttr(YNSIRSecurityHelper::GetCurrentUserID());
	}

	public function GetUserID()
	{
		return $this->userId;
	}

	public function GetUserPerms()
	{
		return $this->arUserPerms;
	}

	public function HavePerm($permEntity, $permAttr, $permType = 'READ')
	{
		// HACK: only for product and currency support
		$permType = strtoupper($permType);
		if ($permEntity == 'CONFIG' && $permAttr == self::PERM_CONFIG && $permType == 'READ')
		{
			return true;
		}

		// HACK: Compatibility with CONFIG rights
		if ($permEntity == 'CONFIG')
			$permType = 'WRITE';

		if(self::IsAdmin($this->userId))
		{
			return $permAttr != self::PERM_NONE;
		}

		if (!isset($this->arUserPerms[$permEntity][$permType]))
			return $permAttr == self::PERM_NONE;

		$icnt = count($this->arUserPerms[$permEntity][$permType]);
		if ($icnt > 1 && $this->arUserPerms[$permEntity][$permType]['-'] == self::PERM_NONE)
		{
			foreach ($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue)
			{
				if ($sField == '-')
					continue ;
				$sPrevPerm = $permAttr;
				foreach ($arFieldValue as $fieldValue => $sAttr)
					if ($sAttr > $permAttr)
						return $sAttr == self::PERM_NONE;
				return $permAttr == self::PERM_NONE;
			}
		}

		if ($permAttr == self::PERM_NONE)
			return $this->arUserPerms[$permEntity][$permType]['-'] == self::PERM_NONE;

		if ($this->arUserPerms[$permEntity][$permType]['-'] >= $permAttr)
			return true;

		return false;
	}

    public function HavePermSec($permEntity, $permAttr, $permType = 'READ',$SECTION)
    {
        // HACK: only for product and currency support
        $permType = strtoupper($permType);
        if ($permEntity == 'CONFIG' && $permAttr == self::PERM_CONFIG && $permType == 'READ')
        {
            return true;
        }

        // HACK: Compatibility with CONFIG rights
        if ($permEntity == 'CONFIG')
            $permType = 'WRITE';

        if(self::IsAdmin($this->userId))
        {
            return $permAttr != self::PERM_NONE;
        }

        if (!isset($this->arUserPerms[$permEntity][$permType]['FIELDS'][$SECTION]))
            return $permAttr == self::PERM_NONE;

        if ($permAttr == self::PERM_NONE)
            return $this->arUserPerms[$permEntity][$permType]['FIELDS'][$SECTION] == self::PERM_NONE;

        if ($this->arUserPerms[$permEntity][$permType]['FIELDS'][$SECTION] >= $permAttr)
            return true;

        return false;
    }

    public function GetPermType($permEntity, $permType = 'READ', $arEntityAttr = array(),$SECTION = '')
	{
		if (self::IsAdmin($this->userId))
			return self::PERM_ALL;

		if (!isset($this->arUserPerms[$permEntity][$permType]))
			return self::PERM_NONE;

		if($permType === 'READ' && in_array(self::ATTR_READ_ALL, $arEntityAttr, true))
			return self::PERM_ALL;

		$icnt = count($this->arUserPerms[$permEntity][$permType]);
        if(strlen($SECTION) > 0) {
            //check by SECTION
            if ($icnt > 1) {
                foreach ($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue) {
                    if ($sField == '-')
                        continue;
                    foreach ($arFieldValue as $fieldValue => $sAttr) {
                        if ($fieldValue == $SECTION)
                            return $sAttr;
                    }
                }
                return self::PERM_NONE;
            } else
                return self::PERM_NONE;
        } else {
            //CHECK FOR ALL (NOT SECTION)
            if ($icnt == 1 && isset($this->arUserPerms[$permEntity][$permType]['-']))
                return $this->arUserPerms[$permEntity][$permType]['-'];
            else if ($icnt > 1) {
                foreach ($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue) {
                    if ($sField == '-')
                        continue;
                    foreach ($arFieldValue as $fieldValue => $sAttr) {
                        if (in_array($sField . $fieldValue, $arEntityAttr))
                            return $sAttr;
                    }
                }
                return $this->arUserPerms[$permEntity][$permType]['-'];
            } else
                return self::PERM_NONE;
        }
	}

	public static function GetEntityGroup($permEntity, $permAttr = self::PERM_NONE, $permType = 'READ')
	{
		global $DB;

		$arResult = array();
		$arRole = YNSIRRole::GetRoleByAttr($permEntity, $permAttr, $permType);

		if (!empty($arRole))
		{
			$sSql = 'SELECT RELATION FROM b_ynsir_role_relation WHERE RELATION LIKE \'G%\' AND ROLE_ID IN ('.implode(',', $arRole).')';
			$res = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			while($row = $res->Fetch())
				$arResult[] = substr($row['RELATION'], 1);
		}
		return $arResult;
	}

	public static function GetEntityRelations($permEntity, $permAttr = self::PERM_NONE, $permType = 'READ')
	{
		global $DB;

		$arResult = array();
		$arRole = YNSIRRole::GetRoleByAttr($permEntity, $permAttr, $permType);

		if (!empty($arRole))
		{
			$sSql = 'SELECT RELATION FROM b_ynsir_role_relation WHERE ROLE_ID IN ('.implode(',', $arRole).')';
			$res = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			while($row = $res->Fetch())
				$arResult[] = $row['RELATION'];
		}
		return $arResult;
	}

	static public function IsAccessEnabled(YNSIRPerms_ $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = self::GetCurrentUserPermissions();
		}

		return YNSIRCandidate::IsAccessEnabled($userPermissions);
//			|| CCrmContact::IsAccessEnabled($userPermissions)
	}


	public function CheckEnityAccess($permEntity, $permType, $arEntityAttr,$SECTION = '')
	{
		if (!is_array($arEntityAttr))
			$arEntityAttr = array();

		$enableCummulativeMode = COption::GetOptionString('recruitment', 'enable_permission_cumulative_mode', 'Y') === 'Y';

		$permAttr = $this->GetPermType($permEntity, $permType, $arEntityAttr,$SECTION);
		if ($permAttr == self::PERM_NONE)
		{
			return false;
		}
		if ($permAttr == self::PERM_ALL)
		{
			return true;
		}
		if ($permAttr == self::PERM_OPEN)
		{
			if((in_array('O', $arEntityAttr) || in_array('U'.$this->userId, $arEntityAttr)))
			{
				return true;
			}

			//For backward compatibility (is not comulative mode)
			if(!$enableCummulativeMode)
			{
				return false;
			}
		}
		if ($permAttr >= self::PERM_SELF && in_array('U'.$this->userId, $arEntityAttr))
		{
			return true;
		}

		$arAttr = self::GetUserAttr($this->userId);

		if ($permAttr >= self::PERM_DEPARTMENT && is_array($arAttr['INTRANET']))
		{
			// PERM_OPEN: user may access to not opened entities in his department
			foreach ($arAttr['INTRANET'] as $iDepartment)
			{
				if (in_array($iDepartment, $arEntityAttr))
				{
					return true;
				}
			}
		}
		if ($permAttr >= self::PERM_SUBDEPARTMENT && is_array($arAttr['SUBINTRANET']))
		{
			// PERM_OPEN: user may access to not opened entities in his intranet
			foreach ($arAttr['SUBINTRANET'] as $iDepartment)
			{
				if (in_array($iDepartment, $arEntityAttr))
				{
					return true;
				}
			}
		}
		return false;
	}
	public function GetUserAttrForSelectEntity($permEntity, $permType, $bForcePermAll = false)
	{
		$arResult = array();
		if (!isset($this->arUserPerms[$permEntity][$permType]))
			return $arResult;

		$entityTypeName = self::ResolveEntityTypeName($permEntity);
		$arAttr = self::GetUserAttr($this->userId);
		$sDefAttr = $this->arUserPerms[$permEntity][$permType]['-'];
		foreach($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue)
		{
			if($sField === '-' && count($this->arUserPerms[$permEntity][$permType]) == 2)
			{
				$_arResult = array();
				$sAttr = $sDefAttr;
				if ($sAttr == self::PERM_NONE)
				{
					continue;
				}
				if ($sAttr == self::PERM_OPEN)
				{
					$_arResult[] = 'O';
					foreach ($arAttr['USER'] as $iUser)
					{
						$arResult[] = array($iUser);
					}
				}
				else if ($sAttr != self::PERM_ALL || $bForcePermAll)
				{
					if ($sAttr >= self::PERM_SELF)
					{
						foreach ($arAttr['USER'] as $iUser)
						{
							$arResult[] = array($iUser);
						}
					}
					if ($sAttr >= self::PERM_DEPARTMENT && isset($arAttr['INTRANET']))
					{
						foreach ($arAttr['INTRANET'] as $iDepartment)
						{
							//HACK: SKIP IU code it is not required for this method
							if(strlen($iDepartment) > 0 && substr($iDepartment, 0, 2) === 'IU')
							{
								continue;
							}

							if(!in_array($iDepartment, $_arResult))
							{
								$_arResult[] = $iDepartment;
							}
						}
					}
					if ($sAttr >= self::PERM_SUBDEPARTMENT && isset($arAttr['SUBINTRANET']))
					{
						foreach ($arAttr['SUBINTRANET'] as $iDepartment)
						{
							if(strlen($iDepartment) > 0 && substr($iDepartment, 0, 2) === 'IU')
							{
								continue;
							}

							if(!in_array($iDepartment, $_arResult))
							{
								$_arResult[] = $iDepartment;
							}
						}
					}
				}
				else //self::PERM_ALL
				{
					$arResult[] = array();
				}

				if(!empty($_arResult))
				{
					$arResult[] = $_arResult;
				}
			}
		}

		return $arResult;
	}

	static private function RegisterPermissionSet(&$items, $newItem)
	{
		$qty = count($items);
		if($qty === 0)
		{
			$items[] = $newItem;
			return $newItem;
		}

		$user = $newItem['USER'];
		$openedOnly = $newItem['OPENED_ONLY'];
		$departments = $newItem['DEPARTMENTS'];
		$departmentQty = count($departments);
		for($i = 0; $i < $qty; $i++)
		{
			if($user === $items[$i]['USER']
				&& $openedOnly === $items[$i]['OPENED_ONLY']
				&& $departmentQty === count($items[$i]['DEPARTMENTS'])
				&& ($departmentQty === 0 || count(array_diff($departments, $items[$i]['DEPARTMENTS'])) === 0))
			{
				$items[$i]['SCOPES'] = array_merge($items[$i]['SCOPES'], $newItem['SCOPES']);
				return $items[$i];
			}
		}

		$items[] = $newItem;
		return $newItem;
	}

	static public function BuildSqlForEntitySet(array $enityTypes, $aliasPrefix, $permType, $options = array())
	{
		$total = count($enityTypes);
		if($total === 0)
		{
			return false;
		}

		if($total === 1)
		{
			return self::BuildSql($enityTypes[0], $aliasPrefix, $permType, $options);
		}

		$restrictedQueries = array();
		$unrestrictedQueries = array();
		$enityOptions = array_merge(array('RAW_QUERY' => true), $options);
		$sqlType = isset($options['PERMISSION_SQL_TYPE']) && $options['PERMISSION_SQL_TYPE'] === 'FROM' ? 'FROM' : 'WHERE';

		for($i = 0; $i < $total; $i++)
		{
			$enityType = $enityTypes[$i];
			$sql = self::BuildSql($enityType, $aliasPrefix, $permType, $enityOptions);

			if($sql === false)
			{
				continue;
			}

			if($sql === '')
			{
				if($sqlType === 'WHERE')
				{
					$unrestrictedQueries[] = "SELECT {$aliasPrefix}P.ENTITY_ID FROM b_ynsir_entity_perms {$aliasPrefix}P WHERE {$aliasPrefix}P.ENTITY = '{$enityType}'";
				}
				else
				{
					$unrestrictedQueries[] = "SELECT {$aliasPrefix}P.ENTITY_ID FROM b_ynsir_entity_perms {$aliasPrefix}P WHERE {$aliasPrefix}P.ENTITY = '{$enityType}' GROUP BY {$aliasPrefix}P.ENTITY_ID";
				}
			}
			else
			{
				$restrictedQueries[] = $sql;
			}
		}

		if(!empty($restrictedQueries))
		{
			$queries = array_merge($unrestrictedQueries, $restrictedQueries);
		}
		else
		{
			$unrestricted = count($unrestrictedQueries);
			if($unrestricted === 0)
			{
				return false;
			}

			if($unrestricted === $total)
			{
				return '';
			}

			$queries = $unrestrictedQueries;
		}


		$sqlUnion = isset($options['PERMISSION_SQL_UNION']) && $options['PERMISSION_SQL_UNION'] === 'DISTINCT' ? 'DISTINCT' : 'ALL';
		$querySql = implode($sqlUnion === 'DISTINCT' ? ' UNION ' : ' UNION ALL ', $queries);
		if(isset($options['RAW_QUERY']) && $options['RAW_QUERY'] === true)
		{
			return $querySql;
		}

		$identityCol = 'ID';
		if(is_array($options)
			&& isset($options['IDENTITY_COLUMN'])
			&& is_string($options['IDENTITY_COLUMN'])
			&& $options['IDENTITY_COLUMN'] !== '')
		{
			$identityCol = $options['IDENTITY_COLUMN'];
		}

		if($sqlType === 'WHERE')
		{
			return "{$aliasPrefix}.{$identityCol} IN ({$querySql})";
		}

		return "INNER JOIN ({$querySql}) {$aliasPrefix}GP ON {$aliasPrefix}.{$identityCol} = {$aliasPrefix}GP.ENTITY_ID";
	}

	static public function BuildSql($permEntity, $sAliasPrefix, $mPermType, $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$perms = null;
		if(isset($arOptions['PERMS']))
		{
			$perms = $arOptions['PERMS'];
		}

		if(!is_object($perms))
		{
			// Process current user permissions
			if (self::IsAdmin(0))
			{
				return '';
			}

			$perms = self::GetCurrentUserPermissions();
		}
		elseif(self::IsAdmin($perms->GetUserID()))
		{
			return '';
		}

		$arUserAttr = array();
		$arPermType = is_array($mPermType) ? $mPermType : array($mPermType);
		foreach ($arPermType as $sPermType)
		{
			$arUserAttr = array_merge($arUserAttr, $perms->GetUserAttrForSelectEntity($permEntity, $sPermType));
		}

		if (empty($arUserAttr))
		{
			// Access denied
			return false;
		}

		$restrictByIDs = null;
		if(isset($arOptions['RESTRICT_BY_IDS']) && is_array($arOptions['RESTRICT_BY_IDS']))
		{
			$restrictByIDs = $arOptions['RESTRICT_BY_IDS'];
		}

		$enableCummulativeMode = COption::GetOptionString('ynsirecruitment', 'enable_permission_cumulative_mode', 'Y') === 'Y';
		$allAttrs = self::GetUserAttr($perms->GetUserID());
		$intranetAttrs = array();
		$allIntranetAttrs = isset($allAttrs['INTRANET']) && is_array($allAttrs['INTRANET']) && !empty($allAttrs['INTRANET']) ? $allAttrs['INTRANET'] : array();
		if(!empty($allIntranetAttrs))
		{
			foreach($allIntranetAttrs as $attr)
			{
				if(preg_match('/^D\d+$/', $attr))
				{
					$intranetAttrs[] = "'{$attr}'";
				}
			}
		}

		$permissionSets = array();
		foreach ($arUserAttr as &$attrs)
		{
			if (empty($attrs))
			{
				continue;
			}

			$permissionSet = array(
				'USER' => '',
				'DEPARTMENTS' => array(),
				'OPENED_ONLY' => '',
				'SCOPES' => array()
			);

			$qty = count($attrs);
			for($i = 0; $i < $qty; $i++)
			{
				$attr = $attrs[$i];

				if($attr === 'O')
				{
					$permissionSet['OPENED_ONLY'] = "'{$attr}'";
				}
				elseif(preg_match('/^U\d+$/', $attr))
				{
					$permissionSet['USER'] = "'{$attr}'";
				}
				elseif(preg_match('/^D\d+$/', $attr))
				{
					$permissionSet['DEPARTMENTS'][] = "'{$attr}'";
				}
			}

			if(empty($permissionSet['SCOPES']))
			{
				if($permissionSet['OPENED_ONLY'] !== '')
				{
					//HACK: for OPENED ONLY mode - allow user own entities too.
					$userAttr = isset($allAttrs['USER']) && is_array($allAttrs['USER']) && !empty($allAttrs['USER']) ? $allAttrs['USER'][0] : '';
					if($userAttr !== '')
					{
						$permissionSets[] = array(
							'USER' => "'{$userAttr}'",
							'DEPARTMENTS' => array(),
							'OPENED_ONLY' => '',
							'SCOPES' => array()
						);
					}

					if($enableCummulativeMode && !empty($intranetAttrs))
					{
						//OPENED ONLY mode - allow user department entities too.
						$permissionSets[] = array(
							'USER' => '',
							'DEPARTMENTS' => $intranetAttrs,
							'OPENED_ONLY' => '',
							'SCOPES' => array()
						);
					}
				}

				$permissionSets[] = &$permissionSet;
				unset($permissionSet);
			}
			else
			{
				$permissionSet = self::RegisterPermissionSet($permissionSets, $permissionSet);
				if($permissionSet['OPENED_ONLY'] !== '')
				{
					//HACK: for OPENED ONLY mode - allow user own entities too.
					$userAttr = isset($allAttrs['USER']) && is_array($allAttrs['USER']) && !empty($allAttrs['USER']) ? $allAttrs['USER'][0] : '';
					if($userAttr !== '')
					{
						self::RegisterPermissionSet(
							$permissionSets,
							array(
								'USER' => "'{$userAttr}'",
								'DEPARTMENTS' => array(),
								'OPENED_ONLY' => '',
								'SCOPES' => $permissionSet['SCOPES']
							)
						);
					}
				}
			}
		}
		unset($attrs);

		$isRestricted = false;
		$subQueries = array();

		$effectiveEntityIDs = array();
		if(is_array($restrictByIDs))
		{
			foreach($restrictByIDs as $entityID)
			{
				if($entityID > 0)
				{
					$effectiveEntityIDs[] = (int)$entityID;
				}
			}
		}

		foreach($permissionSets as &$permissionSet)
		{
			$scopes = $permissionSet['SCOPES'];
			$scopeQty = count($scopes);
			if($scopeQty === 0)
			{
				$restrictionSql = '';
				if($permissionSet['OPENED_ONLY'] !== '')
				{
					$attr = $permissionSet['OPENED_ONLY'];
					$restrictionSql = "{$sAliasPrefix}P.ATTR = {$attr}";
				}
				elseif($permissionSet['USER'] !== '')
				{
					$attr = $permissionSet['USER'];
					$restrictionSql = "{$sAliasPrefix}P.ATTR = {$attr}";
				}
				elseif(!empty($permissionSet['DEPARTMENTS']))
				{
					$departments = $permissionSet['DEPARTMENTS'];
					$restrictionSql = count($departments) > 1
						? $sAliasPrefix.'P.ATTR IN('.implode(', ', $departments).')'
						: $sAliasPrefix.'P.ATTR = '.$departments[0];
				}

				if($restrictionSql !== '')
				{
					$subQuery = "SELECT {$sAliasPrefix}P.ENTITY_ID FROM b_ynsir_entity_perms {$sAliasPrefix}P WHERE {$sAliasPrefix}P.ENTITY = '{$permEntity}' AND {$restrictionSql}";
					if(!empty($effectiveEntityIDs))
					{
						$subQuery .= " AND {$sAliasPrefix}P.ENTITY_ID IN (".implode(', ', $effectiveEntityIDs).")";
					}
					$subQueries[] = $subQuery;

					if(!$isRestricted)
					{
						$isRestricted = true;
					}
				}
			}
			else
			{
				$scopeSql = $scopeQty > 1
					? $sAliasPrefix.'P2.ATTR IN ('.implode(', ', $scopes).')'
					: $sAliasPrefix.'P2.ATTR = '.$scopes[0];

				$restrictionSql = '';
				if($permissionSet['OPENED_ONLY'] !== '')
				{
					$attr = $permissionSet['OPENED_ONLY'];
					$restrictionSql = "{$sAliasPrefix}P1.ATTR = {$attr}";
				}
				elseif($permissionSet['USER'] !== '')
				{
					$attr = $permissionSet['USER'];
					$restrictionSql = "{$sAliasPrefix}P1.ATTR = {$attr}";
				}
				elseif(!empty($permissionSet['DEPARTMENTS']))
				{
					$departments = $permissionSet['DEPARTMENTS'];
					$restrictionSql = count($departments) > 1
						? $sAliasPrefix.'P1.ATTR IN('.implode(', ', $departments).')'
						: $sAliasPrefix.'P1.ATTR = '.$departments[0];
				}

				if($restrictionSql !== '')
				{
					$subQuery = "SELECT {$sAliasPrefix}P2.ENTITY_ID FROM b_ynsir_entity_perms {$sAliasPrefix}P1 INNER JOIN b_ynsir_entity_perms {$sAliasPrefix}P2 ON {$sAliasPrefix}P1.ENTITY = '{$permEntity}' AND {$sAliasPrefix}P2.ENTITY = '{$permEntity}' AND {$sAliasPrefix}P1.ENTITY_ID = {$sAliasPrefix}P2.ENTITY_ID AND {$restrictionSql} AND {$scopeSql}";
				}
				else
				{
					$subQuery = "SELECT {$sAliasPrefix}P2.ENTITY_ID FROM b_ynsir_entity_perms {$sAliasPrefix}P2 WHERE {$sAliasPrefix}P2.ENTITY = '{$permEntity}' AND {$scopeSql}";
				}
				if(!empty($effectiveEntityIDs))
				{
					$subQuery .= " AND {$sAliasPrefix}P2.ENTITY_ID IN (".implode(',', $effectiveEntityIDs).")";
				}
				$subQueries[] = $subQuery;

				if(!$isRestricted)
				{
					$isRestricted = true;
				}
			}
		}
		unset($permissionSet);

		if(!$isRestricted)
		{
			return '';
		}

		if(isset($arOptions['READ_ALL']) && $arOptions['READ_ALL'] === true)
		{
			//Add permission 'Read allowed to Everyone' permission
			$readAll = self::ATTR_READ_ALL;
			$subQuery = "SELECT {$sAliasPrefix}P.ENTITY_ID FROM b_ynsir_entity_perms {$sAliasPrefix}P WHERE {$sAliasPrefix}P.ENTITY = '{$permEntity}' AND {$sAliasPrefix}P.ATTR = '{$readAll}'";
			if(!empty($effectiveEntityIDs))
			{
				$subQuery .= " AND {$sAliasPrefix}P.ENTITY_ID IN (".implode(',', $effectiveEntityIDs).")";
			}
			$subQueries[] = $subQuery;
		}

		$sqlUnion = isset($arOptions['PERMISSION_SQL_UNION']) && $arOptions['PERMISSION_SQL_UNION'] === 'DISTINCT' ? 'DISTINCT' : 'ALL';
		$subQuerySql = implode($sqlUnion === 'DISTINCT' ? ' UNION ' : ' UNION ALL ', $subQueries);
		//BAD SOLUTION IF USER HAVE A LOT OF RECORDS IN b_ynsir_entity_perms TABLE.
		//$subQuerySql = "SELECT {$sAliasPrefix}PX.ENTITY_ID FROM({$subQuerySql}) {$sAliasPrefix}PX ORDER BY {$sAliasPrefix}PX.ENTITY_ID ASC";

		if(isset($arOptions['RAW_QUERY']) && $arOptions['RAW_QUERY'] === true)
		{
			return $subQuerySql;
		}

		$identityCol = 'ID';
		if(is_array($arOptions)
			&& isset($arOptions['IDENTITY_COLUMN'])
			&& is_string($arOptions['IDENTITY_COLUMN'])
			&& $arOptions['IDENTITY_COLUMN'] !== '')
		{
			$identityCol = $arOptions['IDENTITY_COLUMN'];
		}

		$sqlType = isset($arOptions['PERMISSION_SQL_TYPE']) && $arOptions['PERMISSION_SQL_TYPE'] === 'FROM' ? 'FROM' : 'WHERE';
		if($sqlType === 'WHERE')
		{
			return "{$sAliasPrefix}.{$identityCol} IN ({$subQuerySql})";
		}

		return "INNER JOIN ({$subQuerySql}) {$sAliasPrefix}GP ON {$sAliasPrefix}.{$identityCol} = {$sAliasPrefix}GP.ENTITY_ID";
	}

	static public function GetEntityAttr($permEntity, $arIDs)
	{
		if (!is_array($arIDs))
		{
			$arIDs = array($arIDs);
		}

		$effectiveEntityIDs = array();
		foreach ($arIDs as $entityID)
		{
			if($entityID > 0)
			{
				$effectiveEntityIDs[] = $entityID;
			}
		}

		$arResult = array();
		$entityPrefix = strtoupper($permEntity);
		$missedEntityIDs = array();
		foreach($effectiveEntityIDs as $entityID)
		{
			$entityKey = "{$entityPrefix}_{$entityID}";
			if(isset(self::$ENTITY_ATTRS[$entityKey]))
			{
				$arResult[$entityID] = self::$ENTITY_ATTRS[$entityKey];
			}
			else
			{
				$missedEntityIDs[] = $entityID;
			}
		}

		if(empty($missedEntityIDs))
		{
			return $arResult;
		}

		global $DB;
		$sqlIDs = implode(',', $missedEntityIDs);
		$obRes = $DB->Query(
			"SELECT ENTITY_ID, ATTR FROM b_ynsir_entity_perms WHERE ENTITY = '{$DB->ForSql($permEntity)}' AND ENTITY_ID IN({$sqlIDs})",
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		while($arRow = $obRes->Fetch())
		{
			$entityID = $arRow['ENTITY_ID'];
			$entityAttr = $arRow['ATTR'];
			$arResult[$entityID][] = $entityAttr;

			$entityKey = "{$entityPrefix}_{$entityID}";
			if(!isset(self::$ENTITY_ATTRS[$entityKey]))
			{
				self::$ENTITY_ATTRS[$entityKey] = array();
			}
			self::$ENTITY_ATTRS[$entityKey][] = $entityAttr;
		}
		return $arResult;
	}
	static public function UpdateEntityAttr($entityType, $entityID, $arAttrs = array())
	{
		global $DB;
		$entityID = intval($entityID);
		$entityType = strtoupper($entityType);

		if(!is_array($arAttrs))
		{
			$arAttrs = array();
		}

		/*if(!is_array($arOptions))
		{
			$arOptions = array();
		}*/

		$key = "{$entityType}_{$entityID}";
		if(isset(self::$ENTITY_ATTRS[$key]))
		{
			unset(self::$ENTITY_ATTRS[$key]);
		}

		$entityType = $DB->ForSql($entityType);
		$sQuery = "DELETE FROM b_ynsir_entity_perms WHERE ENTITY = '{$entityType}' AND ENTITY_ID = {$entityID} AND (TYPE IS NULL OR TYPE = \"\")";;
		$DB->Query($sQuery, false, $sQuery.'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		if (!empty($arAttrs))
		{
			foreach ($arAttrs as $sAttr)
			{
				$sQuery = "INSERT INTO b_ynsir_entity_perms(ENTITY, ENTITY_ID, ATTR) VALUES ('{$entityType}', {$entityID}, '".$DB->ForSql($sAttr)."')";
				$DB->Query($sQuery, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			}
		}
	}
	static public function DeleteEntity($entityType, $entityID, $typeAttr = '',$sourceType = '')
	{
		global $DB;
		$entityID = intval($entityID);
		$entityType = strtoupper($entityType);

		$entityType = $DB->ForSql($entityType);
		$sQuery = "DELETE FROM b_ynsir_entity_perms WHERE ENTITY = '{$entityType}' AND ENTITY_ID = {$entityID} AND TYPE = \"{$typeAttr}\" AND SOURCE = \"{$sourceType}\"";
		$DB->Query($sQuery, false, $sQuery.'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
	}

	static public function AddEntityAttr($entityType, $entityID, $arAttrs = array(),$typeAttr = '',$sourceType = '')
	{
		global $DB;
		$entityID = intval($entityID);
		$entityType = strtoupper($entityType);

		if(!is_array($arAttrs))
		{
			$arAttrs = array();
		}

		$entityType = $DB->ForSql($entityType);

		if (!empty($arAttrs))
		{
			foreach ($arAttrs as $sAttr)
			{
				$sQuery = "INSERT INTO b_ynsir_entity_perms(ENTITY, ENTITY_ID, ATTR, TYPE, SOURCE) VALUES ('{$entityType}', {$entityID}, '".$DB->ForSql($sAttr)."','".$DB->ForSql($typeAttr)."','".$DB->ForSql($sourceType)."')";
				$DB->Query($sQuery, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			}
		}
	}

	public static function ResolvePermissionEntityType($entityType, $entityID, array $parameters = null)
	{
		return false;
	}

	public static function HasPermissionEntityType($permissionEntityType)
	{

		$entityTypeID = YNSIROwnerType::ResolveID($permissionEntityType);
		return ($entityTypeID !== YNSIROwnerType::Undefined && $entityTypeID !== YNSIROwnerType::System);
	}

	public static function ResolveEntityTypeName($permissionEntityType)
	{
//		if(DealCategory::hasPermissionEntity($permissionEntityType))
//		{
//			return CCrmOwnerType::DealName;
//		}

		return $permissionEntityType;
	}
}
