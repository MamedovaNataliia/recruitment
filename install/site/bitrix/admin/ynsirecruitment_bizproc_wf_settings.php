<?
define('MODULE_ID', 'ynsirecruitment');
if (!empty($_REQUEST['entity']))
	define('ENTITY', $_REQUEST['entity']);
else
	define('ENTITY', 'YNSIRDocumentJobOrder');

define('DISABLE_BIZPROC_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bizprocdesigner/admin/bizproc_wf_settings.php');
?>