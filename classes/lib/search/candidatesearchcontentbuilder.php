<?php
namespace Bitrix\YNSIR\Search;
use \Bitrix\Crm\LeadTable;
class YNSIRCandidateSearchContentBuilder extends SearchContentBuilder
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}
	public function isFullTextSearchEnabled()
	{
		return LeadTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT');
	}
	protected function prepareEntityFields($entityID)
	{
		$dbResult = \CCrmLead::GetListEx(
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

		$operation = $this->isFullTextSearchEnabled() ? '*' : '*%';
		return array("{$operation}SEARCH_CONTENT" => YNSIRSearchEnvironment::prepareToken($value));
	}
	/**
	 * Prepare search map.
	 * @param array $fields Entity Fields.
	 * @return SearchMap
	 */
	protected function prepareSearchMap(array $fields)
	{
		$map = new SearchMap();

		$entityID = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if($entityID <= 0)
		{
			return $map;
		}

		$map->add($entityID);
		$map->addField($fields, 'ID');
		$map->addField($fields, 'TITLE');

		$map->addField($fields, 'LAST_NAME');
		$map->addField($fields, 'NAME');
		$map->addField($fields, 'SECOND_NAME');

		$map->addField($fields, 'OPPORTUNITY');
		$map->add(
			\CCrmCurrency::GetCurrencyName(
				isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
			)
		);

		if(isset($fields['ASSIGNED_BY_ID']))
		{
			$map->addUserByID($fields['ASSIGNED_BY_ID']);
		}

		$multiFields = $this->getEntityMultiFields($entityID);
		if(isset($multiFields[\CCrmFieldMulti::PHONE]))
		{
			foreach($multiFields[\CCrmFieldMulti::PHONE] as $multiField)
			{
				if(isset($multiField['VALUE']))
				{
					$map->addPhone($multiField['VALUE']);
				}
			}
		}
		if(isset($multiFields[\CCrmFieldMulti::EMAIL]))
		{
			foreach($multiFields[\CCrmFieldMulti::EMAIL] as $multiField)
			{
				if(isset($multiField['VALUE']))
				{
					$map->addEmail($multiField['VALUE']);
				}
			}
		}

		return $map;
	}
	protected function save($entityID, SearchMap $map)
	{
		LeadTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}
}