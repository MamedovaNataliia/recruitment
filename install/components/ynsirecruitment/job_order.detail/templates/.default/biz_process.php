<?php
/**
 * Created by PhpStorm.
 * User: nhatth
 * Date: 10/11/17
 * Time: 4:20 PM
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->RestartBuffer();

$APPLICATION->IncludeComponent('bitrix:bizproc.workflow.start',
    'ynsir.custom.view',
    Array(
        'MODULE_ID' => 'ynsirecruitment',
        'ENTITY' => 'YNSIRDocumentJobOrder',
        'DOCUMENT_TYPE' => YNSIR_JOB_ORDER,
        'DOCUMENT_ID' => YNSIR_JOB_ORDER.'_'.$arResult['ID'],
        'TEMPLATE_ID' => $_REQUEST['workflow_template_id'],
        'SET_TITLE'	=>	'Y'
    ),
    $component,
    array('HIDE_ICONS' => 'Y')
);
die;