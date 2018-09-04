<?
if (!CModule::IncludeModule('bizproc'))
	return;
if (!CModule::IncludeModule('ynsirecruitment'))
	return;

IncludeModuleLangFile(dirname(__FILE__)."/crm_document.php");

class YNSIRDocumentJobOrder extends YNSIRDocument
	implements IBPWorkflowDocument
{
	static public function GetDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arResult = self::getEntityFields($arDocumentID['TYPE']);

		return $arResult;
	}

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.
			strtolower($entityType).'.edit/component.php');

//		$addressLabels = Bitrix\Crm\EntityAddress::getShortLabels();
		$printableFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_TEXT').')';
		$emailFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_EMAIL').')';
		$arResult = array(
			'ID' => array(
				'Name' => GetMessage('YNSIR_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'TITLE' => array(
				'Name' => GetMessage('YNSIR_FIELD_TITLE'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			),
			'STATUS' => array(
				'Name' => GetMessage('YNSIR_FIELD_STATUS_ID'),
				'Type' => 'select',
				'Options' => YNSIRGeneral::getListJobStatus(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
            'CREATED_BY' => array(
				'Name' => GetMessage('YNSIR_FIELD_CREATED_BY_ID'),
				'Type' => 'user',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
            "MODIFIED_BY" => array(
                "Name" => GetMessage("YNSIR_FIELD_MODIFIED_BY_ID"),
                "Type" => "user",
                "Filterable" => true,
                "Editable" => false,
                "Required" => false,
            ),
			"DATE_CREATE" => array(
				"Name" => GetMessage("YNSIR_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_MODIFY" => array(
				"Name" => GetMessage("YNSIR_FIELD_DATE_MODIFY"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"SALARY_FROM" => array(
				"Name" => GetMessage("YNSIR_FIELD_SALARY_FROM"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"SALARY_TO" => array(
				"Name" => GetMessage("YNSIR_FIELD_SALARY_TO"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
		);

		return $arResult;
	}

	static public function PrepareDocument(array &$arFields)
	{
		$stuses = CCrmStatus::GetStatusList('STATUS');
		$statusID = isset($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : '';
		$arFields['STATUS_ID_PRINTABLE'] = $statusID !== '' && isset($stuses[$statusID]) ? $stuses[$statusID] : '';
		$arFields['FULL_ADDRESS'] = Bitrix\Crm\Format\LeadAddressFormatter::format(
			$arFields,
			array('SEPARATOR' => Bitrix\Crm\Format\AddressSeparator::Comma)
		);
	}

	static public function CreateDocument($parentDocumentId, $arFields)
	{
		if(!is_array($arFields))
		{
			throw new Exception("Entity fields must be array");
		}

		global $DB;
		$arDocumentID = self::GetDocumentInfo($parentDocumentId);
		if ($arDocumentID == false)
			$arDocumentID['TYPE'] = $parentDocumentId;

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$fieldType = $arDocumentFields[$key]["Type"];
			if (in_array($fieldType, array("phone", "email", "im", "web"), true))
			{
				YNSIRDocument::PrepareEntityMultiFields($arFields, strtoupper($fieldType));
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			if ($fieldType == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, "LEAD_0");
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($fieldType == "select" && substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField(YNSIR_JOB_ORDER, $arFields, $key);
			}
			elseif ($fieldType == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
					$value = $file;
				}
				unset($value);
			}
			elseif ($fieldType == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		$DB->StartTransaction();

		if(isset($arFields['COMMENTS']))
		{
			if(preg_match('/<[^>]+[\/]?>/i', $arFields['COMMENTS']) === 1)
			{
				$arFields['COMMENTS'] = htmlspecialcharsbx($arFields['COMMENTS']);
			}
			$arFields['COMMENTS'] = str_replace(array("\r\n", "\r", "\n"), "<br>", $arFields['COMMENTS']);
		}

		$CCrmEntity = new CCrmLead(false);
		$id = $CCrmEntity->Add(
			$arFields,
			true,
			array('REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => static::getSystemUserId())
		);

		if (!$id || $id <= 0)
		{
			$DB->Rollback();
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('LEAD');
			if (false === $CCrmBizProc->CheckFields(false, true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($id && $id > 0 && !$CCrmBizProc->StartWorkflow($id))
			{
				$DB->Rollback();
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		//Region automation
		\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $id);
		//End region

		if ($id && $id > 0)
			$DB->Commit();

		return $id;
	}

	static public function UpdateDocument($documentId, $arFields)
	{
		global $DB;

		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		if(!CCrmLead::Exists($arDocumentID['ID']))
		{
			throw new Exception(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));
		}

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$fieldType = $arDocumentFields[$key]["Type"];
			if (in_array($fieldType, array("phone", "email", "im", "web"), true))
			{
				YNSIRDocument::PrepareEntityMultiFields($arFields, strtoupper($fieldType));
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			if ($fieldType == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, $documentId);
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($fieldType == "select" && substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField(YNSIR_JOB_ORDER, $arFields, $key);
			}
			elseif ($fieldType == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
					$value = $file;
				}
				unset($value);
			}
			elseif ($fieldType == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if(isset($arFields['COMMENTS']) && $arFields['COMMENTS'] !== '')
		{
			$arFields['COMMENTS'] = preg_replace("/[\r\n]+/".BX_UTF_PCRE_MODIFIER, "<br/>", $arFields['COMMENTS']);
		}

		//check STATUS_ID changes
		$statusChanged = false;
		if (isset($arFields['STATUS_ID']))
		{
			$dbDocumentList = CCrmLead::GetListEx(
				array(),
				array('ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'STATUS_ID')
			);
			$arPresentFields = $dbDocumentList->Fetch();
			if ($arPresentFields['STATUS_ID'] != $arFields['STATUS_ID'])
				$statusChanged = true;
		}

		$DB->StartTransaction();

		$CCrmEntity = new CCrmLead(false);
		$res = $CCrmEntity->Update(
			$arDocumentID['ID'],
			$arFields,
			true,
			true,
			array('REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => static::getSystemUserId())
		);

		if (!$res)
		{
			$DB->Rollback();
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('LEAD');
			if (false === $CCrmBizProc->CheckFields($arDocumentID['ID'], true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($res && !$CCrmBizProc->StartWorkflow($arDocumentID['ID']))
			{
				$DB->Rollback();
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		//Region automation
		if ($statusChanged)
			\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $arDocumentID['ID']);
		//End region

		if ($res)
			$DB->Commit();
	}

	public function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		$dbDocumentList = CCrmLead::GetListEx(
			array(),
			array('ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('TITLE')
		);
		if ($arPresentFields = $dbDocumentList->Fetch())
		{
			return $arPresentFields['TITLE'];
		}

		return null;
	}
}
