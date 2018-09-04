<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$currentEntityId = !empty($currentValues['CANDIDATE_ID']) ? $currentValues['CANDIDATE_ID'] : '';
?>

<tbody id="crm_entity_base_form">
<tr>
	<td align="right" width="40%">
		<span style="font-weight: bold"><?=GetMessage("YNSIR_CACTIVITY_LABLE_ENTITY_ID")?></span>
	</td>
	<td width="60%">
		<input type="text" name="CANDIDATE_ID" id="id_entity" value="<?=htmlspecialcharsbx($currentEntityId)?>" size="20">
		<input type="button" value="..." onclick="BPAShowSelector('id_entity', 'string');">
	</td>
</tr>
</tbody>
