<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr id="ria_pd_list_form">
    <td colspan="2">
        <table width="100%" class="adm-detail-content-table edit-table">
            <tr>
                <td align="right" width="40%" class="adm-detail-content-cell-l">
					<span class="adm-required-field">
						<?= GetMessage("BPRIORA_PD_CANDIDATE_ID") ?>:
					</span>
                </td>
                <td width="60%" class="adm-detail-content-cell-r">
                    <?=CBPDocument::ShowParameterField("int", 'candidate_id', $currentValues['candidate_id'], Array('size'=>50))?>
                </td>
            </tr>
            <tr>
                <td align="right" width="40%" class="adm-detail-content-cell-l">
					<span class="adm-required-field">
						<?= GetMessage("BPRIORA_PD_ORDER_ID") ?>:
					</span>
                </td>
                <td width="60%" class="adm-detail-content-cell-r">
                    <?=CBPDocument::ShowParameterField("int", 'job_order_id', $currentValues['job_order_id'], Array('size'=>50))?>
                </td>
            </tr>
            <tr>
                <td align="right" width="40%" class="adm-detail-content-cell-l">
					<span class="adm-required-field">
						<?= GetMessage("BPRIORA_PD_ASP_EMAIL") ?>:
					</span>
                </td>
                <td width="60%" class="adm-detail-content-cell-r">
                    <?=CBPDocument::ShowParameterField("string", 'asp_email', $currentValues['asp_email'], Array('size'=>50))?>
                </td>
            </tr>
        </table>
        <br>
    </td>
</tr>