<?php
use Bitrix\Disk\File;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$APPLICATION->SetTitle($arResult["TITLE"] . ": " . $arResult['CANDIDATE']['FULL_NAME']);

//$config = $arResult['CONFIG'];
//

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/autorun_proc.js');
//
$guid = $arResult['GUID'];
$config = $arResult['CONFIG'];

$isExpanded = $config['expanded'] === 'Y';
$isFixed = $config['fixed'] === 'Y';
?>

<?php
if(!$arResult['PERM']){
    ShowError(GetMessage('CRM_PERMISSION_DENIED'));
    die;
}
if($arResult['CAN_EDIT'] ):
    $this->SetViewTarget("pagetitle", 100);
    ?>
    <div onclick="window.top.location='/recruitment/candidate/edit/<?= $arResult['ID'] ?>/';"
         class="pagetitle-container pagetitle-align-right-container-edit">
        <a>
            <?= GetMessage('YNSIC_T_PROFILE_BTN_EDIT') ?>
        </a>
    </div>
    <?
    $this->EndViewTarget();
endif;
?>
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
                                        <span class="crm-lead-header-title-text"><?= $arResult['CANDIDATE']['NAME'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="crm-lead-header-header-right">
                                <div class="crm-lead-header-right-inner">
                                    <div class="crm-lead-header-status">
                                        <span id="crm_company_show_v12_qpv_menu_btn"
                                              class="crm-lead-header-right"><?= GetMessage("YNSIR_DETAIL_CANDIDATE_STATUS") ?></span>
                                        <span id="crm_company_show_v12_qpv_menu_btn"
                                              class="crm-lead-header-right crm-lead-header-right-value"><?= $arResult['CANDIDATE']['CANDIDATE_STATUS'] ?></span>
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
                            <table id="<?= $guid ?>_center_container" class="crm-lead-header-inner-table">
                                <tbody>
                                </tbody>
                                <colgroup>
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small">
                                    <col class="crm-lead-header-inner-cell">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del">
                                </colgroup>
                                <tbody>
                                <tr id="<?= $guid ?>_center_phone">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">

                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small"><?= GetMessage("YNSIR_IG_TITLE_CMOBILE") ?></td>
                                    <td id="content_phone" class="crm-lead-header-inner-cell"
                                        style="vertical-align: top;overflow: hidden;">
                                        <span class="crm-client-contacts-block-text ynsi-show-phone"
                                              style="max-width: 300px;"><?= $arResult['CANDIDATE']['CMOBILE'] ?></span>
                                        <br></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <tr id="<?= $guid ?>_center_email">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">

                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small"><?= GetMessage("YNSIR_DETAIL_EMAIL") ?></td>
                                    <td class="crm-lead-header-inner-cell">
                                        <?= $arResult['CANDIDATE']['EMAIL'] ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <tr id="<?= $guid ?>_center_im">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">

                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title ynsir-candidate-header-small"><?= GetMessage("YNSIR_SOCIAL") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <? if (strlen($arResult['CANDIDATE']['FACEBOOK']) > 0):
                                            if (!strpos($arResult['CANDIDATE']['FACEBOOK'], '//')) {
                                                $arResult['CANDIDATE']['FACEBOOK'] = 'https://' . $arResult['CANDIDATE']['FACEBOOK'];
                                            }
                                            ?>
                                            <span class="crm-client-contacts-block-text ynsir-candidate-social-block-text">
                                    <a target="_blank" href="<?= $arResult['CANDIDATE']['FACEBOOK'] ?>">
                                        <img src="/bitrix/js/ynsirecruitment/images/facebook-logo.png"
                                             alt="YouNet SI Facebook">
                                    </a>
                                </span>
                                        <?endif;
                                        if (strlen($arResult['CANDIDATE']['LINKEDIN']) > 0):
                                            if (!strpos($arResult['CANDIDATE']['LINKEDIN'], '//')) {
                                                $arResult['CANDIDATE']['LINKEDIN'] = 'https://' . $arResult['CANDIDATE']['LINKEDIN'];
                                            }
                                            ?>
                                            <span class="crm-client-contacts-block-text ynsir-candidate-social-block-text">
                                    <a target="_blank" href="<?= $arResult['CANDIDATE']['LINKEDIN'] ?>">
                                        <img src="/bitrix/js/ynsirecruitment/images/linkedin-logo.png" alt="">
                                    </a>
                                </span>
                                        <?endif;
                                        if (strlen($arResult['CANDIDATE']['TWITTER']) > 0):
                                            if (!strpos($arResult['CANDIDATE']['TWITTER'], '//')) {
                                                $arResult['CANDIDATE']['TWITTER'] = 'https://' . $arResult['CANDIDATE']['TWITTER'];
                                            }
                                            ?>
                                            <span class="crm-client-contacts-block-text ynsir-candidate-social-block-text">
                                    <a target="_blank" href="<?= $arResult['CANDIDATE']['TWITTER'] ?>">
                                        <img src="/bitrix/js/ynsirecruitment/images/twitter-logo.png" alt="">
                                    </a>
                                </span>
                                        <?endif;
                                        if (strlen($arResult['CANDIDATE']['SKYPE_ID']) > 0):
                                            if (!strpos($arResult['CANDIDATE']['SKYPE_ID'], '//')) {
                                                $arResult['CANDIDATE']['SKYPE_ID'] = 'https://' . $arResult['CANDIDATE']['SKYPE_ID'];
                                            }
                                            ?>
                                            <span class="crm-client-contacts-block-text ynsir-candidate-social-block-text">
                                    <a target="_blank" href="<?= $arResult['CANDIDATE']['SKYPE_ID'] ?>">
                                        <img src="/bitrix/js/ynsirecruitment/images/skype-logo.png" alt="">
                                    </a>
                                </span>
                                        <? endif; ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="crm-lead-header-cell">
                            <table id="<?= $guid ?>_right_container" class="crm-lead-header-inner-table">
                                <tbody>
                                </tbody>
                                <colgroup>
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title">
                                    <col class="crm-lead-header-inner-cell">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del">
                                </colgroup>
                                <tbody>
                                <tr id="<?= $guid ?>_center_phone">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title"><?= GetMessage("YNSIR_DETAIL_HIRGHEST_QUALIFICATION_HELD") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                    <span class="crm-client-contacts-block-text" style="max-width: 300px;">
                                        <?= $arResult['CANDIDATE']['HIGHEST_OBTAINED_DEGREE'] ?>
                                    </span>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <tr id="<?= $guid ?>_center_phone">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title"><?= GetMessage("YNSIR_DETAIL_CURRENT_JOB_TITLE") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                    <span class="crm-client-contacts-block-text" style="max-width: 300px;">
                                        <?= $arResult['CANDIDATE']['CURRENT_JOB_TITLE'] ?>
                                    </span>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <tr id="<?= $guid ?>_center_email">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">

                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title"><?= GetMessage("YNSIR_DETAIL_CURRENT_ENPLOYER") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                    <span class="crm-client-contacts-block-text" style="max-width: 300px;">
                                        <?= $arResult['CANDIDATE']['CURRENT_EMPLOYER'] ?>
                                    </span>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="crm-lead-header-cell">
                            <table id="<?= $guid ?>_right_container" class="crm-lead-header-inner-table">
                                <tbody>
                                </tbody>
                                <colgroup>
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title">
                                    <col class="crm-lead-header-inner-cell">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del">
                                </colgroup>
                                <tbody>
                                <tr id="<?= $guid ?>_center_phone">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title">
                                        <?= GetMessage("YNSIR_DETAIL_CANDIDATE_OWNER") ?>
                                    </td>
                                    <td class="crm-lead-header-inner-cell">
                                        <div class="crm-client-photo-wrapper">
                                            <div class="crm-client-user-def-pic">
                                                <img alt="Author Photo"
                                                     src="<?= $arResult['CANDIDATE']['CANDIDATE_OWNER_PHOTO_URL'] ?>"/>
                                            </div>
                                        </div>
                                        <span class="crm-client-contacts-block-text recruitment-candidate-info-label-user"
                                              style="max-width: 300px;">
                                        <?= $arResult['CANDIDATE']['CANDIDATE_OWNER'] ?>
                                    </span>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del"></td>
                                </tr>
                                <tr id="<?= $guid ?>_left_source_description">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">

                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title"><?= GetMessage("YNSIR_DETAIL_LAST_UPDATE") ?></td>
                                    <td class="crm-lead-header-inner-cell">
                                        <div class="crm-lead-header-text-wrapper">
                                            <div class="crm-lead-header-text-view-wrapper"><?= $arResult['CANDIDATE']['MODIFIED_DATE_SHORT'] ?></div>
                                        </div>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del">
                                        <div class=""></div>
                                    </td>
                                </tr>
                                <tr id="<?= $guid ?>_left_source_description">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">

                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title"><?= GetMessage("YNSIR_DETAIL_NEAREST_ACTIVITY") ?></td>
                                    <td class="crm-lead-header-inner-cell">
                                        <div class="crm-lead-header-text-wrapper">
                                            <div class="crm-lead-header-text-view-wrapper"><?= $arResult['CANDIDATE']['ACTIVITY']['columns']['ACTIVITY_ID']; ?></div>
                                        </div>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del">
                                        <div class=""></div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="crm-lead-header-cell crm-lead-header-comments" colspan="3">
                            <table id="<?= $guid ?>_bottom_container" class="crm-lead-header-inner-table">
                                <tbody>
                                </tbody>
                                <colgroup>
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-com-cell">
                                    <col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del">
                                </colgroup>
                                <tbody>
                                <tr id="<?= $guid ?>_bottom_comments">
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move">
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title"><?= GetMessage("YNSIR_DETAIL_SKILL_SET") ?></td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-com-cell">
                                        <div class="crm-lead-header-lhe-wrapper">
                                            <div class="crm-lead-header-lhe-view-wrapper"><?= $arResult['CANDIDATE']['SKILL_SET'] ?></div>
                                        </div>
                                    </td>
                                    <td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del">
                                    </td>
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
<?
/*
 * NEW TEMPLATE GENDER
 * update by nhatth2
 */
?>
    <div id="<?= $guid ?>_section_wrapper" style="display:<?= $isExpanded ? 'block' : 'none' ?>"
         class="recruitment-candidate-main-wrap">
        <?
        foreach ($arResult['FIELD_CANDIDATE_VIEW'] as $SECTION_KEY => $EACH_CANDIDATE_SECTION):
            $SECTION_NAME = $EACH_CANDIDATE_SECTION['NAME'];
            $arField = $EACH_CANDIDATE_SECTION['FIELDS'];
            ?>
            <table id="section_deal_info_contents" class="recruitment-candidate-info-table">
                <tbody>
                <tr id="section_deal_info">
                    <td colspan="7">
                        <div class="recruitment-candidate-title">
                            <span class="recruitment-candidate-title-text"><?= $SECTION_NAME ?></span>
                        </div>
                    </td>
                </tr>
                <?
                switch ($SECTION_KEY):
                    case YNSIRConfig::CS_ATTACHMENT_INFORMATION:
                        ?>
                        <tr data-dragdrop-context="field" class="recruitment-candidate-row" id="title_wrap">
                            <td class="recruitment-candidate-info-left" colspan="7" style="padding-left: 42px;">
                                <table width="100%"
                                       style="padding-left: 42px;border-collapse: collapse;font-size: 13px;color: #565e6a;">
                                    <tbody style="padding-left: 42px;border-collapse: collapse;font-weight: bold;">
                                    <tr>
                                        <td width="30%"
                                            style="text-align: left;height: 30px;padding-left: 10px;"><?= GetMessage("YNSIR_DETAIL_FILE_NAME") ?></td>
                                        <td width="15%"
                                            style="text-align: left;height: 30px;"><?= GetMessage("YNSIR_DETAIL_ATTACHED_BY") ?></td>
                                        <td width="20%"
                                            style="text-align: left;height: 30px;"><?= GetMessage("YNSIR_DETAIL_MODIFIED_TIME") ?></td>
                                        <td width="10%"
                                            style="text-align: left;height: 30px;"><?= GetMessage("YNSIR_DETAIL_FILE_SIZE") ?></td>
                                        <td width="20%"
                                            style="text-align: left;height: 30px;"><?= GetMessage("YNSIR_DETAIL_CATEGORY") ?></td>
                                        <!--                                    <td width="10%" style="text-align: left;height: 30px;"></td>-->
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    <?php
                                    foreach ($arResult['CANDIDATE']['FILE'] as $arFile) {
                                        $k = $arFile['id'];
                                        $file = File::loadById($arFile['id'], array('STORAGE'));

                                        if (!$file) continue;
                                        ?>
                                        <tr>
                                            <td style="height: 30px;text-align: left;width:40%">
                                                <?
                                                $arFileInfo = CFile::GetByID($file->getFileId())->Fetch();
                                                ?>
                                                <div id="bx-disk-filepage-<?= $k ?>" class="bx-disk-filepage-OTHERS">
                                                    <a href="#" data-bx-viewer="iframe"
                                                       data-bx-download="/disk/downloadFile/<?= $k ?>/?&amp;ncc=1&amp;filename=<?= $arFileInfo['ORIGINAL_NAME'] ?>"
                                                       data-bx-title="<?= $arFileInfo['ORIGINAL_NAME'] ?>"
                                                       data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=<?= $k ?>&amp;service=gvdrive&amp; bx-attach-file-id="<?= $k ?>
                                                    "
                                                    data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId=<?= $k ?>
                                                    &amp;service=gdrive&amp;action=<?= $k ?>">
                                                    <?= $arFileInfo['ORIGINAL_NAME'] ?>
                                                    </a>
                                                </div>

                                                <script type="text/javascript">
                                                    BX.viewElementBind(
                                                        'bx-disk-filepage-<?=$k?>',
                                                        {showTitle: true},
                                                        {attr: 'data-bx-viewer'}
                                                    );
                                                </script>
                                            </td>
                                            <td style="text-align: left;">
                                                <?= $arFile['acttact_by'] ?>
                                            </td>
                                            <td style="text-align: left;"><?= $arFile['modify_date'] ?></td>
                                            <td style="text-align: left;"><?= $arFile['file_size'] ?></td>
                                            <td style="text-align: left;width: 20%"><?= $arFile['category'] ?></td>
                                        </tr>
                                        <?
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <?
                        break;
                    default:
                        foreach ($arField as $KEY => $eachField):?>
                            <?
                            if ($KEY % 2 == 0):?>
                                <tr id="title_wrap" class="recruitment-candidate-row" data-dragdrop-context="field">
                            <? endif; ?>
                            <td class="recruitment-candidate-info-left">
                                <div class="recruitment-candidate-info-label-wrap">
                                    <span class="recruitment-candidate-info-label-alignment"></span>
                                    <span class="recruitment-candidate-info-label">
                                    <?= $eachField['NAME'] ?>
                                </span>
                                </div>
                            </td>
                            <td class="recruitment-candidate-info-right">
                                <div class="recruitment-candidate-info-data-wrap">
                                    <?
                                    switch ($eachField['KEY']):
                                        case 'CANDIDATE_OWNER':
                                        case 'MODIFIED_BY':
                                        case 'CREATED_BY':
                                            ?>
                                            <span class="recruitment-candidate-info-label-alignment"></span>
                                            <div class="crm-client-photo-wrapper">
                                                <div class="crm-client-user-def-pic">
                                                    <img alt="Author Photo"
                                                         src="<?= $arResult['CANDIDATE'][$eachField['KEY'] . '_PHOTO_URL'] ?>"/>
                                                </div>
                                            </div>
                                            <?
                                        case 'PHONE':
                                        case 'CMOBILE':
                                        case 'EMAIL':
                                            ?>
                                            <span class="recruitment-candidate-info-label"><?= $arResult['CANDIDATE'][$eachField['KEY']] ?></span>
                                            <?
                                            break;
                                        default:
                                            ?>
                                            <span class="recruitment-candidate-info-label-alignment"></span>
                                            <span class="recruitment-candidate-info-label"><?= $arResult['CANDIDATE'][$eachField['KEY']] ?></span>
                                            <?
                                            break;
                                    endswitch;
                                    ?>
                                </div>
                            </td>
                            <?
                            if ($KEY % 2 == 1):?>
                                <td class="recruitment-candidate-last-td"></td>
                                </tr>
                            <? endif; ?>
                        <?endforeach;
                        break;
                endswitch; ?>
                </tbody>
            </table>
        <?endforeach;
        unset($SECTION_NAME);
        unset($SECTION_KEY);
        unset($arField);
        ?>

    </div>

    <script>
        BX.ready(
            function () {
                BX.CandidateQuickPanelView.create(
                    "<?=CUtil::JSEscape($guid)?>",
                    {
                        prefix: "<?=CUtil::JSEscape($guid)?>",
                        config: <?=CUtil::PhpToJSObject($config)?>,
                        serviceUrl: "<?='/bitrix/components/ynsirecruitment/candidate.detail/settings.php?' . bitrix_sessid_get()?>"
                    }
                );
            }
        )
    </script>
<?
/*
 *
 * EVENT
 *
 */

$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
$APPLICATION->IncludeComponent(
    'ynsirecruitment:activity.editor',
    '',
    array(
        'EDITOR_ID' => $activityEditorID,
        'PREFIX' => $arResult['GRID_ID'],
        'OWNER_TYPE' => 'CANDIDATE',
        'OWNER_ID' => 0,
        'READ_ONLY' => false,
        'ENABLE_UI' => false,
        'ENABLE_TOOLBAR' => false
    ),
    null,
    array('HIDE_ICONS' => 'Y')
);

$extension = $arResult['EXTENSION'];
$extensionConfig = isset($extension['CONFIG']) ? $extension['CONFIG'] : null;
if (is_array($extensionConfig)) {
    $extensionID = isset($extension['ID']) ? $extension['ID'] : $gridID;
    $extensionMessages = isset($extension['MESSAGES']) && is_array($extension['MESSAGES']) ? $extension['MESSAGES'] : array();

    ?>

    <script type="text/javascript">
        BX.ready(
            function () {
                BX.YNSIRUIGridExtension.messages = <?=CUtil::PhpToJSObject($extensionMessages)?>;
                BX.YNSIRUIGridExtension.create(
                    "<?=CUtil::JSEscape($extensionID)?>",
                    <?=CUtil::PhpToJSObject($extensionConfig)?>
                );
            }
        );
    </script>
    <?
}
?>