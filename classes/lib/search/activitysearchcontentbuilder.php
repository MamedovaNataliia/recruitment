<?php
namespace Bitrix\YNSIR\Search;
use Bitrix\YNSIR\YNSIRActivityTable;
class YNSIRActivitySearchContentBuilder extends YNSIRSearchContentBuilder
{
	public function getEntityTypeID()
	{
		return \YNSIROwnerType::Activity;
	}
	protected function prepareEntityFields($entityID)
	{
		$dbResult = \YNSIRActivity::GetList(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*'/*, 'UF_*'*/)
		);

		$fields = $dbResult->Fetch();
		return is_array($fields) ? $fields : null;
	}
	public function prepareEntityFilter(array $params)
	{
		$value = isset($params['SEARCH_CONTENT']) ? $params['SEARCH_CONTENT'] : '';
		if(!is_string($value) || $value === '')
		{
			return array();
		}

		$operation = '*%';
		return array("{$operation}SEARCH_CONTENT" => YNSIRSearchEnvironment::prepareToken($value));
	}
	/**
	 * Prepare search map.
	 * @param array $fields Entity Fields.
	 * @return YNSIRSearchMap
	 */
	protected function prepareSearchMap(array $fields)
	{
		$map = new YNSIRSearchMap();

		$entityID = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if($entityID <= 0)
		{
			return $map;
		}

		$map->add($entityID);
		$map->addField($fields, 'ID');
		$map->addField($fields, 'SUBJECT');

		$description = isset($fields['DESCRIPTION']) ? trim($fields['DESCRIPTION']) : '';
		if($description !== '')
		{
			$descriptionType = isset($fields['DESCRIPTION_TYPE'])
				? (int)$fields['DESCRIPTION_TYPE'] : \YNSIRContentType::PlainText;

			if($descriptionType === \YNSIRContentType::Html)
			{
				$description = strip_tags(
					preg_replace("/<br(\s*\/\s*)?>/i", ' ', $description)
				);
			}
			elseif($descriptionType === \YNSIRContentType::BBCode)
			{
				$parser = new \CTextParser();
				$parser->allow['SMILES'] = 'N';
				$description = strip_tags(
					preg_replace(
						"/<br(\s*\/\s*)?>/i",
						' ',
						$parser->convertText($description)
					)
				);
			}

			$map->addText($description, 256);
		}

		if(isset($fields['RESPONSIBLE_ID']))
		{
			$map->addUserByID($fields['RESPONSIBLE_ID']);
		}

		//region Bindings
		$bindings = \YNSIRActivity::GetBindings($entityID);
		if(is_array($bindings))
		{
			foreach($bindings as $binding)
			{
				$ownerID = isset($binding['OWNER_ID'])
					? (int)$binding['OWNER_ID'] : 0;
				$ownerTypeID = isset($binding['OWNER_TYPE_ID'])
					? (int)$binding['OWNER_TYPE_ID'] : \YNSIROwnerType::Undefined;
				if($ownerID > 0 && \YNSIROwnerType::IsDefined($ownerTypeID))
				{
					$map->add(
						\YNSIROwnerType::GetCaption($ownerTypeID, $ownerID, false)
					);
				}
			}
		}
		//endregion

		return $map;
	}
	protected function save($entityID, YNSIRSearchMap $map)
	{
		YNSIRActivityTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}
}