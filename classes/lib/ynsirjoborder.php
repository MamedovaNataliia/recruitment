<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\YNSIR;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class YNSIRJobOrderTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_ynsir_job_order';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'CREATED_BY' => array(
				'data_type' => 'integer'
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer'
			),
            'DATE_CREATE' => array(
                'data_type' => 'datetime'
            ),
            'DATE_MODIFY' => array(
                'data_type' => 'datetime'
            ),
            'HEADCOUNT' => array(
                'data_type' => 'integer'
            ),
            'TITLE' => array(
                'data_type' => 'string'
            ),
            'DEPARTMENT' => array(
                'data_type' => 'integer'
            ),
            'EXPECTED_END_DATE' => array(
                'data_type' => 'datetime'
            ),
            'STATUS' => array(
                'data_type' => 'string'
            ),
            'VACANCY_REASON' => array(
                'data_type' => 'string'
            ),
            'IS_REPLACE' => array(
                'data_type' => 'integer'
            ),
            'SALARY_FROM' => array(
                'data_type' => 'integer'
            ),
            'SALARY_TO' => array(
                'data_type' => 'integer'
            ),
            'TEMPLATE_ID' => array(
                'data_type' => 'integer'
            ),
            'EXTEND_TEMPLATE' => array(
                'data_type' => 'integer'
            ),
            'DESCRIPTION' => array(
                'data_type' => 'string'
            ),
            'SEARCH_CONTENT' => array(
                'data_type' => 'string'
            ),
            'ACTIVE' => array(
                'data_type' => 'integer'
            )
		);
	}
}
