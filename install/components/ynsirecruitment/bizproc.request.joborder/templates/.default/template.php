<?php
CJSCore::Init(array("jquery"));
$APPLICATION->AddHeadScript('/bitrix/js/ynsirecruitment/select2.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/ynsirecruitment/select2.css');
$APPLICATION->AddHeadScript('/bitrix/components/ynsirecruitment/candidate.edit/templates/.default/jquery-ui.js');
$APPLICATION->SetAdditionalCSS('/bitrix/components/ynsirecruitment/candidate.edit/templates/.default/jquery-ui.css');
?>
<!--<table class="bizproc-table-main bizproc-task-table" cellpadding="3" border="0">-->
    <tbody>
    <tr>
        <td class="bizproc-field-name" width="30%" valign="top"
            align="right"><?= GetMessage('YNSIR_TITLE_JOB_ORDER') . ':' ?></td>
        <td class="bizproc-field-value" width="70%" valign="top">
            <select class="recruitment-item-table-select"
                    id="ynsirc_job_order_<?= $_REQUEST['TASK_ID'] ?>"
                    name="ynsirc_job_order">
                <option value="0"><?= GetMessage('YNSIR_NOT_SPECIFIED') ?></option>
                <?php

                foreach ($arResult['JO'] as $iIdQ => $sNameQ) {
                    ?>
                    <option value="<?= $iIdQ ?>"
                            item_type="<?= $sNameQ['TITLE'] ?>"
                            lable_type="<?= $sNameQ['TITLE'] ?>"><?= $sNameQ['TITLE'] ?></option>
                    <?php
                }
                ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="bizproc-field-name" style="<?=$arParams['RequiredComment']==0?'font-weight:normal':''?>" width="30%" valign="top" align="right">
            <?

            if(strlen($arParams['LabelComment'])>0){
                echo $arParams['LabelComment'];
            }else{
                echo GetMessage('YNSIR_TITLE_COMMENT') . ':';
            }
            ?>
        </td>
        <td class="bizproc-field-value" width="70%" valign="top"><textarea rows="3" cols="50"
                                                                           name="comment"></textarea></td>
    </tr>
    </tbody>
<!--</table>-->
<script>

    $(document).ready(function () {
        var arNewPlaceIssue = [];
        var arOldPlaceIssue = [];

        $("#ynsirc_job_order_<?=$_REQUEST['TASK_ID']?>").select2({
            templateResult: formatState,
            templateSelection: formatRepoSelection,
        });

        function formatState(state) {
            if (!state.id) {
                return state.text;
            }
            var $state = $(
                '<span>' + state.text + '</span>'
            );
            return $state;
        };

        function formatRepoSelection(state) {
            return state.text;
        }
    });
</script>