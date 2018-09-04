<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CUtil::InitJSCore();
?>
<form action="<?=POST_FORM_ACTION_URI?>" name="crmPermForm" method="POST">
	<input type="hidden" id="ROLE_ACTION" name="save" value=""/>
	<input type="hidden" name="ROLE_ID" value="<?=$arResult['ROLE']['ID']?>"/>
	<?=bitrix_sessid_post()?>
	<?=GetMessage('YNSIR_PERMS_FILED_NAME')?>: <input name="NAME" value="<?=htmlspecialcharsbx($arResult['ROLE']['NAME'])?>" class="crmPermRoleName"/>
	<br/>
	<br/>
	<table width="100%" cellpadding="0" cellspacing="0" class="crmPermRoleTable" id="crmPermRoleTable" >
		<tr>
			<th><?=GetMessage('YNSIR_PERMS_HEAD_ENTITY')?></th>
			<?php
			foreach ($arResult['FULL_PERMS'] as $iKeyPerms => $sTextPerms) {
				?>
				<th><?=$sTextPerms?></th>
				<?php
			}
			?>
		</tr>
		<? foreach ($arResult['ENTITY'] as $entityType => $entityName): ?>
		<tr>
			<td><? if (isset($arResult['ENTITY_FIELDS'][$entityType])): ?><a href="javascript:void(0)" class="crmPermRoleTreePlus" onclick="CrmPermRoleShowRow(this)"></a><?endif;?><?=$entityName?></td>
			<?php
			foreach ($arResult['FULL_PERMS'] as $iKP => $sPerms) {
				?>
				<td>
					<? if (in_array($iKP, $arResult['ENTITY_PERMS'][$entityType])): ?>
					<span id="divPermsBox<?=$entityType?><?=$iKP?>" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType][$iKP]['-']]?></span>
					<span id="divPermsBox<?=$entityType?><?=$iKP?>_Select" style="display:none">
						<select id="divPermsBox<?=$entityType?><?=$iKP?>_SelectBox" name="ROLE_PERMS[<?=$entityType?>][<?=$iKP?>][-]">
						<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
							<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType][$iKP]['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
						<? endforeach; ?>
						</select>
					</span>
					<? endif; ?>
				</td>
				<?php
			}
			?>
		</tr>
		<?	if (isset($arResult['ENTITY_FIELDS'][$entityType])):
				foreach ($arResult['ENTITY_FIELDS'][$entityType] as $fieldID => $arFieldValue):
					foreach ($arFieldValue as $fieldValueID => $fieldValue):
		?>
		<tr class="crmPermRoleFields" style="display:none">
			<td><?=$fieldValue?></td>
			<?php
			foreach ($arResult['FULL_PERMS'] as $iKP => $sPerms) {
				?>
				<td>
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType][$iKP][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType][$iKP][$fieldID]))
							$sOrigPermAttr = $arResult['~ROLE_PERMS'][$entityType][$iKP][$fieldID][$fieldValueID];
					?>
					<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?><?=$iKP?>" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType][$iKP][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?><?=$iKP?>')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType][$iKP][$fieldID][$fieldValueID]]?></span>
					<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?><?=$iKP?>_Select" style="display:none">

						<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?><?=$iKP?>_SelectBox" name="ROLE_PERMS[<?=$entityType?>][<?=$iKP?>][<?=$fieldID?>][<?=$fieldValueID?>]">
							<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('YNSIR_PERMS_PERM_INHERIT')?></option>
						<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName):?>
							<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
						<? endforeach; ?>
						</select>
					</span>
				</td>
				<?php
			}
			?>
		</tr>
		<?
					endforeach;
				endforeach;
			endif;
		endforeach;
		?>
		<tr  class="ConfigEdit">
			<td colspan="<?=(sizeof($arResult['FULL_PERMS']) + 1)?>"><input name="ROLE_PERMS[CONFIG][WRITE][-]" <?=($arResult['ROLE_PERMS']['CONFIG']['WRITE']['-'] == 'X' ? 'checked="checked"' : '')?> value="X" id="crmConfigEdit" type="checkbox" /><label for="crmConfigEdit"><?=GetMessage("YNSIR_PERMS_PERM_ADD")?></label></td>
		</tr>
	</table>
	<br/>
	<div id="crmPermButtonBoxPlace">
		<? if ($arResult['ROLE']['ID'] > 0): ?>
		<div style="float:right; padding-right: 10px;"><a href="<?=$arResult['PATH_TO_ROLE_DELETE']?>" onclick="CrmRoleDelete('<?=CUtil::JSEscape(GetMessage('YNSIR_PERMS_DLG_TITLE'))?>', '<?=CUtil::JSEscape(GetMessage('YNSIR_PERMS_DLG_MESSAGE'))?>', '<?=CUtil::JSEscape(GetMessage('YNSIR_PERMS_DLG_BTN'))?>', '<?=CUtil::JSEscape($arResult['PATH_TO_ROLE_DELETE'])?>'); return false;" style="color:#E00000"><?=GetMessage('YNSIR_PERMS_ROLE_DELETE')?></a></div>
		<? endif;?>
		<div align="left">
			<input type="submit" name="save" value="<?=GetMessage('YNSIR_PERMS_BUTTONS_SAVE');?>"/>
			<input type="submit" naem="apply" value="<?=GetMessage('YNSIR_PERMS_BUTTONS_APPLY');?>" onclick="BX('ROLE_ACTION').name='apply'"/>
		</div>
	</div>
</form>
