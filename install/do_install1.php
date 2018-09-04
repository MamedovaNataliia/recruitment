<?if(!check_bitrix_sessid()) return;?>
<?
IncludeModuleLangFile(__FILE__);
if (!empty($GLOBALS['errors']))
	echo CAdminMessage::ShowMessage($GLOBALS['errors']);
else
	echo CAdminMessage::ShowNote(GetMessage('MOD_YNSIR_INST_OK'));
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage('MOD_YNSIR_BACK')?>">	
<form>