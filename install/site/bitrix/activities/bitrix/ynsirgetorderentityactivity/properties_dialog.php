<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$currentEntityId = !empty($currentValues['EntityId']) ? $currentValues['EntityId'] : '';
?>

<tbody id="crm_entity_base_form">
<tr>
	<td align="right" width="40%">
		<span style="font-weight: bold"><?=GetMessage("YNSIR_ACTIVITY_LABLE_ENTITY_ID")?></span>
	</td>
	<td width="60%">
		<input type="text" name="EntityId" id="id_entity" value="<?=htmlspecialcharsbx($currentEntityId)?>" size="20">
		<input type="button" value="..." onclick="BPAShowSelector('id_entity', 'string');">
	</td>
</tr>
</tbody>
