<?
define('MODULE_ID', 'ynsirecruitment');
if (!empty($_REQUEST['entity']))
	define('ENTITY', $_REQUEST['entity']);
else
	define('ENTITY', 'YNSIRDocumentJobOrder');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bizprocdesigner/admin/bizproc_activity_settings.php');
?>