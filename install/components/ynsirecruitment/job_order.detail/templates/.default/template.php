<?php
// begin iframe
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$isIFrame = $request['IFRAME'] == 'Y';
if ($isIFrame) :
    global $APPLICATION;
    $APPLICATION->RestartBuffer();
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= LANGUAGE_ID ?>" lang="<?= LANGUAGE_ID ?>">
    <head>
        <script type="text/javascript">
            if (window == window.top) {
                window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam("", array("IFRAME", "IFRAME_TYPE"))); ?>";
            }
        </script>
        <? $APPLICATION->ShowHead(); ?>
    </head>
    <body id="tasks-iframe-popup-scope" class="
			template-<?= SITE_TEMPLATE_ID ?> <? $APPLICATION->ShowProperty("BodyClass"); ?> <? if ($isIFrame): ?>task-iframe-popup-side-slider<? endif ?>"
    onload="window.top.BX.onCustomEvent(window.top, 'tasksIframeLoad');"
    onunload="window.top.BX.onCustomEvent(window.top, 'tasksIframeUnload');">

    <? if ($isIFrame): ?>
    <div class="tasks-iframe-header">
        <div class="pagetitle-wrap">
            <div class="pagetitle-inner-container">
                <div class="pagetitle-menu" id="pagetitle-menu"><?
                    $APPLICATION->ShowViewContent("pagetitle")
                    ?></div>
                <div class="pagetitle">
                    <span id="pagetitle"
                          class="pagetitle-item"><? $APPLICATION->ShowTitle(false); ?><? if ($existingTask): ?><span
                            class="task-page-link-btn js-id-copy-page-url"
                            title="<?= Loc::getMessage('TASKS_TIP_TEMPLATE_COPY_CURRENT_URL') ?>"></span><? endif ?></span>
                </div>
            </div>
        </div>
    </div>
    <? endif;?>
    <div class="task-iframe-workarea <? if ($isIFrame): ?>task-iframe-workarea-own-padding<? endif ?>"
    id="tasks-content-outer">
    <div class="task-iframe-sidebar">
        <? $APPLICATION->ShowViewContent("sidebar"); ?>
    </div>
    <div class="task-iframe-content">
    <?
endif;
// end header iframe

use Bitrix\Disk\File;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$APPLICATION->SetTitle(GetMessage('YNSIR_JOD_T_JOB_ORDER') . ': ' . htmlspecialchars($arResult['JOB_ORDER']['TITLE']));

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/common.js');

$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');

CJSCore::Init(array("tooltip"));

//
$this->SetViewTarget("pagetitle", 100);
$guid = $arResult['GUID'];
$config = $arResult['CONFIG'];

$isExpanded = $config['expanded'] === 'Y';
$isFixed = $config['fixed'] === 'Y';
$bShowNoteSalary = COption::GetOptionString("ynsirecruitment", "ynsir_order_salary_note");
// if iframe not show tab
if (!$isIFrame) {
    ?>
    <div class="pagetitle-container pagetitle-align-right-container">
        <a href="/recruitment/job-order/list/" class="ynsir-candidate-detail-back">
            <?= GetMessage('YNSIR_JOD_T_BTN_BACK') ?>
        </a>
    </div>
    <?
    $this->EndViewTarget();
}

if($arResult['ID'] != $arResult['JOB_ORDER']['ID'] && $arResult['ID'] > 0){
    $APPLICATION->SetTitle(GetMessage('YNSIR_JOD_T_JOB_ORDER'));
    ShowError(GetMessage("YNSIR_CJOD_JOB_ORDER_NOT_FOUND"));
    return;
}

if ($arResult['CAN_EDIT']):
    $this->SetViewTarget("pagetitle", 100);
    if (!$isIFrame && strlen($arResult['BIZ']['APPROVE']["TEMPLATES"]["URL"]) > 0 && $arResult['JOB_ORDER']['STATUS'] == 'NEW') :
    ?>
    <div id="SUBMIT_APPROVE_BTN" onclick=""
         class="pagetitle-container pagetitle-align-right-container-submit">
        <a id="SUBMIT_APPROVE_BTN_a">
            <?= GetMessage('YNSIR_JOD_T_BTN_SUBMIR') ?>
        </a>
    </div>
    <?endif;?>
    <div onclick="window.top.location='/recruitment/job-order/edit/<?= $arResult['JOB_ORDER']['ID'] ?>/';"
         class="pagetitle-container pagetitle-align-right-container-edit">
        <a>
            <?= GetMessage('YNSIR_JOD_T_BTN_EDIT') ?>
        </a>
    </div>
    <?
    $this->EndViewTarget();
endif;
$isShowPopUp = 'none';
$iHidePupupSubmit = 1;
if(isset($_SESSION[$arResult['ID'].'_SUBMIT']) || isset($_SESSION[$arResult['ID'].'_SUCCESS'])){
    $isShowPopUp = 'block';
    $sShowSubmitMess = GetMessage("YNSIR_CJOD_SUBMIT_WF_SUCCESS");
    if(isset($_SESSION[$arResult['ID'].'_SUBMIT'])){
        $sShowSubmitMess = '';
        $iHidePupupSubmit = 0;
        foreach ($_SESSION[$arResult['ID'].'_SUBMIT'] as $sMessSunmit){
            $sShowSubmitMess .= '<p>' . $sMessSunmit . '</p>';
        }
        unset($_SESSION[$arResult['ID'].'_SUBMIT']);
    }
    unset($_SESSION[$arResult['ID'].'_SUCCESS']);
} elseif(isset($_SESSION['SUBMIT_APPROVE_WORKFLOW_ERROR']) && isset($_SESSION['SUBMIT_APPROVE_WORKFLOW_ERROR_MESS'])) {
    $isShowPopUp = 'block';
    $sShowSubmitMess = $_SESSION['SUBMIT_APPROVE_WORKFLOW_ERROR_MESS'];
    unset($_SESSION['SUBMIT_APPROVE_WORKFLOW_ERROR']);
    unset($_SESSION['SUBMIT_APPROVE_WORKFLOW_ERROR_MESS']);
}
?>
    <div id="popup-jo-submit-alert" class="popup-window bx-disk-alert-popup popup-jo-submit-alert" style="display: <?=$isShowPopUp?>;">
        <div id="popup-window-content-bx-disk-status-action" class="popup-window-content">
            <div class="bx-disk-alert" style="display: block;">
<!--                <span class="bx-disk-aligner"></span>-->
                <span class="bx-disk-alert-text" id="popup-jo-submit-message"><?=$sShowSubmitMess?></span>
                <div class="bx-disk-alert-footer"></div>
            </div>
        </div>
        <span class="popup-window-close-icon" onclick="closePupupSubmit(this)" id="popup-jo-submit-close"></span>
    </div>

    <div id="<?= $guid ?>_placeholder" class="crm-lead-header-table-placeholder">
        <div id="<?= $guid ?>_wrap" class="crm-lead-header-table-wrap">
            <div class="crm-lead-header-table-inner-wrap">
                <table id="<?= $guid ?>_inner_wrap"
                       class="crm-lead-header-table crm-lead-header-offer crm-lead-header-table-lid">
                    <tbody>
                    <tr id="<?= $guid ?>_header">
                        <td class="crm-lead-header-header" colspan="3">
                            <div class="crm-lead-header-header-left">
                                <div class="crm-lead-header-left-inner">
                                    <span class="crm-lead-header-icon"></span>
                                    <div id="<?= $guid ?>_title" class="crm-lead-header-title">
                                        <span class="crm-lead-header-title-text"><?= htmlspecialchars($arResult['JOB_ORDER']['TITLE']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="crm-lead-header-header-right">
                                <div class="crm-lead-header-right-inner">
                                    <div class="crm-lead-header-status">
                                    <span id="crm_company_show_v12_qpv_menu_btn" class="crm-lead-header-right">
                                        <?= GetMessage("YNSIR_JOD_T_JOB_ORDER_STATUS_LABEL") ?>
                                    </span>
                                        <span id="crm_company_show_v12_qpv_menu_btn"
                                              class="crm-lead-header-right crm-lead-header-right-value">
                                            <?=htmlspecialchars($arResult['STATUS'][$arResult['JOB_ORDER']['STATUS']])?>
                                    </span>
                                    </div>
                                    <div class="crm-lead-header-contact-btns">
                                    <span id="<?= $guid ?>_pin_btn"
                                          class="crm-lead-header-contact-btn <?= $isFixed ? 'crm-lead-header-contact-btn-pin' : 'crm-lead-header-contact-btn-unpin' ?>"></span>
                                        <span id="<?= $guid ?>_toggle_btn"
                                              class="crm-lead-header-contact-btn <?= $isExpanded ? 'crm-lead-header-contact-btn-open' : 'crm-lead-header-contact-btn-close' ?>"></span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="crm-lead-header-white" colspan="3"></td>
                    </tr>
                    <tr>
                        <td class="crm-lead-header-blue" colspan="3"></td>
                    </tr>
                    <tr>
                        <td class="crm-lead-header-cell">
                            <table class="crm-lead-header-inner-table">
                                <tbody>
                                <!-- Position -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_POSITION_TITLE") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <span class="crm-client-contacts-block-text" style="max-width: 300px;">
                                            <?= htmlspecialchars($arResult['JOB_ORDER']['TITLE']) ?>
                                        </span>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <!-- Head Count -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_HEADCOUNT") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <?= htmlspecialchars($arResult['JOB_ORDER']['HEADCOUNT']) ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <!-- Expected End-date -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_EXPECTED_END_DATE") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <?= htmlspecialchars($arResult['JOB_ORDER']['EXPECTED_END_DATE']) ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="crm-lead-header-cell">
                            <table class="crm-lead-header-inner-table">
                                <tbody>
                                <!-- Department -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_DEPARTMENT") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <span class="crm-client-contacts-block-text" style="max-width: 300px;">
                                            <?= htmlspecialchars($arResult['DEPARTMENT'][$arResult['JOB_ORDER']['DEPARTMENT']]) ?>
                                        </span>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <!-- Supervisor -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_SUPERVISOR") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <div class="recruitment-candidate-info-data-wrap">
                                            <span class="recruitment-candidate-info-label-alignment"></span>
                                            <div class="crm-client-photo-wrapper">
                                                <div class="crm-client-user-def-pic">
                                                    <img alt="Author Photo"
                                                         src="<?= $arResult['DATA_USER'][$arResult['JOB_ORDER']['SUPERVISOR']]['PHOTO_SRC'] ?>">
                                                </div>
                                            </div>
                                            <span class="recruitment-candidate-info-label">
                                                <a id="user_tooltip_<?= $arResult['JOB_ORDER']['SUPERVISOR'] ?>_SUPERVISOR"
                                                   alt="A"
                                                   href="/company/personal/user/<?= $arResult['JOB_ORDER']['SUPERVISOR'] ?>/">
                                                    <?= htmlspecialchars($arResult['DATA_USER'][$arResult['JOB_ORDER']['SUPERVISOR']]['FULL_NAME']) ?>
                                                </a>
                                                <script type="text/javascript">
                                                    BX.tooltip("<?=$arResult['JOB_ORDER']['SUPERVISOR']?>", "user_tooltip_<?=$arResult['JOB_ORDER']['SUPERVISOR']?>_SUPERVISOR", "", "", false, "");
                                                </script>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <!-- Interviews -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_INTERVIEWS") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <?= count($arResult['JOB_ORDER']['INTERVIEW']) ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="crm-lead-header-cell">
                            <table class="crm-lead-header-inner-table">
                                <tbody>
                                <!-- Owner -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_OWNER") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <div class="recruitment-candidate-info-data-wrap">
                                            <span class="recruitment-candidate-info-label-alignment"></span>
                                            <div class="crm-client-photo-wrapper">
                                                <div class="crm-client-user-def-pic">
                                                    <img alt="Author Photo"
                                                         src="<?= $arResult['DATA_USER'][$arResult['JOB_ORDER']['OWNER']]['PHOTO_SRC'] ?>">
                                                </div>
                                            </div>
                                            <span class="recruitment-candidate-info-label">
                                                <a id="user_tooltip_<?= $arResult['JOB_ORDER']['OWNER'] ?>_OWNER"
                                                   alt="A"
                                                   href="/company/personal/user/<?= $arResult['JOB_ORDER']['OWNER'] ?>/">
                                                    <?= htmlspecialchars($arResult['DATA_USER'][$arResult['JOB_ORDER']['OWNER']]['FULL_NAME']) ?>
                                                </a>
                                                <script type="text/javascript">
                                                    BX.tooltip("<?=$arResult['JOB_ORDER']['OWNER']?>", "user_tooltip_<?=$arResult['JOB_ORDER']['OWNER']?>_OWNER", "", "", false, "");
                                                </script>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <!-- Recruiter -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_RECRUITER") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <div class="recruitment-candidate-info-data-wrap">
                                            <span class="recruitment-candidate-info-label-alignment"></span>
                                            <div class="crm-client-photo-wrapper">
                                                <div class="crm-client-user-def-pic">
                                                    <img alt="Author Photo"
                                                         src="<?= $arResult['DATA_USER'][$arResult['JOB_ORDER']['RECRUITER']]['PHOTO_SRC'] ?>">
                                                </div>
                                            </div>
                                            <span class="recruitment-candidate-info-label">
                                                <a id="user_tooltip_<?= $arResult['JOB_ORDER']['RECRUITER'] ?>_RECRUITER"
                                                   alt="A"
                                                   href="/company/personal/user/<?= $arResult['JOB_ORDER']['RECRUITER'] ?>/">
                                                    <?= htmlspecialchars($arResult['DATA_USER'][$arResult['JOB_ORDER']['RECRUITER']]['FULL_NAME']) ?>
                                                </a>
                                                <script type="text/javascript">
                                                    BX.tooltip("<?=$arResult['JOB_ORDER']['RECRUITER']?>", "user_tooltip_<?=$arResult['JOB_ORDER']['RECRUITER']?>_RECRUITER", "", "", false, "");
                                                </script>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <!-- Last update -->
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                        <?= GetMessage("YNSIR_JOD_T_LAST_UPDATE") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <?= $arResult['JOB_ORDER']['DATE_MODIFY'] ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="crm-lead-header-cell crm-lead-header-comments" colspan="3">
                            <table class="crm-lead-header-inner-table">
                                <tbody>
                                <tr>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title"><?= GetMessage("YNSIR_JOD_T_ANY_NOTE_TITLE") ?></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-com-cell">
                                        <div class="crm-lead-header-lhe-wrapper">
                                            <div class="crm-lead-header-lhe-view-wrapper"><?= GetMessage("YNSIR_JOD_T_ANY_NOTE_TEXT") ?></div>
                                        </div>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="<?= $guid ?>_section_wrapper" class="recruitment-candidate-main-wrap" style="display:<?= $isExpanded ? 'block' : 'none' ?>">
        <? if ($arResult['PERM']['BASIC'] == true): ?>
            <table class="recruitment-candidate-info-table">
                <tbody>
                <tr>
                    <td colspan="7">
                        <div class="recruitment-candidate-title">
                            <span class="recruitment-candidate-title-text"><?= GetMessage("YNSIR_JOD_T_BASIC_TAB") ?></span>
                        </div>
                    </td>
                </tr>
                <!-- Row 01 -->
                <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                        <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_POSITION_TITLE") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= htmlspecialchars($arResult['JOB_ORDER']['TITLE']) ?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_LEVEL") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= htmlspecialchars($arResult['LEVEL'][$arResult['JOB_ORDER']['LEVEL']]) ?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-last-td"></td>
                </tr>
                <!-- Row 02 -->
                <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_STATUS") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?=htmlspecialchars($arResult['STATUS'][$arResult['JOB_ORDER']['STATUS']])?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label"><?= GetMessage("YNSIR_JOD_T_RECRUITER") ?>
                                :</span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right crm-lead-header-inner-cell">
                        <div class="recruitment-candidate-info-data-wrap">
                        <span class="recruitment-candidate-info-label">
                            <div class="recruitment-candidate-info-data-wrap" style="text-align: left;">
                                <div class="crm-client-photo-wrapper">
                                <div class="crm-client-user-def-pic">
                                    <img alt="Author Photo"
                                         src="<?= $arResult['DATA_USER'][$arResult['JOB_ORDER']['RECRUITER']]['PHOTO_SRC'] ?>">
                                </div>
                                </div>
                                <span class="recruitment-candidate-info-label">
                                    <a id="user_tooltip_<?= $arResult['JOB_ORDER']['RECRUITER'] ?>_RECRUITER_2" alt="A"
                                       href="/company/personal/user/<?= $arResult['JOB_ORDER']['RECRUITER'] ?>/">
                                        <?= htmlspecialchars($arResult['DATA_USER'][$arResult['JOB_ORDER']['RECRUITER']]['FULL_NAME']) ?>
                                    </a>
                                    <script type="text/javascript">
                                        BX.tooltip("<?=$arResult['JOB_ORDER']['RECRUITER']?>", "user_tooltip_<?=$arResult['JOB_ORDER']['RECRUITER']?>_RECRUITER_2", "", "", false, "");
                                    </script>
                                </span>
                            </div>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-last-td"></td>
                </tr>
                <!-- Row 03 -->
                <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_DIVISION_DEPARTMENT") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= htmlspecialchars($arResult['DEPARTMENT'][$arResult['JOB_ORDER']['DEPARTMENT']]) ?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_EXPECTED_END_DATE") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= htmlspecialchars($arResult['JOB_ORDER']['EXPECTED_END_DATE']) ?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-last-td"></td>
                </tr>
                <!-- Row 04 -->
                <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_SUPERVISOR_LINE_MANAGER") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right crm-lead-header-inner-cell">
                        <div class="recruitment-candidate-info-data-wrap">
                        <span class="recruitment-candidate-info-label">
                            <div class="recruitment-candidate-info-data-wrap" style="text-align: left;">
                                <div class="crm-client-photo-wrapper">
                                <div class="crm-client-user-def-pic">
                                    <img alt="Author Photo"
                                         src="<?= $arResult['DATA_USER'][$arResult['JOB_ORDER']['SUPERVISOR']]['PHOTO_SRC'] ?>">
                                </div>
                                </div>
                                <span class="recruitment-candidate-info-label">
                                    <a id="user_tooltip_<?= $arResult['JOB_ORDER']['SUPERVISOR'] ?>_SUPERVISOR_2"
                                       alt="A"
                                       href="/company/personal/user/<?= $arResult['JOB_ORDER']['SUPERVISOR'] ?>/">
                                        <?= htmlspecialchars($arResult['DATA_USER'][$arResult['JOB_ORDER']['SUPERVISOR']]['FULL_NAME']) ?>
                                    </a>
                                    <script type="text/javascript">
                                        BX.tooltip("<?=$arResult['JOB_ORDER']['SUPERVISOR']?>", "user_tooltip_<?=$arResult['JOB_ORDER']['SUPERVISOR']?>_SUPERVISOR_2", "", "", false, "");
                                    </script>
                                </span>
                            </div>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_SUBORDINATES") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right crm-lead-header-inner-cell">
                        <div class="recruitment-candidate-info-data-wrap">
                        <span class="recruitment-candidate-info-label">
                            <?php
                            foreach ($arResult['JOB_ORDER']['SUBORDINATE'] as $iIdSubordinates) {
                                ?>
                                <div style="clear: both; margin-bottom: 20px;">
                                    <div class="recruitment-candidate-info-data-wrap" style="text-align: left;">
                                        <div class="crm-client-photo-wrapper">
                                        <div class="crm-client-user-def-pic">
                                            <img alt="Author Photo"
                                                 src="<?= $arResult['DATA_USER'][$iIdSubordinates]['PHOTO_SRC'] ?>">
                                        </div>
                                        </div>
                                        <span class="recruitment-candidate-info-label">
                                            <a id="user_tooltip_<?= $iIdSubordinates ?>_SUBORDINATES" alt="A"
                                               href="/company/personal/user/<?= $iIdSubordinates ?>/">
                                                <?= htmlspecialchars($arResult['DATA_USER'][$iIdSubordinates]['FULL_NAME']) ?>
                                            </a>
                                            <script type="text/javascript">
                                                BX.tooltip("<?=$iIdSubordinates?>", "user_tooltip_<?=$iIdSubordinates?>_SUBORDINATES", "", "", false, "");
                                            </script>
                                        </span>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-last-td"></td>
                </tr>
                <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_HEADCOUNTS") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= htmlspecialchars($arResult['JOB_ORDER']['HEADCOUNT']) ?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-left"></td>
                    <td class="recruitment-candidate-info-right"></td>
                    <td class="recruitment-candidate-last-td"></td>
                </tr>
                </tbody>
            </table>
        <? endif; ?>
        <?php if ($arResult['PERM']['SENSITIVE'] == true): ?>
            <table class="recruitment-candidate-info-table">
                <tbody>
                <tr>
                    <td colspan="7">
                        <div class="recruitment-candidate-title">
                            <span class="recruitment-candidate-title-text"><?= GetMessage("YNSIR_JOD_T_SENSITIVE_TAB") ?></span>
                        </div>
                    </td>
                </tr>
                <!-- Row 01 -->
                <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                        <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_TYPE") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?php
                            echo $arResult['JOB_ORDER']['IS_REPLACE'] == 1 ? GetMessage("YNSIR_JOD_T_TYPE_REPLACE") : GetMessage("YNSIR_JOD_T_TYPE_NEW");
                            ?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-left"></td>
                    <td class="recruitment-candidate-info-right"></td>
                    <td class="recruitment-candidate-last-td"></td>
                </tr>
                <!-- Row 02 -->
                <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                        <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_VACANCY_REASON") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?= htmlspecialchars($arResult['JOB_ORDER']['VACANCY_REASON']) ?>
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-left">
                        <div class="recruitment-candidate-info-label-wrap">
                        <span class="recruitment-candidate-info-label">
                            <?= GetMessage("YNSIR_JOD_T_RANGE_SALARY") ?>:
                        </span>
                        </div>
                    </td>
                    <td class="recruitment-candidate-info-right">
                        <div class="recruitment-candidate-info-data-wrap">
                            <span class="recruitment-candidate-info-label-alignment"></span>
                            <span class="recruitment-candidate-info-label">
                            <?php
                            if (intval($arResult['JOB_ORDER']['SALARY_FROM']) > 0)
                                echo $arResult['JOB_ORDER']['SALARY_FROM'];
                            if (intval($arResult['JOB_ORDER']['SALARY_FROM']) > 0
                                && intval($arResult['JOB_ORDER']['SALARY_TO']) > 0)
                                echo ' ';
                            if (intval($arResult['JOB_ORDER']['SALARY_TO']) > 0)
                                echo ' - ' . $arResult['JOB_ORDER']['SALARY_TO'];
                            ?>
                            (<?= GetMessage('YNSIR_JOD_T_CURRENCY') ?>)
                            </span>
                        </div>
                        <?php
                        if($bShowNoteSalary == "Y"){
                            ?>
                            <div class="recruitment-candidate-info-data-wrap" style="padding-left: 4px;">
                                <span class="recruitment-candidate-info-label">
                                    <?=htmlspecialchars($arResult['JOB_ORDER']['NOTE_SALARY'])?>
                                </span>
                            </div>
                            <?php
                        }
                        ?>
                    </td>
                    <td class="recruitment-candidate-last-td"></td>
                </tr>
                </tbody>
            </table>
        <? endif; ?>
        <?php if ($arResult['PERM']['INTERVIEWS'] == true): ?>
            <table id="section_lead_info_contents" class="crm-offer-info-table">
                <tbody>
                <tr id="section_lead_info">
                    <td colspan="5">
                        <div class="crm-offer-title">
                        <span class="crm-offer-title-text recruitment-candidate-title-text">
                            <?= GetMessage("YNSIR_JOD_T_INTERVIEWS_TAB") ?>
                        </span>
                        </div>
                    </td>
                </tr>
                <tr id="opportunity_wrap" class="crm-offer-row">
                    <td colspan="5">
                        <?php
                        $iCountRount = 1;
                        foreach ($arResult['JOB_ORDER']['INTERVIEW'] as $arRoundData) {
                            ?>
                            <div id="interview-round">
                                <div class="interview-round">
                                    <div class="interview-round-lable">
                                        <?= GetMessage("YNSIR_JOD_T_ROUND") . ' ' . $iCountRount ?>
                                    </div>
                                    <div class="interview-round-content">
                                        <div class="interview-round-participant-text">
                                        <span>
                                            <?= GetMessage("YNSIR_JOD_T_PARTICIPANTS") ?>:
                                        </span>
                                        </div>
                                        <div class="interview-round-participant-data crm-lead-header-inner-cell">
                                            <div class="participant-profile-info">
                                                <?php
                                                foreach ($arRoundData['PARTICIPANT'] as $iIdUserParticipant) {
                                                    ?>
                                                    <div style="clear: both; margin-bottom: 20px;">
                                                        <div class="recruitment-candidate-info-data-wrap"
                                                             style="text-align: left;">
                                                            <div class="crm-client-photo-wrapper">
                                                                <div class="crm-client-user-def-pic">
                                                                    <img alt="Author Photo"
                                                                         src="<?= $arResult['DATA_USER'][$iIdUserParticipant]['PHOTO_SRC'] ?>">
                                                                </div>
                                                            </div>
                                                            <span class="recruitment-candidate-info-label">
                                                            <a id="user_tooltip_<?= $iIdUserParticipant ?>_PARTICIPANTS_<?= $iCountRount ?>"
                                                               alt="A"
                                                               href="/company/personal/user/<?= $iIdUserParticipant ?>/">
                                                                <?= htmlspecialchars($arResult['DATA_USER'][$iIdUserParticipant]['FULL_NAME']) ?>
                                                            </a>
                                                            <script type="text/javascript">
                                                                BX.tooltip("<?=$iIdUserParticipant?>", "user_tooltip_<?=$iIdUserParticipant?>_PARTICIPANTS_<?=$iCountRount?>", "", "", false, "");
                                                            </script>
                                                        </span>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="interview-round-note-text">
                                            <span><?= GetMessage("YNSIR_JOD_T_NOTE") ?>: </span>
                                        </div>
                                        <div class="interview-round-note-data">
                                            <?= htmlspecialchars($arRoundData['NOTE']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $iCountRount++;
                        }
                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
        <? endif; ?>
        <?php if ($arResult['PERM']['DESCRIPTION'] == true): ?>
            <table class="crm-offer-info-table crm-offer-main-info-text">
                <tbody>
                <tr>
                    <td  colspan="5" onclick="collapseDescription()" style="cursor:pointer">
                        <div class="crm-offer-title">
                            <div id="title_description" class="icon-collages icon-show-more"></div>
                        <span class="crm-offer-title-text recruitment-candidate-title-text" style="margin-left: 20px;">
                            <?= GetMessage("YNSIR_JOD_T_DESCRIPTION") ?>
                        </span>
                            <span class="crm-offer-title-set-wrap"></span>
                        </div>
                    </td>
                </tr>
                <tr class="crm-offer-row" style="display: none" id="content_description">
                    <td class="crm-offer-info-drg-btn"></td>
                    <td class="crm-offer-info-left">
                        <div class="crm-offer-info-label-wrap">
                            <span class="crm-offer-info-label-alignment"></span>
                            <span class="crm-offer-info-label">
                            <?= GetMessage("YNSIR_JOD_T_CONTENT") ?>:
                        </span>
                        </div>
                    </td>
                    <td  class="crm-offer-info-right " colspan="2" >
                        <div>
                            <div class="crm-fld-block-readonly">
                                <span class="crm-offer-info-label-alignment"></span>
                                <?= $arResult['JOB_ORDER']['DESCRIPTION'] ?>
                            </div>
                        </div>
                    </td>
                    <td class="crm-offer-last-td"></td>
                </tr>
                </tbody>
            </table>
        <? endif; ?>
    </div>
<?php

if(!$arResult['IS_NEW_STATUS']) {
    $arTabs[] = array(
        'id' => 'tab_activity',
        'name' => "Activity",
        'title' => "Activity",
        'icon' => '',
        'fields' => $arResult['FIELDS']['tab_activity']
    );
    /*
     * Edit by nhatth2
     * Todo Template Tab
     * EVENT LIST
     */

    $arTabs[] = array(
        'id' => 'tab_order_associate_list',
        'name' => GetMessage('YNSIR_JOD_TAB_ASSOCIATE'),
        'title' => GetMessage('YNSIR_JOD_TAB_ASSOCIATE_TITLE'),
        'icon' => '',
        'fields' => $arResult['FIELDS']['tab_order_associate_list']
    );
    $arTabs[] = array(
        'id' => 'tab_bizproc',
        'name' => GetMessage('YNSIR_JOD_TAB_BIZPROC'),
        'title' => GetMessage('YNSIR_JOD_TAB_BIZPROC_TITLE'),
        'icon' => '',
        'fields' => $arResult['FIELDS']['tab_bizproc']
    );
}
$arTabs[] = array(
    'id' => 'tab_event',
    'name' => GetMessage('YNSIR_JOD_TAB_HISTORY'),
    'title' => GetMessage('YNSIR_JOD_TAB_HISTORY_TITLE'),
    'icon' => '',
    'fields' => $arResult['FIELDS']['tab_event']
);
$arTabs[] = array(
    'id' => 'tab_feedback',
    'name' => GetMessage('YNSIR_TAB_FEEDBACK'),
    'title' => GetMessage('YNSIR_TAB_FEEDBACK'),
    'icon' => '',
    'fields' => $arResult['FIELDS']['tab_feedback']
);
// if iframe not show tab
if (!$isIFrame) :

$APPLICATION->IncludeComponent(
    'ynsirecruitment:ynsir.interface.form',
    '',
    array(
        'FORM_ID' => $arResult['FORM_ID'],
        'THEME_GRID_ID' => $arResult['GRID_ID'],
        'TABS' => $arTabs,
        'BUTTONS' => array('standard_buttons' => false),
        'DATA' => null,//$arParams['~DATA'],
    ),
    $component, array('HIDE_ICONS' => 'Y')
);
endif;
/*
 * END EVENT LIST
 */
$bizprocDispatcherID = strtolower($arResult['FORM_ID']).'_bp_disp';
$arConfigMessage = array(
        'YNSIR_APPROVAL_ALERT' => GetMessage('YNSIR_SUBMIT_APPROVE_CONFIRM'),
        'YNSIR_APPROVAL_TITLE' => GetMessage('YNSIR_SUBMIT_APPROVE_CONFIRM_TITLE'),
        'YNSIR_APPROVAL_SUBMIT_BTN' => GetMessage("YNSIR_JOD_T_BTN_SUBMIR"),
        'YNSIR_APPROVAL_CANCEL_BTN' => GetMessage("YNSIR_JOD_T_BTN_CANCEL")
);
if($iHidePupupSubmit == 1){
    ?>
    <script>
        setTimeout(function(){ $('#popup-jo-submit-alert').hide(500); }, 3000);
    </script>
    <?php
}
?>
<script>
    var _id_job_order = <?=$arResult['ID']?>;
    var _url_submit = "/recruitment/job-order/detail/" + <?=$arResult['ID']?>+'/';
    BX.ready(
        function () {
            BX.JobOrderQuickPanelView.create(
                "<?=CUtil::JSEscape($guid)?>",
                {
                    prefix: "<?=CUtil::JSEscape($guid)?>",
                    config: <?=CUtil::PhpToJSObject($config)?>,
                    serviceUrl: "<?='/bitrix/components/ynsirecruitment/job_order.detail/settings.php?' . bitrix_sessid_get()?>"
                }
            );
//            BP
            BX.JobOrderControl.create(
                "<?=CUtil::JSEscape($guid)?>",
                {
                    submitbtn:'SUBMIT_APPROVE_BTN',
                    serviceUrl: '<?=$arResult['BIZ']['APPROVE']['TEMPLATES']['URL']?>',
                    messages:<?=CUtil::PhpToJSObject($arConfigMessage)?>
                }
            );
            var bpContainerId = "<?=$arResult['BIZPROC_CONTAINER_ID']?>";
            if(!BX(bpContainerId))
            {
                return;
            }

            BX.YNSIRBizprocDispatcher.create(
                "<?=CUtil::JSEscape($bizprocDispatcherID)?>",
                {
                    containerID: bpContainerId,
                    entityTypeName: "<?=YNSIROwnerType::OrderName?>",
                    entityID: <?=$arResult['ID']?>,
                    serviceUrl: "/bitrix/components/ynsirecruitment/job_order.detail/bizproc.php?job_order_id=<?=$arResult['ELEMENT_ID']?>&post_form_uri=<?=urlencode($arResult['POST_FORM_URI'])?>&<?=bitrix_sessid_get()?>",
                    formID: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
                    pathToEntityShow: "<?=CUtil::JSEscape("/recruitment/job-order/detail/".$arResult['ID']."/")?>"
                }
            );
//            end BP

        }
    )
    <?
    if(isset($_GET['RECRUITMENT_JOB_ORDER_SHOW_V1_active_tab'])){
        ?>
        $('html,body').animate({scrollTop: $("#RECRUITMENT_JOB_ORDER_SHOW_V1_tab_block").offset().top},'slow');
        <?
    }
    ?>
</script>

<?php
// iframe
if ($isIFrame):?>
    </div>
    </div>
    </body>
    </html><?
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
    die();
endif;
?>