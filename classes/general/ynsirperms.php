<?php
/**
* YouNet SI
*/
class YNSIRPerms
{
	public static function havePermsConfig(){
		global $USER;
		$bResult = false;
		if($USER->IsAdmin())
			$bResult = true;
		else {
			$iId = $USER->GetID();
			$arPerm = YNSIRRole::GetUserPerms($iId);
			if(isset($arPerm['CONFIG']['WRITE']['-']) && $arPerm['CONFIG']['WRITE']['-'] == 'X'){
				$bResult = true;
			}
		}
		return $bResult;
	}
}
?>