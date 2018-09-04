<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPEAA_PD_ENTITY_ID") ?>:</span></td>
	<td width="60%">
        <input type="text" name="entity_ID" id="entity_ID" value="<?=htmlspecialcharsbx($arCurrentValues["entity_ID"])?>" size="20">
        <input type="button" value="..." onclick="BPAShowSelector('entity_ID', 'string');">
    </td>
</tr><tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPEAA_PD_ENTITY_TYPE") ?>:</span></td>
	<td width="60%">
		<select name="entity_type">
			<option value=""></option>
			<?
			$fl = false;
			foreach ($arEntityTypes as $key => $value)
			{
				if ($key == $arCurrentValues["entity_type"])
					$fl = true;
				?><option value="<?= htmlspecialcharsbx($key) ?>"<?= ($key == $arCurrentValues["entity_type"]) ? " selected" : "" ?>><?= htmlspecialcharsbx($value) ?></option><?
			}
			?>
		</select>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPEAA_PD_TYPE") ?>:</span></td>
	<td width="60%">
		<select name="event_type">
			<option value=""></option>
			<?
			$fl = false;
			foreach ($arTypes as $key => $value)
			{
				if ($key == $arCurrentValues["event_type"])
					$fl = true;
				?><option value="<?= htmlspecialcharsbx($key) ?>"<?= ($key == $arCurrentValues["event_type"]) ? " selected" : "" ?>><?= htmlspecialcharsbx($value) ?></option><?
			}
			?>
		</select>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPEAA_PD_MESSAGE") ?>:</span></td>
	<td width="60%">
		<textarea name="event_text" id="id_event_text" rows="7" cols="40"><?= htmlspecialcharsbx($arCurrentValues["event_text"]) ?></textarea>
		<input style="vertical-align: top" type="button" value="..." onclick="BPAShowSelector('id_event_text', 'string');">
	</td>
</tr>