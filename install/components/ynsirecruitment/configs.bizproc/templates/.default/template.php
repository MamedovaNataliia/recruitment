<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Page\Asset;

CUtil::InitJSCore(array("amcharts", "amcharts_funnel", "amcharts_serial"));
?>

    <table class="bx-edit-table ">
        <tr class="bx-top">
            <td class="bx-field-name-bizproc bx-padding">
                <div>
<!--                    <span class="required">*</span>-->
                    <span><?=GetMessage('YNSIR_WORKFLOWS_FOR_APPROVE_JOB_ORDER').':'?></span>
                </div>
            </td>
            <td class="bx-field-value">
                <select id="YNSIR_BIZ_APPROVE_ORDER_ID" name="YNSIR_BIZ_APPROVE_ORDER_ID">
                    <option value=0>-None-</option>
                    <? foreach ($arResult['ITEMS'] as $k => $v): ?>
                        <? $selected = $k == $arResult["CONFIGS"]['YNSIR_BIZ_APPROVE_ORDER_ID'] ? 'selected' : '' ?>
                        <option <?= $selected ?> value="<?= $k ?>"><?= $v ?></option>
                    <? endforeach; ?>
                </select>
                <div class="error" hidden=""
                     style="display: none;">
                    <span></span>
                </div>
            </td>
        </tr>
        <tr class="bx-top">
            <td class="bx-field-name-bizproc bx-padding">
                <div>
<!--                    <span class="required">*</span>-->
                    <span><?=GetMessage('YNSIR_BIZ_APPROVE_ORDER_ID').':'?></span>
                </div>
            </td>
            <td class="bx-field-value">
                <select id="BIZPROC_BIZ_SCAN_CV_ID"  name="BIZPROC_BIZ_SCAN_CV_ID">
                    <option value=0>-None-</option>
                    <? foreach ($arResult["ITEMS"] as $k => $v): ?>
                        <? $selected = $k == $arResult["CONFIGS"]['BIZPROC_BIZ_SCAN_CV_ID'] ? 'selected' : '' ?>
                        <option <?= $selected ?> value="<?= $k ?>"><?= $v ?></option>
                    <? endforeach; ?>
                </select>
                <div class=" error" hidden=""
                     style="display: none;">
                    <span></span>
                </div>
            </td>
        </tr>
        <tr class="bx-top">
            <td class="bx-field-name-bizproc bx-padding">
                <div>
                    <!--                    <span class="required">*</span>-->
                    <span><?=GetMessage('YNSIR_BIZ_ONBOARDING').':'?></span>
                </div>
            </td>
            <td class="bx-field-value">
                <select id="BIZPROC_BIZ_ONBOARDING"  name="BIZPROC_BIZ_ONBOARDING_ID">
                    <option value=0>-None-</option>
                    <? foreach ($arResult["ITEMS"] as $k => $v): ?>
                        <? $selected = $k == $arResult["CONFIGS"]['BIZPROC_BIZ_ONBOARDING_ID'] ? 'selected' : '' ?>
                        <option <?= $selected ?> value="<?= $k ?>"><?= $v ?></option>
                    <? endforeach; ?>
                </select>
                <div class=" error" hidden=""
                     style="display: none;">
                    <span></span>
                </div>
            </td>
        </tr>
    </table>
<script type="text/javascript">
    var MESSAGE_ERROR = <?=json_encode(array(
            'ERROR_WORFLOW_NOT_EXIST' => GetMessage('YNSIR_CONFIG_ERROR_WORFLOW_NOT_EXIST'),
            'ERROR_WORFLOW_EMPTY' => GetMessage('YNSIR_CONFIG_ERROR_WORFLOW_EMPTY'),
            'ERROR_TEMPALTE_NOT_EXIST' => GetMessage('YNSIR_CONFIG_ERROR_APPROVE_NOT_EXIST'),
            'ERROR_TEMPLATE_EMPTY' => GetMessage('YNSIR_CONFIG_ERROR_APPROVE_ORDER_IDEMPTY')))
        ?>;
//    $('#YNSIR_BIZ_APPROVE_ORDER_ID').change(function () {
//        var id = this.value;
//        BX.ajax({
//            url: '/recruitment/config/bizproc/',
//            method: 'POST',
//            dataType: 'json',
//            data: {'ACTION': 'GET_TEMPLATE', 'ID': id, "sessid": BX.bitrix_sessid()},
//            onsuccess: function (result) {
//                $('#BIZPROC_BIZ_SCAN_CV_ID').html('');
//                $('#BIZPROC_BIZ_SCAN_CV_ID').append($('<option>', {value: ''}).text('-None-'));
//                for (var key in result.ITEMS) {
//                    $('#BIZPROC_BIZ_SCAN_CV_ID').append($('<option>', {value: key}).text(result.ITEMS[key]));
//                }
//            }
//        });
//    });
</script>




