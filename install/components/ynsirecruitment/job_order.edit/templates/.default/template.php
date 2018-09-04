<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
CJSCore::Init(array("jquery"));
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');
$this->SetViewTarget("pagetitle", 1000);
?>
<div class="pagetitle-container pagetitle-align-right-container">
    <a href="/recruitment/job-order/" class="ynsir-cot-back">
        <?=GetMessage("YNSIR_CJOE_BTN_BACK")?>
    </a>
</div>
<?
$this->EndViewTarget();
if($arResult['ID'] != $arResult['DATA']['ID'] && $arResult['ID'] > 0){
    ShowError(GetMessage("YNSIR_CJOD_JOB_ORDER_NOT_FOUND"));
    return;
}
$bShowNoteSalary = COption::GetOptionString("ynsirecruitment", "ynsir_order_salary_note");
$arFieldsPrepare = array();
if($bShowNoteSalary == 'Y') $arFieldsPrepare[] = 'NOTE_SALARY';
$sTextShowRequire = '*';
?>
<div id="ynsir_job_order">
    <form name="" action="<?=$arResult['URL_FORM_SUBMIT']?>" method="POST"
          onsubmit="return submitJobOrderForm(this);" class="recruitment-candidate-form" enctype="multipart/form-data">
        <div class="crm-offer-main-wrap">
            <?if($arResult['PERM'][YNSIRConfig::OS_BASIC_INFO] == true):?>
                <?php $arFieldsPrepare[] = 'TITLE';?>
                <table class="recruitment-jo-info-table">
                    <tbody>
                    <tr>
                        <td colspan="5">
                            <div class="recruitment-jo-title">
                                <span class="recruitment-jo-title-text"><?=GetMessage("YNSIR_CJOE_BASIC")?></span>
                            </div>
                        </td>
                    </tr>

                    <tr class="recruitment-jo-row" data-dragdrop-context="field">
                        <!--Position title-->
                        <td class="recruitment-jo-info-left">
                            <div class="recruitment-jo-info-label-wrap">
                                <span class="recruitment-jo-info-label-alignment"></span>
                                <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_POSITION_TITLE")?><span class="jo-field-require"><?=$sTextShowRequire?></span>: </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <input type="text" class="recruitment-jo-item-inp" id="ynsir_jo_TITLE" name="TITLE"
                                       value="<?= htmlspecialchars($arResult['DATA']['TITLE'], ENT_QUOTES) ?>"/>
                                <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                            </div>
                        </td>
                        <!--Level-->
                        <td class="recruitment-jo-info-left">
                            <div class="recruitment-jo-info-label-wrap">
                                <span class="recruitment-jo-info-label-alignment"></span>
                                <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_LEVEL")?><span class="jo-field-require"><?=$sTextShowRequire?></span>: </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <select class="recruitment-item-table-select" id="ynsir_jo_LEVEL" name="LEVEL" onchange="onchangeExtendsData(this)">
                                    <?php
                                    foreach ($arResult['LEVEL'] as $iIdLevel => $arTitleLevel){
                                        $sSelected = $iIdLevel == $arResult['DATA']['LEVEL'] ? 'selected' : '';
                                        ?>
                                        <option value="<?=$iIdLevel?>" <?=$sSelected?>><?=$arTitleLevel['NAME_' . strtoupper(LANGUAGE_ID)]?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <div class="extends-data">
                                    <?php
                                    foreach ($arResult['LEVEL'] as $iIdLevel => $arTitleLevel){
                                        $sDefault1 = '';
                                        $sDefault2 = '';
                                        if($iIdLevel == $arResult['DATA']['LEVEL']){
                                            $sDefault1 = $arResult['DATA']['LEVEL_NOTE'];
                                            if($arTitleLevel['ADDITIONAL_INFO'] == YNSIRConfig::YNSIR_TYPE_LIST_USER){
                                                $sDefault2 = $arResult['DATA_USER'][$sDefault1]['FULL_NAME'];
                                            }
                                        }
                                        echo YNSIRJobOrderEdit::htmlExtendsDataItem($arTitleLevel, 'LEVEL', $sDefault1, $sDefault2);
                                    }
                                    ?>
                                </div>
                                <script>setDefalutExtentionData('ynsir_jo_LEVEL')</script>
                                <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                            </div>
                        </td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                    <?php
                    if(isset($arResult['DATA']['STATUS']) && $arResult['DATA']['STATUS'] != JOStatus::JOSTATUS_NEW
                        && $arResult['DATA']['STATUS'] != JOStatus::JOSTATUS_WAITING):
                        ?>
                        <tr class="recruitment-jo-row" data-dragdrop-context="field">
                            <!--Status-->
                            <td class="recruitment-jo-info-left">
                                <div class="recruitment-jo-info-label-wrap">
                                    <span class="recruitment-jo-info-label-alignment"></span>
                                    <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_STATUS")?><span class="jo-field-require"><?=$sTextShowRequire?></span>: </span>
                                </div>
                            </td>
                            <td class="recruitment-jo-info-right">
                                <div class="recruitment-jo-info-data-wrap">
                                    <select class="recruitment-item-table-select" id="ynsir_jo_STATUS" name="STATUS">
                                        <?php
                                        foreach ($arResult['JO_STATUS'] as $sKeyStatus => $sNameStatus) {
                                            $sSSelected = $sKeyStatus == $arResult['DATA']['STATUS'] ? 'selected' : '';
                                            ?>
                                            <option value="<?=$sKeyStatus?>" <?=$sSSelected?>><?=$sNameStatus?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;">
                                        <span></span>
                                    </div>
                                </div>
                            </td>
                            <!--Recruiter-->
                            <td class="recruitment-jo-info-left">
                                <div class="recruitment-jo-info-label-wrap">
                                    <span class="recruitment-jo-info-label-alignment"></span>
                                    <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_RECRUITER")?><span class="jo-field-require"><?=$sTextShowRequire?></span>: </span>
                                </div>
                            </td>
                            <td class="recruitment-jo-info-right">
                                <div class="recruitment-jo-info-data-wrap">
                                    <?php
                                    ob_start();
                                    CCrmViewHelper::RenderUserCustomSearch(
                                        array(
                                            'ID' => 'ynsir_jo_RECRUITER_id',
                                            'SEARCH_INPUT_ID' => 'ynsir_jo_RECRUITER_crm',
                                            'DATA_INPUT_ID' => 'RECRUITER',
                                            'COMPONENT_NAME' => 'RECRUITER',
                                            'NAME_FORMAT' => CSite::GetNameFormat(false),
                                            'USER' => array(
                                                'ID' => $arResult['DATA']['RECRUITER'],
                                                'NAME' => $arResult['DATA_USER'][$arResult['DATA']['RECRUITER']]['FULL_NAME']
                                            )
                                        )
                                    );
                                    $userSelectorHtml = ob_get_contents();
                                    ob_end_clean();
                                    echo $userSelectorHtml;
                                    ?>
                                    <span id="ynsir_jo_RECRUITER"></span>
                                    <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                                </div>
                            </td>
                            <td class="recruitment-jo-last-td"></td>
                        </tr>
                        <?php
                    endif;
                    ?>
                    <tr class="recruitment-jo-row" data-dragdrop-context="field">
                        <!--Division/Dept-->
                        <td class="recruitment-jo-info-left">
                            <div class="recruitment-jo-info-label-wrap">
                                <span class="recruitment-jo-info-label-alignment"></span>
                                <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_DIVISION_DEPARTMENT")?><span class="jo-field-require"><?=$sTextShowRequire?></span>: </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <select class="recruitment-item-table-select" id="ynsir_jo_DEPARTMENT" name="DEPARTMENT">
                                    <?php
                                    foreach ($arResult['DEPARTMENT'] as $iIdDepartment => $arNameDepartment){
                                        $sSelected = $iIdDepartment == $arResult['DATA']['DEPARTMENT'] ? 'selected' : '';
                                        ?>
                                        <option value="<?=$iIdDepartment?>" <?=$sSelected?>><?=$arNameDepartment?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                            </div>
                        </td>
                        <!--Expected end date-->
                        <td class="recruitment-jo-info-left">
                            <div class="recruitment-jo-info-label-wrap">
                                <span class="recruitment-jo-info-label-alignment"></span>
                                <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_EXPECTED_END_DATE")?><span class="jo-field-require"><?=$sTextShowRequire?></span>: </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap" style="padding-bottom: 5px">
                            <span class="profile-item-table-date">
                                    <input style="width: 50%" type="text" class="recruitment-jo-item-inp" id="ynsir_jo_EXPECTED_END_DATE"
                                           name="EXPECTED_END_DATE" value="<?=$arResult['DATA']['EXPECTED_END_DATE']?>" size="50">
                                    <span class="profile-date-button" id="dob_date_btn"></span>
                                    <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;">
                                        <span></span>
                                    </div>
                                    <script>
                                        BX.ready(function () {
                                            BX.YSIRDateLinkField.create(BX('ynsir_jo_EXPECTED_END_DATE'), BX('dob_date_btn'), {
                                                showTime: false,
                                                setFocusOnShow: false
                                            });
                                        });
                                    </script>
                                </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                    <tr class="recruitment-jo-row" data-dragdrop-context="field">
                        <td class="recruitment-jo-info-left">
                            <div class="recruitment-jo-info-label-wrap">
                                <span class="recruitment-jo-info-label-alignment"></span>
                                <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_SUPERVISOR_LINE_MANAGER")?><span class="jo-field-require"><?=$sTextShowRequire?></span>: </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <?php
                                ob_start();
                                CCrmViewHelper::RenderUserCustomSearch(
                                    array(
                                        'ID' => 'ynsir_jo_SUPERVISOR_id',
                                        'SEARCH_INPUT_ID' => 'ynsir_jo_SUPERVISOR_crm',
                                        'DATA_INPUT_ID' => 'SUPERVISOR',
                                        'COMPONENT_NAME' => 'SUPERVISOR',
                                        'NAME_FORMAT' => CSite::GetNameFormat(false),
                                        'USER' => array(
                                            'ID' => $arResult['DATA']['SUPERVISOR'],
                                            'NAME' => $arResult['DATA_USER'][$arResult['DATA']['SUPERVISOR']]['FULL_NAME']
                                        )
                                    )
                                );
                                $userSelectorHtml = ob_get_contents();
                                ob_end_clean();
                                echo $userSelectorHtml;
                                ?>
                                <span id="ynsir_jo_SUPERVISOR"></span>
                                <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                            </div>
                        </td>
                        <!--Subordinates-->
                        <td class="recruitment-jo-info-left">
                            <div class="recruitment-jo-info-label-wrap">
                                <span class="recruitment-jo-info-label-alignment"></span>
                                <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_SUBORDINATES")?>: </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <div class="subordinates-profile-info">
                                    <div id="job_order_subordinate" class="job-order-subordinate">
                                        <?php
                                        foreach ($arResult['DATA']['SUBORDINATE'] as $iIdSubordinate) {
                                            ?>
                                            <div class="ynsir-list-summary-wrapper">
                                                <div class="ynsir-list-photo-wrapper">
                                                    <div class="ynsir-list-def-pic">
                                                        <img alt="Author Photo" src="<?=$arResult['DATA_USER'][$iIdSubordinate]['PHOTO_SRC']?>">
                                                    </div>
                                                </div>
                                                <div class="ynsir-list-info-wrapper">
                                                    <div class="ynsir-list-title-wrapper">
                                                        <a href="/company/personal/user/<?=$iIdSubordinate?>/" id="job_order_subordinate_<?=$iIdSubordinate?>">
                                                            <?=htmlspecialchars($arResult['DATA_USER'][$iIdSubordinate]['FULL_NAME'], ENT_QUOTES)?>
                                                        </a>
                                                        <script type="text/javascript">BX.tooltip(<?=$iIdSubordinate?>, "job_order_subordinate_<?=$iIdSubordinate?>", "");</script>
                                                    </div>
                                                </div>
                                                <div class="ynsir-list-info-wrapper">
                                                    <div class="interview-round-delete user-delete" onclick="deleteParticipant(this)"></div>
                                                </div>
                                                <input class="subordinate-user-<?=$iIdSubordinate?>" name="SUBORDINATE[]" value="<?=$iIdSubordinate?>" hidden>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <div style="padding-top: 5px; clear: both;" class="add-user-subordinates">
                                        <a href="javascript:void(1);" onclick="showUserPopup(this, 'SUBORDANATE')">+ Add</a>
                                    </div>
                                </div>
                                <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                            </div>
                        </td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                    <tr class="recruitment-jo-row" data-dragdrop-context="field">
                        <!--Headcounts-->
                        <td class="recruitment-jo-info-left">
                            <div class="recruitment-jo-info-label-wrap">
                                <span class="recruitment-jo-info-label-alignment"></span>
                                <span class="recruitment-jo-info-label"><?=GetMessage("YNSIR_CJOE_HEADCOUNTS")?><span class="jo-field-require"><?=$sTextShowRequire?></span>: </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <input onkeyup="numberInput(this, 4)" type="number" class="recruitment-jo-item-inp" id="ynsir_jo_HEADCOUNT" name="HEADCOUNT"
                                       value="<?= htmlspecialchars($arResult['DATA']['HEADCOUNT'], ENT_QUOTES) ?>">
                                <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-left"></td>
                        <td class="recruitment-jo-info-right"></td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                    </tbody>
                </table>
            <?endif;?>
            <?if($arResult['PERM'][YNSIRConfig::OS_SENSITIVE] == true):?>
                <?php $arFieldsPrepare[] = 'VACANCY_REASON';?>
                <table class="crm-offer-info-table">
                    <tbody>
                    <tr id="section_lead_info">
                        <td colspan="5">
                            <div class="crm-offer-title">
                                <span class="crm-offer-title-text recruitment-jo-title-text"><?=GetMessage("YNSIR_CJOE_SENSITIVE")?></span>
                            </div>
                        </td>
                    </tr>
                    <tr class="recruitment-jo-row" data-dragdrop-context="field">
                        <!-- New or Replace -->
                        <td class="recruitment-jo-info-left">
                            <div class="crm-offer-info-label-wrap">
                                <span class="crm-offer-info-label-alignment"></span>
                                <span class="crm-offer-info-label">
                                <label for="ynsir_jo_IS_REPLACE">
                                    <?=GetMessage("YNSIR_CJOE_NEW_OR_REPLACE_TYPE")?>:
                                </label>
                            </span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="crm-offer-info-data-wrap" style="margin-top: 2px;">
                                <?php
                                $sNewChecked = $arResult['DATA']['IS_REPLACE'] == 1 ? '' : 'checked';
                                $sReplaceChecked = $arResult['DATA']['IS_REPLACE'] == 1 ? 'checked' : '';
                                ?>
                                <label><input type="radio" name="IS_REPLACE" value="0" <?=$sNewChecked?>><?=GetMessage('YNSIR_CJOE_NEW')?></label>
                                <label><input type="radio" name="IS_REPLACE" value="1" <?=$sReplaceChecked?>><?=GetMessage('YNSIR_CJOE_REPLACE')?></label>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-left"></td>
                        <td class="recruitment-jo-info-right"></td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                    <tr class="recruitment-jo-row" data-dragdrop-context="field">
                        <!-- Vacancy reason -->
                        <td class="recruitment-jo-info-left">
                            <div class="crm-offer-info-label-wrap">
                                <span class="crm-offer-info-label-alignment"></span>
                                <span class="crm-offer-info-label"><?=GetMessage("YNSIR_CJOE_VACANCY_REASON")?><span class="jo-field-require"><?=$sTextShowRequire?></span>:</span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="crm-offer-info-data-wrap">
                                <textarea class="recruitment-edit-text-area" id="ynsir_jo_VACANCY_REASON" name="VACANCY_REASON" cols="40" rows="3"><?=htmlspecialchars($arResult['DATA']['VACANCY_REASON'], ENT_QUOTES)?></textarea>
                                <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                            </div>
                        </td>
                        <!-- Range salary -->
                        <td class="recruitment-jo-info-left">
                            <div class="crm-offer-info-label-wrap">
                                <span class="crm-offer-info-label-alignment"></span>
                                <span class="crm-offer-info-label"><?=GetMessage("YNSIR_CJOE_RANGE_SALARY")?>:</span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <span class="range-salary">
                                    <input onkeyup="addSpacesEvent(this, 9)" type="text" class="recruitment-jo-item-inp" id="ynsir_jo_SALARY_FROM"
                                           name="SALARY_FROM" value="<?=$arResult['DATA']['SALARY_FROM']?>" placeholder="<?=GetMessage('YNSIR_CJOE_SALARY_FROM')?>">
                                </span>
                                <span class="range-salary-padding">-</span>
                                <span class="range-salary">
                                    <input onkeyup="addSpacesEvent(this, 9)" type="text" class="recruitment-jo-item-inp" id="ynsir_jo_SALARY_TO"
                                           name="SALARY_TO" value="<?=$arResult['DATA']['SALARY_TO']?>" placeholder="<?=GetMessage('YNSIR_CJOE_SALARY_TO')?>">
                                </span>
                                <?php
                                if($bShowNoteSalary == 'Y'){
                                    ?>
                                    <span class="note-salary-range">
                                        <textarea id="ynsir_jo_NOTE_SALARY" name="NOTE_SALARY" placeholder="<?=GetMessage("YNSIR_CJOE_NOTE_SALARY")?>"><?= htmlspecialchars($arResult['DATA']['NOTE_SALARY'], ENT_QUOTES) ?></textarea>
                                    </span>
                                    <?php
                                }
                                ?>
                                <div class="recruitment-jo-item-error-label error" hidden="" style="display: none;"></div>
                            </div>
                        </td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                    </tbody>
                </table>
            <?endif;?>
            <?if($arResult['PERM'][YNSIRConfig::OS_INTERVIEWS] == true):?>
                <table class="crm-offer-info-table">
                    <tbody>
                    <tr id="section_lead_info">
                        <td colspan="5">
                            <div class="crm-offer-title">
                                <span class="crm-offer-title-text recruitment-jo-title-text"><?=GetMessage("YNSIR_CJOE_INTERVIEWS")?></span>
                            </div>
                        </td>
                    </tr>
                    <!-- Participants -->
                    <!-- Note -->
                    <tr id="opportunity_wrap" class="crm-offer-row" data-dragdrop-context="field" data-dragdrop-id="OPPORTUNITY">
                        <td colspan="5">
                            <div id="interview-round">
                                <?php
                                $iCountRound = 1;
                                foreach ($arResult['DATA']['INTERVIEW'] as $iKeyRound => $arItemRound) {
                                    ?>
                                    <div class="interview-round" role-name-participant="INTERVIEW_PARTICIPANT[<?=$iKeyRound?>][]">
                                        <div class="interview-round-lable"><?=GetMessage("YNSIR_CJOE_ROUND")?> <?=$iCountRound?></div>
                                        <div class="interview-round-content">
                                            <div class="interview-round-participant-text">
                                                <span><?=GetMessage("YNSIR_CJOE_PARTICIPANTS")?>: </span>
                                            </div>
                                            <div class="interview-round-participant-data">
                                                <div class="participant-profile-info">
                                                    <?php
                                                    foreach ($arItemRound['PARTICIPANT'] as $iIdPerticipant) {
                                                        ?>
                                                        <div class="ynsir-list-summary-wrapper">
                                                            <div class="ynsir-list-photo-wrapper">
                                                                <div class="ynsir-list-def-pic">
                                                                    <img alt="Author Photo" src="<?=$arResult['DATA_USER'][$iIdPerticipant]['PHOTO_SRC']?>">
                                                                </div>
                                                            </div>
                                                            <div class="ynsir-list-info-wrapper">
                                                                <div class="ynsir-list-title-wrapper">
                                                                    <a href="/company/personal/user/<?=$iIdPerticipant?>/" id="job_order_participant_<?=$iKeyRound.$iIdPerticipant?>">
                                                                        <?=htmlspecialchars($arResult['DATA_USER'][$iIdPerticipant]['FULL_NAME'], ENT_QUOTES)?>
                                                                    </a>
                                                                    <script type="text/javascript">BX.tooltip(<?=$iIdPerticipant?>, "job_order_participant_<?=$iKeyRound.$iIdPerticipant?>", "");</script>
                                                                </div>
                                                            </div>
                                                            <div class="ynsir-list-info-wrapper">
                                                                <div class="interview-round-delete user-delete" onclick="deleteParticipant(this)"></div>
                                                            </div>
                                                            <input class="participant-user-1" name="INTERVIEW_PARTICIPANT[<?=$iKeyRound?>][]" value="<?=$iIdPerticipant?>" hidden>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <div class="add-user-participant">
                                                    <a href="javascript:void(1);" onclick="showUserPopup(this, 'PARTICIPANT')">+ <?=GetMessage("YNSIR_CJOE_BTN_ADD")?></a>
                                                </div>
                                            </div>
                                            <div class="interview-round-note-text">
                                                <span><?=GetMessage("YNSIR_CJOE_NOTE")?>: </span>
                                            </div>
                                            <div class="interview-round-note-data">
                                                <textarea name="INTERVIEW_NOTE[<?=$iKeyRound?>]"><?=$arItemRound['NOTE']?></textarea>
                                            </div>
                                            <div class="interview-round-delete" onclick="deleteRound(this)"></div>
                                        </div>
                                    </div>
                                    <?php
                                    $iCountRound++;
                                }
                                ?>
                            </div>
                            <div class="interview-add-round">
                                <a href="javascript:void(0);" onclick="addRound()">+ <?=GetMessage("YNSIR_CJOE_ADD_ROUND")?></a>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            <?endif;?>
            <?if($arResult['PERM'][YNSIRConfig::OS_DESCRIPTION] == true):?>
                <table class="crm-offer-info-table">
                    <tr id="section_lead_info">
                        <td colspan="5">
                            <div class="crm-offer-title">
                                <span class="crm-offer-title-text recruitment-jo-title-text"><?=GetMessage("YNSIR_CJOE_DESCRIPTION")?></span>
                            </div>
                        </td>
                    </tr>
                    <tr class="recruitment-jo-row">
                        <!-- Category -->
                        <td class="recruitment-jo-info-left">
                            <div class="crm-offer-info-label-wrap">
                                <span class="crm-offer-info-label-alignment"></span>
                                <span class="crm-offer-info-label"><?=GetMessage("YNSIR_CJOE_TEMPLATE_CATEGORY")?>:</span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <select class="recruitment-item-table-select" id="category_template" onchange="changeTCategory(this)">
                                    <?php
                                    foreach ($arResult['TEMPLATE_CATEGORY'] as $iIdTCategory => $sNameTCategory) {
                                        $sSelectedTCate = $arResult['TEMPLATE_CATEGORY_ID'] == $iIdTCategory ? 'selected' : '';
                                        ?>
                                        <option value="<?=$iIdTCategory?>" <?=$sSelectedTCate?>><?=htmlspecialchars($sNameTCategory, ENT_QUOTES)?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </td>
                        <!-- Template -->
                        <td class="recruitment-jo-info-left">
                            <div class="crm-offer-info-label-wrap">
                                <span class="crm-offer-info-label-alignment"></span>
                                <span class="crm-offer-info-label"><?=GetMessage("YNSIR_CJOE_TEMPLATE")?>:</span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="recruitment-jo-info-data-wrap">
                                <select class="recruitment-item-table-select" name="TEMPLATE_ID" id="ynsir_jo_TEMPLATE_ID" onchange="changeTemplate()">
                                    <option value="0" selected="">-<?=GetMessage("YNSIR_CJOE_NONE")?>-</option>
                                    <?php
                                    foreach ($arResult['TEMPLATE'] as $arTemplate) {
                                        $selectedTemplate = $arTemplate['ID'] == $arResult['DATA']['TEMPLATE_ID'] ? 'selected' : '';
                                        if($arTemplate['CATEGORY'] == $arResult['TEMPLATE_CATEGORY_ID']) {
                                            ?>
                                            <option value="<?=$arTemplate['ID']?>" <?=$selectedTemplate?> cate-id="<?=$arTemplate['CATEGORY']?>"><?=htmlspecialchars($arTemplate['NAME_TEMPLATE'], ENT_QUOTES)?></option>
                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                    <tr class="recruitment-jo-row">
                        <!-- Content -->
                        <td class="recruitment-jo-info-left">
                            <div class="crm-offer-info-label-wrap">
                                <span class="crm-offer-info-label-alignment"></span>
                                <span class="crm-offer-info-label"><?=GetMessage("YNSIR_CJOE_CONTENT")?>:</span>
                            </div>
                        </td>
                        <td class="recruitment-jo-content" colspan="3">
                            <div class="job-order-template-description" id="ynsir_jo_DESCRIPTION">
                                <?php
                                $APPLICATION->IncludeComponent(
                                    "bitrix:main.post.form",
                                    "",
                                    ($formParams = Array(
                                        "FORM_ID" => "DESCRIPTION",
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
                                            "NAME" => "DESCRIPTION",
                                            "VALUE" => $arResult['DATA']['DESCRIPTION'],
                                            "HEIGHT" => "500px"),
                                        "PROPERTIES" => array(),
                                    )),
                                    false,
                                    Array("HIDE_ICONS" => "Y")
                                );
                                ?>
                            </div>
                        </td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                    <tr class="recruitment-jo-row" data-dragdrop-context="field">
                        <!-- New or Replace -->
                        <td class="recruitment-jo-info-left">
                            <div class="crm-offer-info-label-wrap">
                                <span class="crm-offer-info-label-alignment"></span>
                                <span class="crm-offer-info-label"></span>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-right">
                            <div class="job-order-template-save" onclick="saveTemplate()">
                                <a><?=GetMessage("YNSIR_CJOE_SAVE_DESCRIPTION_TEMPLATE")?></a>
                            </div>
                        </td>
                        <td class="recruitment-jo-info-left"></td>
                        <td class="recruitment-jo-info-right"></td>
                        <td class="recruitment-jo-last-td"></td>
                    </tr>
                </table>
            <?endif;?>
        </div>
        <div class="webform-buttons">
            <input type="text" id="io_action" name="JO_ACTION" value="SAVE" hidden="">
            <span class="webform-button webform-button-create">
                <span class="webform-button-left"></span>
                <input class="webform-button-text" type="submit" id="submit-save" name="SAVE" value="<?=GetMessage("YNSIR_CJOE_SAVE_BUTTON")?>" onclick="setJOAction(1)">
                <span class="webform-button-right"></span>
            </span>
            <span class="webform-button">
                <span class="webform-button-left"></span>
                <input class="webform-button-text" type="button" name="CANCEL"
                       onclick="window.location='/recruitment/job-order/list/'" value="<?=GetMessage("YNSIR_CJOE_CANCEL_BUTTON")?>">
                <span class="webform-button-right"></span>
            </span>
        </div>
    </form>
</div>
<!--User select -->
<div id="job-order-select-user" class="job-order-select-user popup-window" hidden>
    <div style="float: left; width: 95%; margin: 0px 0px;">
        <?php
        $APPLICATION->IncludeComponent(
            "bitrix:intranet.user.selector.new",
            ".default",
            array(
                "MULTIPLE" => "N",
                "NAME" => "USER",
                "VALUE" => false,
                "SHOW_BUTTON" => "N",
                "GET_FULL_INFO" => "Y",
                "PATH_TO_USER_PROFILE" => "/company/personal/user/#user_id#/",
                "GROUP_ID_FOR_SITE" => false,
                "SHOW_EXTRANET_USERS" => "FROM_MY_GROUPS",
                "DISPLAY_TAB_GROUP" => "Y",
                "NAME_TEMPLATE" => "#NAME# #LAST_NAME#",
                "SHOW_LOGIN" => "Y",
                "ON_SELECT" => "onSelectedUser",
            ),
            null,
            array("HIDE_ICONS" => "Y")
        );
        ?>
    </div>
</div>

<div id="popup_template" class="ajax-popup" style="min-width: 360px; min-height:60px" hidden>
    <div class="name-template">
        <?=GetMessage("YNSIR_CJOE_NAME_LABEL")?>:
        <input type="text" id="name_template" />
    </div>
    <div class="name-template note-save-template">
        <?=GetMessage("YNSIR_CJOE_TEMPLATE_NEW_NOTE")?>
    </div>
    <div class="error error-template-save" id="error-template-save"></div>
</div>

<script>
    var JSJOEData = {
        'PREPARE': <? echo json_encode($arFieldsPrepare);?>,
        'URL': {
            EDIT: '<?=$arResult["URL_JO_EDIT"]?>',
            DETAIL: '<?=$arResult["URL_JO_DETAIL"]?>',
        }
    };
    var JSJOTemplate = <? echo json_encode($arResult['TEMPLATE'])?>;
    var JSJOEMess = {
        YNSIR_CJOE_SAVE_BUTTON: '<?=GetMessage("YNSIR_CJOE_SAVE_BUTTON")?>',
        YNSIR_CJOE_CANCEL_BUTTON: '<?=GetMessage("YNSIR_CJOE_CANCEL_BUTTON")?>',
        YNSIR_CJOE_TEMPLATE_SAVE_TITLE: '<?=GetMessage("YNSIR_CJOE_TEMPLATE_SAVE_TITLE")?>',
        YNSIR_CJOE_T_NAME_VALIDATE: '<?=GetMessage("YNSIR_CJOE_T_NAME_VALIDATE")?>',
        YNSIR_CJOE_T_CONTENT_VALIDATE: '<?=GetMessage("YNSIR_CJOE_T_CONTENT_VALIDATE")?>',
        YNSIR_CJOE_T_INTERVIEW_ROUND_TITLE: '<?=GetMessage("YNSIR_CJOE_T_INTERVIEW_ROUND_TITLE")?>',
        YNSIR_CJOE_NOTE: '<?=GetMessage("YNSIR_CJOE_NOTE")?>',
        YNSIR_CJOE_PARTICIPANTS: '<?=GetMessage("YNSIR_CJOE_PARTICIPANTS")?>',
        YNSIR_CJOE_BTN_ADD: '<?=GetMessage("YNSIR_CJOE_BTN_ADD")?>',
        YNSIR_CJOE_ROUND: '<?=GetMessage("YNSIR_CJOE_ROUND")?>',
    }
</script>