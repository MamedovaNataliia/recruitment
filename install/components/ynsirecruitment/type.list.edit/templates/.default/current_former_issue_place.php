<?php
/**
 * Created by PhpStorm.
 * User: nhatth
 * Date: 6/13/17
 * Time: 8:59 AM
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$isSkill = strtoupper($arParams['entity']) == HRMConfig::TL_SKILL;
?>
<form action="/recruitment/config/lists/<?= $arParams['entity'] ?>/edit/<?=$arParams['ELEMENT_ID']?>" name="ynsirecruitment-config-list" id="ynsirecruitment-config-list"
      enctype="multipart/form-data" method="POST">
    <?= bitrix_sessid_post() ?>
    <table class="content-edit-form">
        <tr>
            <td class="content-edit-form-field-name"><?=GetMessage('YNSIR_LIST_KEY_TITLE_EN')?><span class="starrequired">*</span></td>
            <td class="content-edit-form-field-input">
                <input type="text" id= "name_en_list" value="<?=($arResult['ELEMENT']['NAME_EN'])?>"  name="NAME_KEY_EN" class="content-edit-form-field-input-text">
            </td>
        </tr>
        <tr>
            <td class="content-edit-form-field-name"><?=GetMessage('YNSIR_LIST_KEY_CODE')?><span class="starrequired">*</span></td>
            <td class="content-edit-form-field-input">
                <input type="text" id= "key_code_list" value="<?=($arResult['ELEMENT']['CODE'])?>"  name="KEY_CODE" class="content-edit-form-field-input-text">
            </td>
        </tr>

        <?if($arResult['SHOW_ADDINATIONAL_VALUE']):?>
        <tr>
            <td class="content-edit-form-field-name"><?=GetMessage('YNSIR_TITLE_TYPE_CONTENT')?>: </td>
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
        <?endif;?>
        <tr>
            <td></td>
            <td>
                <div id="strErr">
                    <span class="error"></span>
                </div>
            </td>
        </tr>
    </table>

    <input type="hidden" name="EDIT" value="Y">
    <input type="hidden" name="submit" value="submit">
</form>
