<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr id="ria_pd_list_form">
	<td colspan="2">
		<table width="100%" class="adm-detail-content-table edit-table">
			<tr>
				<td align="right" width="40%" class="adm-detail-content-cell-l">
					<span class="adm-required-field">
						<?= GetMessage("BPRIORA_PD_REQUESTED_USERS") ?>:
					</span>
				</td>
				<td width="60%" class="adm-detail-content-cell-r">
					<?=CBPDocument::ShowParameterField("user", 'requested_users', $arCurrentValues['requested_users'], Array('rows'=>'2'))?>
				</td>
			</tr>
			<tr>
				<td align="right" width="40%" class="adm-detail-content-cell-l">
					<span class="adm-required-field">
						<?= GetMessage("BPRIORA_PD_REQUESTED_NAME") ?>:
					</span>
				</td>
				<td width="60%" class="adm-detail-content-cell-r">
					<?=CBPDocument::ShowParameterField("string", 'requested_name', $arCurrentValues['requested_name'], Array('size'=>'50'))?>
				</td>
			</tr>
			<tr>
				<td align="right" width="40%" class="adm-detail-content-cell-l" valign="top">
					<?= GetMessage("BPRIORA_PD_REQUESTED_DESCRIPTION") ?>:
				</td>
				<td width="60%" valign="top" class="adm-detail-content-cell-r">
					<?=CBPDocument::ShowParameterField("text", 'description', $arCurrentValues['description'], array('rows' => 7))?>
				</td>
			</tr>
            <tr>
				<td align="right" width="40%" class="adm-detail-content-cell-l" valign="top">
                    Comment is required:
				</td>
                <td class="adm-detail-content-cell-r">
                    <select name="required_comment">
                        <?php
                        foreach ($arActionRequiredComment as $key => $itemAction) {
                            $sSelect = ($key == $arCurrentValues["required_comment"]) ? 'selected' : '';
                            ?>
                            <option value="<?=$key?>" <?=$sSelect?>><?=$itemAction?></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
			</tr>
            <tr>
				<td align="right" width="40%" class="adm-detail-content-cell-l" valign="top">
                    Comment input field label:
				</td>
				<td width="60%" valign="top" class="adm-detail-content-cell-r">
					<?=CBPDocument::ShowParameterField("string", 'label_comment', $arCurrentValues['label_comment'], array('rows' => 7))?>
				</td>
			</tr>
		</table>
		<br>
	</td>
</tr>