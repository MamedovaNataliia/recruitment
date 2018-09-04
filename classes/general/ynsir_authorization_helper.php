<?php
class YNSIRAuthorizationHelper
{
	private static $USER_PERMISSIONS = null;

	public static function GetUserPermissions()
	{
		if(self::$USER_PERMISSIONS === null)
		{
			self::$USER_PERMISSIONS = YNSIRPerms_::GetCurrentUserPermissions();
		}

		return self::$USER_PERMISSIONS;
	}

	public static function CheckCreatePermission($enitityTypeName, $userPermissions = null)
	{
		$enitityTypeName = strval($enitityTypeName);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return !$userPermissions->HavePerm($enitityTypeName, YNSIR_PERM_NONE, 'ADD');
	}
    public static function CheckCreatePermissionSec($enitityTypeName, $userPermissions = null,$SECTION)
    {
        $enitityTypeName = strval($enitityTypeName);

        if(!$userPermissions)
        {
            $userPermissions = self::GetUserPermissions();
        }

        return !$userPermissions->HavePermSec($enitityTypeName, YNSIR_PERM_NONE, 'ADD',$SECTION);
    }

	public static function CheckUpdatePermission($enitityTypeName, $entityID, $userPermissions = null, $entityAttrs = null)
	{
		$enitityTypeName = strval($enitityTypeName);
		$entityID = intval($entityID);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		if($entityID <= 0)
		{
			return !$userPermissions->HavePerm($enitityTypeName, YNSIR_PERM_NONE, 'WRITE');
		}

		if(!is_array($entityAttrs))
		{
			$entityAttrs = $userPermissions->GetEntityAttr($enitityTypeName, $entityID);
		}
		return !$userPermissions->HavePerm($enitityTypeName, YNSIR_PERM_NONE, 'WRITE')
			&& $userPermissions->CheckEnityAccess($enitityTypeName, 'WRITE', isset($entityAttrs[$entityID]) ? $entityAttrs[$entityID] : array());
	}
	public static function CheckUpdatePermissionSec($SECTION,$enitityTypeName, $entityID, $userPermissions = null, $entityAttrs = null)
	{
		$enitityTypeName = strval($enitityTypeName);
		$entityID = intval($entityID);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		if($entityID <= 0)
		{
			return !$userPermissions->HavePermSec($enitityTypeName, YNSIR_PERM_NONE, 'WRITE',$SECTION);
		}

		if(!is_array($entityAttrs))
		{
			$entityAttrs = $userPermissions->GetEntityAttr($enitityTypeName, $entityID);
		}
		return !$userPermissions->HavePermSec($enitityTypeName, YNSIR_PERM_NONE, 'WRITE',$SECTION)
			&& $userPermissions->CheckEnityAccess($enitityTypeName, 'WRITE', isset($entityAttrs[$entityID]) ? $entityAttrs[$entityID] : array(),$SECTION);
	}

	public static function CheckDeletePermission($enitityTypeName, $entityID, $userPermissions = null, $entityAttrs = null,$SECTION = '')
	{
		$enitityTypeName = strval($enitityTypeName);
		$entityID = intval($entityID);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

        if(strlen($SECTION) > 0) {
            $havePerm = !$userPermissions->HavePermSec($enitityTypeName, YNSIR_PERM_NONE, 'DELETE',$SECTION);
        } else {
            $havePerm = !$userPermissions->HavePerm($enitityTypeName, YNSIR_PERM_NONE, 'DELETE');
        }

		if($entityID <= 0)
		{
			return $havePerm;
		}

		if(!is_array($entityAttrs))
		{
			$entityAttrs = $userPermissions->GetEntityAttr($enitityTypeName, $entityID);
		}

		return $havePerm
			&& $userPermissions->CheckEnityAccess($enitityTypeName, 'DELETE', isset($entityAttrs[$entityID]) ? $entityAttrs[$entityID] : array(), $SECTION);
	}

	public static function CheckReadPermission($enitityType, $entityID, $userPermissions = null, $entityAttrs = null,$SECTION = '')
	{
		$enitityTypeName = is_numeric($enitityType)
			? YNSIROwnerType::ResolveName($enitityType)
			: strtoupper(strval($enitityType));

		$entityID = intval($entityID);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

        if(strlen($SECTION) > 0) {
            $havePerm = !$userPermissions->HavePermSec($enitityTypeName, YNSIR_PERM_NONE, 'READ',$SECTION);
        } else {
		    $havePerm = !$userPermissions->HavePerm($enitityTypeName, YNSIR_PERM_NONE, 'READ');
        }

		if($entityID <= 0)
		{
			return $havePerm;
		}

		if(!is_array($entityAttrs))
		{
			$entityAttrs = $userPermissions->GetEntityAttr($enitityTypeName, $entityID);
		}
		return $havePerm
			&& $userPermissions->CheckEnityAccess($enitityTypeName, 'READ', isset($entityAttrs[$entityID]) ? $entityAttrs[$entityID] : array(),$SECTION);
	}

	public static function CheckImportPermission($enitityType, $userPermissions = null)
	{
		$enitityTypeName = is_numeric($enitityType)
			? YNSIROwnerType::ResolveName($enitityType)
			: strtoupper(strval($enitityType));

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return !$userPermissions->HavePerm($enitityTypeName, YNSIR_PERM_NONE, 'IMPORT');
	}

	public static function CheckExportPermission($enitityType, $userPermissions = null)
	{
		$enitityTypeName = is_numeric($enitityType)
			? YNSIROwnerType::ResolveName($enitityType)
			: strtoupper(strval($enitityType));

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return !$userPermissions->HavePerm($enitityTypeName, YNSIR_PERM_NONE, 'EXPORT');
	}

	public static function CheckConfigurationUpdatePermission($userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckConfigurationReadPermission($userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}

	public static function CanEditOtherSettings($user = null)
	{
		if(!($user !== null && ((get_class($user) === 'CUser') || ($user instanceof CUser))))
		{
			$user = YNSIRSecurityHelper::GetCurrentUser();
		}
		return $user->CanDoOperation('edit_other_settings');
	}
}
