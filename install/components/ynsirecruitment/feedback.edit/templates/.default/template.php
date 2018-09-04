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

<form action="/recruitment/feedback/edit/<?= $arParams['feedback_id'] ?>/" name="ynsirecruitment-config-list"
      id="ynsirecruitment-config-list"
      enctype="multipart/form-data" method="POST">
    <?= bitrix_sessid_post() ?>
    <div hidden id="feedback_error ERROR_MSG_" style="color: red;margin-left: 22%;"></div>
    <table class="content-edit-form">
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_TITLE') ?>:</td>
            <td class="content-edit-form-field-input">
            <? $title = GetMessage('YNSIR_FEEDBACK_REFIX_TITLE').' - '.$arResult['CANDIDATE_NAME'].' - '.$round ?>
                <input hidden id="feedback_title" name="TITLE" value="<?=$title?>" class="content-edit-form-field-input-text">
                <span  ><?=$title?></span>
            </td>
        </tr>
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_CANDIDATE') ?>:</td>
            <td class="content-edit-form-field-input">
                <input hidden type="text" id="feedback_candidate" name="CANDIDATE_NAME"
                       value="<?= $arResult['CANDIDATE_NAME'] ?>"
                       class="content-edit-form-field-input-text">
                <a href="/recruitment/candidate/detail/<?= $arResult['CANDIDATE_ID'] ?>/"><?= $arResult['CANDIDATE_NAME'] ?></a>
                <input hidden id="feedback_candidate_id" name="CANDIDATE_ID" value="<?= $arResult['CANDIDATE_ID'] ?>"
                       class="content-edit-form-field-input-text">
            </td>
        </tr>
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_JOB_ORDER') ?>:</td>
            <td class="content-edit-form-field-input">
                <input hidden type="text" name="JOB_ORDER_NAME" value="<?= $arResult['JOB_ORDER_NAME'] ?>"
                       class="content-edit-form-field-input-text">
                <a href="/recruitment/job-order/detail/<?= $arResult['JOB_ORDER_ID'] ?>/"><?= $arResult['JOB_ORDER_NAME'] ?></a>
                <input hidden id="feedback_job_order_id" value="<?= $arResult['JOB_ORDER_ID'] ?>" name="JOB_ORDER_ID"
                       class="content-edit-form-field-input-text">
            </td>
        </tr>
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_ROUND') ?>:</td>
            <td class="content-edit-form-field-select">
                <?$v = $arResult['CONFIG']['ROUND'][$arResult['JOB_ORDER_ID']][$arResult['ROUND_ID']];?>
                <span><?= GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $v['ROUND_INDEX'])); ?></span>
                <select hidden name="ROUND_ID" id="feedback_round_id"
                        class="content-edit-form-field-input-text content-edit-form-field-input-select">
                    <option value=''><?= GetMessage('YNSIR_FEEDBACK_NOTSET') ?></option>
                    <?
                    foreach ($arResult['CONFIG']['ROUND'][$arResult['JOB_ORDER_ID']] as $KEY => $VALUE) { ?>
                        <option value='<?= $KEY ?>' <?= $KEY == $arResult['ROUND_ID'] ? 'selected' : '' ?> >  <?= GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $VALUE['ROUND_INDEX'])); ?></option>
                        <?
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="edit-form-field-name"><?= GetMessage('YNSIR_FEEDBACK_DESCRIPTION') ?><span
                        class="starrequired">(*)</span>:</td>
            <td class="content-edit-form-field-input">
                <?php
                $APPLICATION->IncludeComponent(
                    "bitrix:main.post.form",
                    "",
                    array(
                        "FORM_ID" => "event_edit_form",
                        "SHOW_MORE" => "Y",
                        "PARSER" => Array(
                            "Bold", "Italic", "Underline", "Strike", "ForeColor",
                            "FontList", "FontSizeList", "RemoveFormat", "Quote",
                            "Code", "CreateLink",
                            "Image", "UploadFile",
                            "InputVideo",
                            "Table", "Justify", "InsertOrderedList",
                            "InsertUnorderedList",
                            "Source", "MentionUser"
                        ),
                        "TEXT" => Array(
                            "ID" => 'feedback_ed_description',
                            "NAME" => "DESCRIPTION",
                            "VALUE" => $arResult['DESCRIPTION'],
                            "HEIGHT" => "280px"
                        ),
                        "SMILES" => Array("VALUE" => array()),
                        "LHE" => array(

                            "id" => 'feedback_description'.rand(1, 1000),
                            "documentCSS" => "",
                            "jsObjName" => 'DESCRIPTION',
                            "fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
                            "fontSize" => "12px",
                            "lazyLoad" => false,
                            "setFocusAfterShow" => false
                        )
                    ),
                    false,
                    array(
                        "HIDE_ICONS" => "Y"
                    )
                );
                ?>
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

    <input type="hidden" name="ADD" value="Y">
    <input type="hidden" name="submit" value="submit">
</form>
