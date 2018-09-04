<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetTitle(GetMessage('YNSIR_OTHER_CONFIG_TITLE'));
CJSCore::Init(array("jquery"));
//$APPLICATION->AddHeadScript('/bitrix/js/ynsirecruitment/select2.js');
//$APPLICATION->SetAdditionalCSS('/bitrix/js/ynsirecruitment/select2.css');
$APPLICATION->SetAdditionalCSS('/bitrix/js/ynsirecruitment/css/button-common.css');
$this->SetViewTarget("pagetitle", 1000);
?>
<div class="pagetitle-container pagetitle-align-right-container">
    <a href="/recruitment/config/" class="ynsir-cot-back">
        <?=GetMessage("YNSIR_COT_T_ADD_BACK_BTN")?>
    </a>
</div>
<?
$this->EndViewTarget();
if (isset($_SESSION['BIZ_CONFIG_SUCCESS']) && $_SESSION['BIZ_CONFIG_SUCCESS'] == 1) {
    unset($_SESSION['BIZ_CONFIG_SUCCESS']);
    ?>
    <div class="ynsirecruitment-setting-success">
        <?= GetMessage('YNSIR_OPTION_SAVE_SUCCESS') ?>
    </div>
    <script type="text/javascript">
        setTimeout(function () {
            $('.ynsirecruitment-setting-success').hide('slow');
        }, 1000);
    </script>
    <?php
}
?>
<form name="orther_configs_form" id="orther_configs_form" action="/recruitment/config/configs/">
    <input type="hidden" name="ACTION" value="SAVE_CONFIGS">
    <div class="workarea-content-paddings" id="ynsir-tab-custom">
        <?= bitrix_sessid_post(); ?>
        <div id="template_box" class="crm-transaction-menu">
            <a href="javascript:void(0)" class="tab_index status_tab_active" tab-index-value="tab_bizproc" class="status_tab " title="<?=GetMessage('YNSIR_OPTION_BIZPROC_TITLE')?>">
                <span><?=GetMessage('YNSIR_OPTION_BIZPROC_TITLE')?></span>
            </a>
            <a href="javascript:void(0)" tab-index-value="hr_managage" class="tab_index " title="<?=GetMessage('YNSIR_HR_MANAGEMENT_TITLE')?>">
                <span><?=GetMessage('YNSIR_HR_MANAGEMENT_TITLE')?></span>
            </a>

            <a href="javascript:void(0)" tab-index-value="note-salary-order" class="tab_index " title="<?=GetMessage('YNSIR_JOB_ORDER_SHOW_NOTE_SALARY_TITLE')?>">
                <span><?=GetMessage('YNSIR_JOB_ORDER_SHOW_NOTE_SALARY_TITLE')?></span>
            </a>
            <a href="javascript:void(0)" tab-index-value="notification-status" class="tab_index " title="<?=GetMessage('YNSIR_CANDIDATE_NOTIFICATION_STATUS')?>">
                <span><?=GetMessage('YNSIR_CANDIDATE_NOTIFICATION_STATUS')?></span>
            </a>
            <a href="javascript:void(0)" tab-index-value="process-status" class="tab_index " title="<?=GetMessage('YNSIR_ASSOCIATE_STATUS_BUSINESS_PROCESS')?>">
                <span><?=GetMessage('YNSIR_ASSOCIATE_STATUS_BUSINESS_PROCESS')?></span>
            </a>
        </div>
        <div class="crm-transaction-stage">
            <div id="template-content" class="tab_content crm-status-content active" tab-content-value="tab_bizproc">
                <?php
                if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
                $APPLICATION->IncludeComponent(
                    'ynsirecruitment:configs.bizproc',
                    '.default'
                );
                ?>
            </div>
            <div id="template-content" class="tab_content crm-status-content" tab-content-value="hr_managage">
                <?php
                if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
                $APPLICATION->IncludeComponent(
                    'ynsirecruitment:configs.hr.mananger',
                    '.default'
                );
                ?>
            </div>
            <div id="template-content" class="tab_content ynsir-sort-list-content" tab-content-value="sort_list">
                <div>
                    <label>Sort by: </label>
                </div>
                <div>
                    <select id="LIST_SORT_BY" name="LIST_SORT_BY" class="">
                        <option value="">-None-</option>
                        <?
                        foreach($arResult['CONFIG']['LIST_SORT']['SORT_BY'] as $key => $name) {
                            $selected = (key_exists($key,$arResult['DATA']['LIST_SORT']))? 'selected':'';
                            ?>
                            <option value="<?=$key?>" <?=$selected?>><?=$name?></option>
                        <?}
                        unset($name);
                        unset($key);
                        unset($selected);
                        ?>
                    </select>
                </div>
                <div>
                    <label>Sort Order: </label>
                </div>
                <div>
                    <select id="LIST_SORT_ORDER" name="LIST_SORT_ORDER" class="">
                        <option value="">-None-</option>
                        <?
                        foreach($arResult['CONFIG']['LIST_SORT']['ORDER_BY'] as $key => $name) {
                            $selected = (in_array($key,$arResult['DATA']['LIST_SORT']))? 'selected':'';
                            ?>
                            <option value="<?=$key?>" <?=$selected?>><?=$name?></option>
                        <?}
                        unset($name);
                        unset($key);
                        unset($selected);
                        ?>
                    </select>
                </div>
                <div class="clear-both"></div>
            </div>
            <div id="template-content" class="tab_content ynsir-sort-list-content" tab-content-value="note-salary-order">
                <div>
                    <label><?=GetMessage('YNSIR_JOB_SHOW_NOTE_SALARY_TITLE')?>: </label>
                </div>
                <div>
                    <select id="SHOW_NOTE_SALARY" name="SHOW_NOTE_SALARY" class="">
                        <option value="N" <?=($arResult['DATA']['SHOW_NOTE_SALARY']=='N')? 'selected':''?>><?=GetMessage("YNSIR_JOB_SHOW_NOTE_SALARY_NO")?></option>
                        <option value="Y" <?=($arResult['DATA']['SHOW_NOTE_SALARY']=='Y')? 'selected':''?>><?=GetMessage("YNSIR_JOB_SHOW_NOTE_SALARY_YES")?></option>
                    </select>
                </div>
                <div class="clear-both"></div>
            </div>
            <div id="template-content" class="tab_content ynsir-sort-list-content" tab-content-value="notification-status">
                <div class="content_div">
                    <div>
                        <label><span class="required">*</span> <?=GetMessage('YNSIR_CANDIDATE_ACCEPT_OFFER_TITLE')?>: </label>
                    </div>
                    <div class="value-config">
                        <select id="ACCEPT_OFFER_STATUS" name="ACCEPT_OFFER_STATUS" class="">
                            <option value="">-None-</option>
                            <?
                            $selected_val = $arResult['DATA']['CANDIDATE_STATUS']['ACCEPT_OFFER_STATUS'];
                            foreach ($arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS] as $iIdQ => $sNameQ) {
                                $sSelected = $selected_val == $iIdQ ? 'selected' : '';
                                ?>
                                <option value="<?= $iIdQ ?>" <?= $sSelected ?>><?= $sNameQ ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <div class="error" hidden="" style="display: none;">
                            <span></span>
                        </div>
                    </div>
                </div>
                <div class="content_div">
                    <div>
                        <label><span class="required">*</span> <?=GetMessage('YNSIR_CANDIDATE_REJECT_OFFER_TITLE')?>: </label>
                    </div>
                    <div class="value-config">
                        <select id="REJECT_OFFER_STATUS" name="REJECT_OFFER_STATUS" class="">
                            <option value="">-None-</option>
                            <?
                            $selected_val = $arResult['DATA']['CANDIDATE_STATUS']['REJECT_OFFER_STATUS'];
                            foreach ($arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS] as $iIdQ => $sNameQ) {
                                $sSelected = $selected_val == $iIdQ ? 'selected' : '';
                                ?>
                                <option value="<?= $iIdQ ?>" <?= $sSelected ?>><?= $sNameQ ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <div class="error" hidden="" style="display: none;">
                            <span></span>
                        </div>
                    </div>
                    <div class="clear-both"></div>
                </div>
            </div>
            <div id="template-content" class="tab_content ynsir-process-status" tab-content-value="process-status">
                <div class="content_div">
                    <div>
                        <label><span class="required">*</span> <?=GetMessage('YNSIR_ASSOCIATE_STATUS_ONBOARDING_PROCESS')?>: </label>
                    </div>
                    <div class="value-config">
                        <select id="ON_BOARDING_STATUS" name="ON_BOARDING_STATUS[]" class="" multiple="multiple" size="7">
                            <?
                            $selected_val = $arResult['DATA']['ON_BOARDING_STATUS'];
                            foreach ($arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS] as $iIdQ => $sNameQ) {
                                $sSelected = in_array($iIdQ,$selected_val) ? 'selected' : '';
                                ?>
                                <option value="<?= $iIdQ ?>" <?= $sSelected ?>><?= $sNameQ ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <div class="error" hidden="" style="display: none;">
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
            <div id="template-configs-footer" class="webform-buttons crm-configs-footer">
                <input type="button" value="<?=GetMessage('YNSIR_COT_T_SAVE_BTN')?>" id="submit-save" class="webform-small-button webform-small-button-accept" onclick="YNSIRConfigurations_.submitConfig()">
                <input type="button" value="<?=GetMessage('YNSIR_COT_T_CANCEL_BTN')?>" class="webform-small-button webform-small-button-cancel" onclick="location.reload()">
            </div>
        </div>
    </div>
</form>
<script>
    var MESSAGE_ERROR = <?=json_encode(array(
            'ERROR_WORFLOW_NOT_EXIST' => GetMessage('YNSIR_CONFIG_ERROR_WORFLOW_NOT_EXIST'),
            'ERROR_WORFLOW_EMPTY' => GetMessage('YNSIR_CONFIG_ERROR_WORFLOW_EMPTY'),
            'ERROR_SCAN_WF_NOT_EXIST' => GetMessage('YNSIR_CONFIG_ERROR_SCAN_WF_NOT_EXIST'),
            'ERROR_SCAN_WF_EMPTY' => GetMessage('YNSIR_CONFIG_ERROR_SCAN_WF_ID_EMPTY'),
            'ACCEPT_OFFER_STATUS_EMPTY' => GetMessage('YNSIR_CONFIG_ERROR_ACCEPT_OFFER_STATUS_EMPTY'),
            'ACCEPT_OFFER_STATUS_EXIST' => GetMessage('YNSIR_CONFIG_ERROR_ACCEPT_OFFER_STATUS_NOT_EXIST'),
            'REJECT_OFFER_STATUS_EMPTY' => GetMessage('YNSIR_CONFIG_ERROR_REJECT_OFFER_STATUS_EMPTY'),
            'REJECT_OFFER_STATUS_EXIST' => GetMessage('YNSIR_CONFIG_ERROR_REJECT_OFFER_STATUS_NOT_EXIST'),
            'REJECT_OFFER_STATUS_DUPPLICATE' => GetMessage('YNSIR_CONFIG_ERROR_REJECT_OFFER_STATUS_DUPPLICATE')
        ))
        ?>;
    BX.ready(
        function()
        {
            YNSIRConfigurations_ = new YNSIRConfigurations(
                {
                    'formID':'orther_configs_form',
                    'saveBTN':'submit-save',
                    'url':'/recruitment/config/configs/',
                    'messages':<?=CUtil::PhpToJSObject($arMessage)?>},
                "ynsir-tab-custom"
            );
            YNSIRConfigurations_.create();
//            $("#ACCEPT_OFFER_STATUS").select2({
//                templateResult: formatState,
//                templateSelection: formatRepoSelection,
//            });
//            $("#REJECT_OFFER_STATUS").select2({
//                templateResult: formatState,
//                templateSelection: formatRepoSelection,
//            });
//            function formatState(state) {
//                if (!state.id) {
//                    return state.text;
//                }
//                var $state = $(
//                    '<span>' + state.text + '</span>'
//                );
//                return $state;
//            };
//
//            function formatRepoSelection(state) {
//                return state.text;
//            }

        }
    );
</script>

