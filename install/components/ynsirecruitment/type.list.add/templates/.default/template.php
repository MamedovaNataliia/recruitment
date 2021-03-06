<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<form action="/recruitment/config/lists/<?= $arParams['entity'] ?>/add/" name="ynsirecruitment-config-list" id="ynsirecruitment-config-list"
      enctype="multipart/form-data" method="POST">
    <?= bitrix_sessid_post() ?>
    <table class="content-edit-form">
        <tr>
            <td class="content-edit-form-field-name"><?=GetMessage('YNSIR_LIST_KEY_TITLE_EN')?><span class="starrequired">*</span></td>
            <td class="content-edit-form-field-input">
                <input type="text" id= "name_en_list"  name="NAME_KEY_EN" class="content-edit-form-field-input-text">
            </td>
        </tr>
        <tr>
            <td class="content-edit-form-field-name"><?=GetMessage('YNSIR_TITLE_TYPE_CONTENT')?></td>
            <td class="content-edit-form-field-select">
                <select name="ADDITIONAL_INFO" id="ADDITIONAL_INFO"
                        class="content-edit-form-field-input-text content-edit-form-field-input-select">
                    <option value =''><?=GetMessage('YNSIR_TYPE_LIST_NOTSET')?></option>
                    <?
                    foreach ($arResult['CONFIG']['CONTENT_TYPE'] as $KEY => $VALUE) {?>
                        <option value = '<?=$KEY?>'><?=$VALUE?></option>
                    <?}
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="content-edit-form-field-name"><?=GetMessage('YNSIR_TITLE_TYPE_LABEL_EN')?>: </td>
            <td class="content-edit-form-field-input">
                <input type="text" id= "ADDITIONAL_INFO_LABEL_EN"  name="ADDITIONAL_INFO_LABEL_EN" class="content-edit-form-field-input-text">
            </td>
        </tr>
        <tr>
            <td class="content-edit-form-field-name"><?=GetMessage('YNSIR_TITLE_TYPE_SORT')?>: </td>
            <td class="content-edit-form-field-input">
                <input type="text" id= "SORT"  name="SORT" class="content-edit-form-field-input-text">
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
