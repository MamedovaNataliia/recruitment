<?if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);
if($GLOBALS["errors"]===false):
	CAdminMessage::ShowNote(GetMessage("MOD_YNSIR_UNINST_OK"));
else:
	CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>GetMessage("MOD_YNSIR_UNINST_ERR"), "DETAILS"=>implode("<br>", $GLOBALS["errors"]), "HTML"=>true));
endif;
?><form action="<?=$APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="submit" name="" value="<?=GetMessage("MOD_YNSIR_BACK")?>">
<form>