<?php
CJSCore::Init(array("jquery"));
$APPLICATION->AddHeadScript('/bitrix/js/ynsirecruitment/select2.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/ynsirecruitment/select2.css');
$APPLICATION->AddHeadScript('/bitrix/components/ynsirecruitment/candidate.edit/templates/.default/jquery-ui.js');
$APPLICATION->SetAdditionalCSS('/bitrix/components/ynsirecruitment/candidate.edit/templates/.default/jquery-ui.css');
?>
<!--<table class="bizproc-table-main bizproc-task-table" style="position: absolute" cellpadding="3" border="0">-->
    <tbody>
    <tr>
        <?if(false):?>
        <td class="bizproc-field-name" width="30%" valign="top"
            align="right"><?= GetMessage('YNSIR_TITLE_JOB_ORDER') . ':' ?></td>
        <td class="bizproc-field-value" width="70%" valign="top">
            <?  if($arResult['JO']['ID'] > 0):?>
            <div>
                <a href="/recruitment/job-order/detail/<?= $arResult['JO']['ID'] ?>/"><?= $arResult['JO']['TITLE'] ?></a>
            </div>
            <?endif?>
        </td>
    </tr>
    <?endif?>
    <?if(false):?>
    <tr>
        <td class="bizproc-field-name" width="30%" valign="top"
            align="right"><?= GetMessage('YNSIR_TITLE_JOB_ORDER_REQUESTER') . ':' ?></td>
        <td class="bizproc-field-value" width="70%" valign="top">
            <? $tooltip = YNSIRHelper::getTooltipandPhotoUser($_REQUEST['REQUESTER'] = 3, 'requester') ?>
            <div class="crm-client-photo-wrapper">
                <div class="crm-client-user-def-pic">
                    <img alt="Author Photo" src="<?= $tooltip['PHOTO_URL'] ?>"/>
                </div>
            </div>
            <div class="crm-client-photo-wrapper"><?= $tooltip['TOOLTIP'] ?></div>
            <div style="clear: both"></div>
        </td>
    </tr>
    <?endif?>
    <?if($arParams['action'] == RECRUITMENT_ACTION_CHANGE_STATUS):?>
    <tr>
        <td class="bizproc-field-name" width="30%" valign="top"
            align="right"><?= GetMessage('YNSIR_TITLE_JOB_ORDER_STATUS') . ':' ?></td>
        <td class="bizproc-field-value" width="70%" valign="top">
            <select class="recruitment-item-table-select"
                    id="ynsirc_job_order_<?= $_REQUEST['TASK_ID'] ?>"
                    name="ynsirc_job_order_status">
                <option value="0"><?= GetMessage('YNSIR_NOT_SPECIFIED') ?></option>
                <?php
                foreach ($arResult['STATUS'] as $iIdQ => $sNameQ) {
                    $selected = $arResult['JO']['STATUS'] == $iIdQ?'selected':'';
                    ?>
                    <option <?=$selected?> value="<?= $iIdQ ?>"
                            item_type="<?= $sNameQ ?>"
                            lable_type="<?= $sNameQ ?>"><?= $sNameQ ?></option>
                    <?php
                }
                ?>
            </select>
        </td>
    </tr>
<?endif?>
    <tr>
        <td class="bizproc-field-name" width="30%" valign="top"
            align="right"><?= GetMessage('YNSIR_TITLE_COMMENT') . ':' ?></td>
        <td class="bizproc-field-value" width="70%" valign="top"><textarea rows="3" cols="50"
                                                                           name="comment"></textarea></td>
    </tr>
    </tbody>
<!--</table>-->
<input hidden name="ynsirc_job_order" value="<?= $arResult['JO']['ID'] ?>"/>