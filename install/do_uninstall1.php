<?php
IncludeModuleLangFile(__FILE__);
?>
<form action="<?=$APPLICATION->GetCurPage()?>" onsubmit="this['inst'].disabled=true; return true;">
<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="hidden" name="id" value="ynsirecruitment">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
	<?=CAdminMessage::ShowMessage(GetMessage("MOD_YNSIR_UNINST_WARN"))?>
	<p><?=GetMessage("MOD_YNSIR_UNINST_SAVE")?>:</p>
	<p><input type="checkbox" name="savedata" id="savedata" value="Y" checked><label for="savedata"><?=GetMessage("MOD_YNSIR_UNINST_SAVE_TABLES")?></label></p>
	<input type="submit" name="inst" value="<?=GetMessage("MOD_YNSIR_UNINST_UNINSTALL")?>" />
</form>