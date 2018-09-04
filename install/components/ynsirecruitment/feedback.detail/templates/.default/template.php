<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
if ($arResult['status'] === 'not_data'){
    ?>
    <div class="task-message-label error"><?= GetMessage('DONT_HAVE_FEEDBACK_PERMISSION') ?></div>
    <?php
    exit();
}
$v = $arResult['CONFIG']['ROUND'][$arResult['JOB_ORDER_ID']][$arResult['ROUND_ID']];
$round =  GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $v['ROUND_INDEX']));
?>


    <table class="content-edit-form">
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_TITLE') ?>:</td>
            <td class="content-edit-form-field-input">

                <span  ><?=$arResult['TITLE']?></span>
            </td>
        </tr>
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_CANDIDATE') ?>:</td>
            <td class="content-edit-form-field-input">
                <a href="/recruitment/candidate/detail/<?= $arResult['CANDIDATE_ID'] ?>/"><?= $arResult['CANDIDATE_NAME'] ?></a>

            </td>
        </tr>
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_JOB_ORDER') ?>:</td>
            <td class="content-edit-form-field-input">
                <a href="/recruitment/job-order/detail/<?= $arResult['JOB_ORDER_ID'] ?>/"><?= $arResult['JOB_ORDER'] ?></a>
            </td>
        </tr>
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_ROUND') ?>:</td>
            <td class="content-edit-form-field-select">
                <?$v = $arResult['CONFIG']['ROUND'][$arResult['JOB_ORDER_ID']][$arResult['ROUND_ID']];?>
                <span><?= GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $v['ROUND_INDEX'])); ?></span>

            </td>
        </tr>
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_DESCRIPTION') ?>:</td>
            <td class="content-edit-form-field-input task-detail-description">
                <?= $arResult['DESCRIPTION']?>
                <div hidden class="error-validate" id="ERROR_MSG_DESCRIPTION" style="color: red;font-style: italic;"></div>

            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <div id="strErr">
                    <span class="error"></span>
                </div>
            </td>
        </tr>
    </table>
