<?php
namespace Bitrix\YNSIR\Search;
use Bitrix\YNSIR\YNSIRJobOrderTable;
class YNSIRJobOrderSearchContentBuilder extends YNSIRSearchContentBuilder
{
	public function getEntityTypeID()
	{
		return \YNSIROwnerType::Activity;
	}
	protected function prepareEntityFields($entityID)
	{
		$dbResult = \YNSIRJobOrder::GetList(
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
		$map->addField($fields, 'TITLE');
		$map->addField($fields, 'VACANCY_REASON');
		$map->addField($fields, 'DESCRIPTION');

		if(isset($fields['CREATED_BY']))
		{
			$map->addUserByID($fields['CREATED_BY']);
		}
		if(isset($fields['MODIFIED_BY']))
		{
			$map->addUserByID($fields['MODIFIED_BY']);
		}
		if(isset($fields['SUPERVISOR']))
		{
			$map->addUserByID($fields['SUPERVISOR']);
		}
		if(isset($fields['OWNER']))
		{
			$map->addUserByID($fields['OWNER']);
		}
		if(isset($fields['RECRUITER']))
		{
			$map->addUserByID($fields['RECRUITER']);
		}
		if(isset($fields['SUBORDINATE']))
		{
			$map->addUserByID($fields['SUBORDINATE']);
		}
		if(isset($fields['INTERVIEW']))
		{
			$map->addUserByID($fields['INTERVIEW']);
		}

		return $map;
	}
	protected function save($entityID, YNSIRSearchMap $map)
	{
        YNSIRJobOrderTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}
}