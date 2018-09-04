<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init(array("jquery"));
$this->SetViewTarget("pagetitle", 1000);
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');
?>
<div class="pagetitle-container pagetitle-align-right-container">
    <a href="/recruitment/config/" class="ynsir-cot-back">
        <?=GetMessage("YNSIR_COT_T_ADD_BACK_BTN")?>
    </a>
</div>
<div class="pagetitle-container pagetitle-align-right-container">
    <a href="javascript:void(0);" onclick="addNewTemplate()" title="<?=GetMessage("YNSIR_COT_T_ADD_NEW_TITLE")?>">
        <span class="webform-small-button webform-small-button-blue bx24-top-toolbar-add crm-deal-add-button">
            <?=GetMessage("YNSIR_COT_T_ADD_NEW_BTN")?>
        </span>
    </a>
</div>
<?
$this->EndViewTarget();
?>
<div class="template-category">
    <span><?=GetMessage("YNSIR_COT_T_CATEGORY_TITLE")?>: </span>
    <span>
        <select id="teamplate_cate" class="cate-template" onchange="window.location='<?=$arResult['URL_TEMPLATE']?>'+this[this.selectedIndex].value+'/';">
            <?php
            foreach ($arResult['CATEGORY'] as $iIdCate => $sNameCate) {
                $sSelectCate = $arResult['CATE_ID'] == $iIdCate ? 'selected' : '';
                ?>
                <option value="<?=$iIdCate?>" <?=$sSelectCate?>>
                    <?=htmlspecialchars($sNameCate, ENT_QUOTES)?>
                </option>
                <?php
            }
            ?>
        </select>
    </span>
</div>
<div class="workarea-content-paddings">
    <div id="template_box" class="crm-transaction-menu">
        <?php
        foreach($arResult['DATA'] as $arTemplate){
            ?>
            <a href="javascript:void(0)" id="template_tab_<?=$arTemplate['ID']?>" class="status_tab " title="<?=htmlspecialchars($arTemplate['NAME_TEMPLATE'])?>" onclick="selectTemplateTab(<?=$arTemplate['ID']?>)">
                <span><?=htmlspecialchars($arTemplate['NAME_TEMPLATE'])?></span>
                <div class="ynsir-active-template <?if($arTemplate['ACTIVE'] == 0) echo 'ynsir-inactive-template';?>"></div>
            </a>
            <?php
        }
        ?>
    </div>
    <div class="crm-transaction-stage">
        <div id="template-content" class="crm-status-content active"></div>
        <div id="template-configs-footer" class="webform-buttons crm-configs-footer">
            <input type="button" value="<?=GetMessage('YNSIR_COT_T_EDIT_BTN')?>" class="webform-small-button webform-small-button-accept" onclick="editTemplate()">
            <input type="button" value="<?=GetMessage('YNSIR_COT_T_DELETE_BTN')?>" class="webform-small-button webform-small-button-cancel" onclick="deleteTemplate()">
        </div>
    </div>
</div>
<div id="popup_template" class="ajax-popup" style="min-width: 710px; min-height:250px" hidden>
    <div class="name-template">
        <div class="name-template-title">
            <?=GetMessage("YNSIR_COT_T_NAME_TITLE")?>:
        </div>
        <div class="name-template-input">
            <input type="text" id="name_template" />
            <p id="name-error-message" class="error"></p>
        </div>
    </div>
    <div id="content-template-design">
        <?php
        $APPLICATION->IncludeComponent(
            "bitrix:main.post.form",
            "",
            ($formParams = Array(
                "FORM_ID" => "JOB_ORDER_TEMPLATE",
                "SHOW_MORE" => "Y",
                'PARSER' => array(
                    'Bold', 'Italic', 'Underline', 'Strike',
                    'ForeColor', 'FontList', 'FontSizeList', 'RemoveFormat',
                    'Quote', 'Code', 'InsertCut',
                    'CreateLink', 'Image', 'Table', 'Justify',
                    'InsertOrderedList', 'InsertUnorderedList',
                    'SmileList', 'Source', 'UploadImage', 'InputVideo', 'MentionUser'
                ),
                "TEXT" => Array(
                    "NAME" => "JOB_ORDER_TEMPLATE",
                    "VALUE" => '',
                    "HEIGHT" => "500px"),
                "PROPERTIES" => array(),
            )),
            false,
            Array("HIDE_ICONS" => "Y")
        );
        ?>
    </div>
    <div class="description-error-message">
        <p id="description-error-message" class="error"></p>
    </div>
    <div class="active-template">
        <label>
            <input type="checkbox" id="template-active"/>
            <span class="active-template-label">
                Active
            </span>
        </label>
    </div>
</div>

<div id="cate_template" class="ajax-popup" style="min-width: 420px; min-height:17px" hidden>
    <div style="color: red;">
        <?=GetMessage('YNSIR_COT_T_CATEGORY_MESS')?>
    </div>
</div>

<div id="popup_confirm" class="ajax-popup" style="min-width: 420px; min-height:17px" hidden>
    <div style="color: red;">
        <?=GetMessage('YNSIR_COT_T_DELETE_MESSAGE')?>
    </div>
</div>
<script>
    var JSCOTData = <?php echo json_encode($arResult['DATA']);?>;
    var JSCOTMess = {
        YNSIR_COT_T_SAVE_BTN: '<?=GetMessage("YNSIR_COT_T_SAVE_BTN")?>',
        YNSIR_COT_T_CANCEL_BTN: '<?=GetMessage("YNSIR_COT_T_CANCEL_BTN")?>',
        YNSIR_COT_T_TEMPLATE_TITLE: '<?=GetMessage("YNSIR_COT_T_TEMPLATE_TITLE")?>',
        YNSIR_COT_T_ADD_NEW_TITLE: '<?=GetMessage("YNSIR_COT_T_ADD_NEW_TITLE")?>',
        YNSIR_COT_T_DELETE_BTN: '<?=GetMessage("YNSIR_COT_T_DELETE_BTN")?>',
        YNSIR_COT_T_DELETE_MESSAGE: '<?=GetMessage("YNSIR_COT_T_DELETE_MESSAGE")?>',
        YNSIR_COT_T_NAME_VALIDATE: '<?=GetMessage("YNSIR_COT_T_NAME_VALIDATE")?>',
        YNSIR_COT_T_CONTENT_VALIDATE: '<?=GetMessage("YNSIR_COT_T_CONTENT_VALIDATE")?>',
        YNSIR_COT_T_DELETE_TEMPLATE_TITLE: '<?=GetMessage("YNSIR_COT_T_DELETE_TEMPLATE_TITLE")?>',
        YNSIR_COT_T_CATEGORY_TITLE: '<?=GetMessage("YNSIR_COT_T_CATEGORY_TITLE")?>',
        YNSIR_COT_T_CATEGORY_BTN_CLOSE: '<?=GetMessage("YNSIR_COT_T_CATEGORY_BTN_CLOSE")?>',
    };
    var _selected_default = <?=$arResult['DEFAULT_ACTIVE']?>;
    var _list_cate = <?php echo json_encode(array_keys($arResult['CATEGORY']))?>;
    var _link_category = '<?=$arResult["URL_CATEGORY"]?>';
</script>

