<?php
namespace Bitrix\YNSIR\Search;
use Bitrix\Main;
class YNSIRSearchContentBuilderFactory
{
	public static function create($entityTypeID)
	{
		if($entityTypeID === \YNSIROwnerType::Candidate)
		{
			return new YNSIRCandidateSearchContentBuilder();
		}
		if($entityTypeID === \YNSIROwnerType::Order)
		{
			return new YNSIRJobOrderSearchContentBuilder();
		}
		elseif($entityTypeID === \YNSIROwnerType::Activity)
		{
			return new YNSIRActivitySearchContentBuilder();
		}
		else
		{
			throw new Main\NotSupportedException("Type: '".\YNSIROwnerType::resolveName($entityTypeID)."' is not supported in current context");
		}
	}
}