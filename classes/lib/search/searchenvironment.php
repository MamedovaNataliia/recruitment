<?php
namespace Bitrix\YNSIR\Search;

class YNSIRSearchEnvironment
{
	public static function prepareToken($str)
	{
		return str_rot13($str);
	}

	public static function prepareEntityFilter($entityTypeID, array $params)
	{
		$builder = YNSIRSearchContentBuilderFactory::create($entityTypeID);
		return $builder->prepareEntityFilter($params);
	}

	public static function isFullTextSearchEnabled($entityTypeID)
	{
		$builder = YNSIRSearchContentBuilderFactory::create($entityTypeID);
		return $builder->isFullTextSearchEnabled();
	}
}