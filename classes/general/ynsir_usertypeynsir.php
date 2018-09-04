<?

IncludeModuleLangFile(__FILE__);

class CUserTypeYNSIR extends CUserTypeString
{
	function GetUserTypeDescription()
	{
		return array(
			'USER_TYPE_ID' => 'ynsirecruitment',
			'CLASS_NAME' => 'CUserTypeYNSIR',
			'DESCRIPTION' => GetMessage('USER_TYPE_YNSIR_DESCRIPTION'),
			'BASE_TYPE' => 'string',
		);
	}

	function PrepareSettings($arUserField)
	{
		$entityType['CANDIDATE'] = $arUserField['SETTINGS']['CANDIDATE'] == 'Y'? 'Y': 'N';
		$entityType['JOBORDER'] = $arUserField['SETTINGS']['JOBORDER'] == 'Y'? 'Y': 'N';

		$iEntityType = 0;
		foreach($entityType as $result)
			if ($result == 'Y') $iEntityType++;

		$entityType['CANDIDATE'] = ($iEntityType == 0)? "Y": $entityType['CANDIDATE'];

		return array(
			'CANDIDATE'	 =>  $entityType['CANDIDATE'],
			'JOBORDER'	 =>  $entityType['JOBORDER'],
		);
	}

	function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';
		$entityTypeLead = 'Y';
		$entityTypeContact = 'Y';
		$entityTypeCompany = 'Y';
		$entityTypeDeal = 'Y';

		if($bVarsFromForm)
		{
			$entityTypeLead = $GLOBALS[$arHtmlControl['NAME']]['CANDIDATE'] == 'Y'? 'Y': 'N';
			$entityTypeDeal = $GLOBALS[$arHtmlControl['NAME']]['JOBORDER'] == 'Y'? 'Y': 'N';
		}
		elseif(is_array($arUserField))
		{
			$entityTypeLead = $arUserField['SETTINGS']['CANDIDATE'] == 'Y'? 'Y': 'N';
			$entityTypeDeal = $arUserField['SETTINGS']['JOBORDER'] == 'Y'? 'Y': 'N';
		}

		$result .= '
		<tr valign="top">
			<td>'.GetMessage("USER_TYPE_CRM_ENTITY_TYPE").':</td>
			<td>
				<input type="checkbox" name="'.$arHtmlControl["NAME"].'[CANDIDATE]" value="Y" '.($entityTypeLead=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_CANDIDATE').' <br/>
				<input type="checkbox" name="'.$arHtmlControl["NAME"].'[JOBORDER]" value="Y" '.($entityTypeDeal=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_JOBORDER').'<br/>
			</td>
		</tr>
		';
		return $result;
	}

	function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$arUserField['VALUE'] = $arHtmlControl['VALUE'];
		ob_start();
		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:system.field.edit',
			'crm',
			array('arUserField' => $arUserField),
			false,
			array('HIDE_ICONS' => 'Y')
		);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	function GetEditFormHTMLMulty($arUserField, $arHtmlControl)
	{
		return self::GetEditFormHTML($arUserField, $arHtmlControl);
	}

	function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return '<input type="text" '.
			'name="'.$arHtmlControl["NAME"].'" '.
			'size="'.$arUserField["SETTINGS"]["SIZE"].'" '.
			'value="'.$arHtmlControl["VALUE"].'"'.
			'>';
	}

	function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		if (strlen($arHtmlControl['VALUE'])>0)
			return $arHtmlControl['VALUE'];
		else
			return '&nbsp;';
	}

	function GetAdminListEditHTML($arUserField, $arHtmlControl)
	{
		return '<div>'.$arHtmlControl['VALUE'].'</div>';
	}

	function CheckFields($arUserField, $value)
	{
		$aMsg = array();

		return $aMsg;
	}

	function CheckPermission($arUserField, $userID = false)
	{
		//permission check is disabled
		if($userID === false)
		{
			return true;
		}

		if (!CModule::IncludeModule('ynsirecruitment'))
		{
			return false;
		}

		$userID = intval($userID);
		$userPerms = $userID > 0 ?
			YNSIRPerms_::GetUserPermissions($userID) : YNSIRPerms_::GetCurrentUserPermissions();

		return YNSIRPerms_::IsAccessEnabled($userPerms);
	}

	function OnSearchIndex($arUserField)
	{
		if(is_array($arUserField['VALUE']))
			return implode("\r\n", $arUserField['VALUE']);
		else
			return $arUserField['VALUE'];
	}

	static function GetShortEntityType($sEntity)
	{
		$sShortEntityType = '';
		switch ($sEntity)
		{
			case 'JOBORDER': $sShortEntityType = 'JO'; break;
			case 'CANDIDATE':
			default : $sShortEntityType = 'CA'; break;
		}
		return $sShortEntityType;
	}

	static function GetLongEntityType($sEntity)
	{
		$sLongEntityType = '';
		switch ($sEntity)
		{
			case 'JO': $sLongEntityType = 'JOBORDER'; break;
			case 'CA':
			default : $sLongEntityType = 'CANDIDATE'; break;
		}
		return $sLongEntityType;
	}
}

?>