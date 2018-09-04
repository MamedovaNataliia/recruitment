<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */

/**
 * Bitrix vars
 *
 * @global CUser $USER
 */

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if($USER->IsAuthorized() && check_bitrix_sessid())
{
	$guid = isset($_REQUEST['guid']) ? $_REQUEST['guid'] : '';
	if($guid === '')
	{
		echo 'ERROR: GUID IS EMPTY.';
		die();
	}

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	if($action === 'saveconfig')
	{
		$guid = $_POST['guid'];

		$options = CUserOptions::GetOption('ynsirecruitment.job_order.detail.quickpanelview', $guid, array());

		CUtil::decodeURIComponent($_POST);
		$config = isset($_POST['config']) && is_array($_POST['config']) ? $_POST['config'] : array();

		if(isset($config['enabled']))
		{
			$options['enabled'] = $config['enabled'] === 'Y' ? 'Y' : 'N';
		}

		if(isset($config['expanded']))
		{
			$options['expanded'] = $config['expanded'] === 'Y' ? 'Y' : 'N';
		}

		if(isset($config['fixed']))
		{
			$options['fixed'] = $config['fixed'] === 'Y' ? 'Y' : 'N';
		}

		if(isset($config['left']) && is_string($config['left']))
		{
			$options['left'] = $config['left'];
		}

		if(isset($config['center']) && is_string($config['center']))
		{
			$options['center'] = $config['center'];
		}

		if(isset($config['right']) && is_string($config['right']))
		{
			$options['right'] = $config['right'];
		}

		if(isset($config['bottom']) && is_string($config['bottom']))
		{
			$options['bottom'] = $config['bottom'];
		}

		if(isset($_POST['forAllUsers']) && $_POST['forAllUsers'] === 'Y')
		{
			if(isset($_POST['delete']) && $_POST['delete'] === 'Y')
			{
				CUserOptions::DeleteOptionsByName('ynsirecruitment.job_order.detail.quickpanelview', $guid);
			}
			CUserOptions::SetOption('ynsirecruitment.job_order.detail.quickpanelview', $guid, $options, true);
		}
		CUserOptions::SetOption('ynsirecruitment.job_order.detail.quickpanelview', $guid, $options);
	}
	elseif($action == 'resetconfig')
	{
		if(isset($_POST['forAllUsers']) && $_POST['forAllUsers'] === 'Y')
		{
			CUserOptions::DeleteOptionsByName('ynsirecruitment.job_order.detail.quickpanelview', $guid);
		}
		else
		{
			CUserOptions::DeleteOption('ynsirecruitment.job_order.detail.quickpanelview', $guid);
		}
	}
	else
	{
		echo 'ERROR: ACTION IS EMPTY OR NOT SUPPORTED.';
		die();
	}
}
echo 'OK';
