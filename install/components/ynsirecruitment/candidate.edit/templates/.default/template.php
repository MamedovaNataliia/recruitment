<?php
use Bitrix\Disk\File;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
CJSCore::Init(array("jquery"));
$APPLICATION->AddHeadScript('/bitrix/components/ynsirecruitment/candidate.edit/templates/.default/jquery-ui.js');
$APPLICATION->SetAdditionalCSS('/bitrix/components/ynsirecruitment/candidate.edit/templates/.default/jquery-ui.css');
$this->SetViewTarget("pagetitle", 100);
?>
<div class="pagetitle-container pagetitle-align-right-container">
    <a href="/recruitment/candidate/list/" class="ynsir-candidate-detail-back">
        <?= GetMessage('YNSIR_CE_TITLE_BACK') ?>
    </a>
</div>
<?
$this->EndViewTarget();
$arResult['CANDIDATE_DATA']['EMAIL'] = isset($arResult['CANDIDATE_DATA']['EMAIL']) ? $arResult['CANDIDATE_DATA']['EMAIL'] : array('');
$arResult['CANDIDATE_DATA']['PHONE'] = isset($arResult['CANDIDATE_DATA']['PHONE']) ? $arResult['CANDIDATE_DATA']['PHONE'] : array('');
$arResult['CANDIDATE_DATA']['CMOBILE'] = isset($arResult['CANDIDATE_DATA']['CMOBILE']) ? $arResult['CANDIDATE_DATA']['CMOBILE'] : array('');
$arResult['CANDIDATE_DATA']['CURRENT_JOB_TITLE'] = isset($arResult['CANDIDATE_DATA']['CURRENT_JOB_TITLE']) ? $arResult['CANDIDATE_DATA']['CURRENT_JOB_TITLE'] : array('');
?>
<?
foreach($arResult['CANDIDATE_DATA']['FILE_UPLOAD'] as $k => $v){
    foreach($v as $id=>$file){
        $file = File::loadById($id, array('STORAGE'));
        if(!$file) continue;
        // id storage
        $iIdStorageTmp = $file->getParentId();
        if($iIdStorageTmp > 0){
            $object = \Bitrix\Disk\BaseObject::loadById((int)$iIdStorageTmp, array('STORAGE'));
            if (!$object) {
                $idStorage = 0;
                continue;
            }
        }
        // end
        $arDetailIFile = CFile::GetByID($file->getFileId())->Fetch();
        echo '<div id="file-id-'.$id.'" hidden class="file-uploaded-candidate" file-id='.$id.' file-name="'.$arDetailIFile['ORIGINAL_NAME'].'"></div>';
    }
}
?>
<div id="ynsir-candidate-edit">
    <form name="" action="<?= $arResult["URL_EDIT_CANDIDATE"] ?>" method="POST"
          onsubmit="return submitCandidateForm(this);" class="recruitment-candidate-form" enctype="multipart/form-data">
        <div class="recruitment-candidate-main-wrap">
            <table class="recruitment-candidate-info-table">
                <tbody>
                <?php
                foreach ($arResult['CANDIDATE']['EDIT_FIELD_SECTION'] as $key => $value) :
                    ?>
                    <tr>
                        <td colspan="7">
                            <div class="recruitment-candidate-title">
                                <span class="recruitment-candidate-title-text"><?= $value['NAME'] ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php
                    switch ($key) :
                        case YNSIRConfig::CS_ATTACHMENT_INFORMATION:
                            ?>
                            <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                                <td class="recruitment-candidate-info-left">
                                    <div class="recruitment-candidate-info-label-wrap">
                                        <span class="recruitment-candidate-info-label-alignment"></span>
                                        <span class="recruitment-candidate-info-label"><?=GetMessage('YNSIR_CE_TITLE_RESUME').':'?></span>
                                    </div>
                                </td>
                                <td colspan="5">
                                    <div class="recruitment-candidate-info-data-wrap">
                                        <div id="file-selectdialog-file_resume" class="file-selectdialog"
                                             style="display: block; opacity: 1;"
                                             dropzone="copy f:*/*">
                                            <div class="file-extended" <?=empty($arResult['CANDIDATE_DATA']['FILE_UPLOAD'][YNSIR_FT_RESUME])?'style="display: none;"':''?>>
                                                <div class="file-placeholder">
                                                    <table id="file_resume-list" class="files-list" cellspacing="0">
                                                        <tbody class="file-placeholder-tbody">
                                                        <?
                                                        foreach ($arResult['CANDIDATE_DATA']['FILE_UPLOAD'][YNSIR_FT_RESUME] as $k => $value){
                                                            $file = File::loadById($k, array('STORAGE'));
                                                            if(!$file) continue;
                                                            $arFileInfo = CFile::GetByID($file->getFileId())->Fetch();
                                                            // id storage
                                                            $iIdStorageTmp = $file->getParentId();
                                                            if($iIdStorageTmp > 0){
                                                                $object = \Bitrix\Disk\BaseObject::loadById((int)$iIdStorageTmp, array('STORAGE'));
                                                                if (!$object) {
                                                                    $idStorage = 0;
                                                                    continue;
                                                                }
                                                            }
                                                            // end
                                                            echo '<div class="file-area">';
                                                            echo '<input id="file-doc' .$k.'" type="hidden" name="FILE_RESUME[]" value="' .$k. '">';
                                                            ?>
                                                            <div id="bx-disk-filepage-file_resume<?=$k?>" class="bx-disk-filepage-file_resume">
                                                                <a href="#"
                                                                   data-bx-download="/disk/downloadFile/<?=$k?>/?&amp;ncc=1&amp;filename=<?=$arFileInfo['ORIGINAL_NAME']?>"
                                                                   data-bx-viewer="iframe" data-bx-title="<?=$arFileInfo['ORIGINAL_NAME']?>" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=<?=$k?>&amp;service=gvdrive&amp; bx-attach-file-id="<?=$k?>" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId=<?=$k?>&amp;service=gdrive&amp;action=<?=$k?>">
                                                                <?=$arFileInfo['ORIGINAL_NAME']?>
                                                                </a>
                                                            </div>
                                                            <span class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"
                                                                  onclick="deleteFile(this,<?=$k?>)"
                                                            ></span>
                                                            <script type="text/javascript">
                                                                BX.viewElementBind(
                                                                    'bx-disk-filepage-file_resume<?=$k?>',
                                                                    {showTitle: true},
                                                                    {attr: 'data-bx-viewer'}
                                                                );
                                                            </script>

                                                            <?php
                                                            echo '</div>';
                                                        }
                                                        ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <?
                                        $APPLICATION->IncludeComponent(
                                            'ynsirecruitment:disk.file.upload',
                                            '.default',
                                            array(
                                                'BUTTON_MESSAGE' => 'Upload',
                                                'ONUPLOADDONE' => 'setFileResume',
                                                'ALLOW_UPLOAD_EXT' => array('docx','pdf'),
                                                'HTML_ELEMENT_ID' => 'file_resume',
                                            )
                                        );
                                        ?>
                                        <p style="color: red" id="upload_empty"></p>


                                    </div>
                                </td>

                            </tr>
                            <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                                <td class="recruitment-candidate-info-left">
                                    <div class="recruitment-candidate-info-label-wrap">
                                        <span class="recruitment-candidate-info-label-alignment"></span>
                                        <span class="recruitment-candidate-info-label"><?=GetMessage('YNSIR_CE_TITLE_FORMATTED_RESUME').':'?></span>
                                    </div>
                                </td>
                                <td colspan="5">
                                    <div class="recruitment-candidate-info-data-wrap">
                                        <div id="file-selectdialog-FORMATTED_RESUME" class="file-selectdialog"
                                             style="display: block; opacity: 1;"
                                             dropzone="copy f:*/*">
                                            <div class="file-extended" <?=empty($arResult['CANDIDATE_DATA']['FILE_UPLOAD'][YNSIR_FT_FORMATTED_RESUME])?'style="display: none;"':''?>>
                                                <div class="file-placeholder">
                                                    <table id="FORMATTED_RESUME-list" class="files-list" cellspacing="0">
                                                        <tbody class="file-placeholder-tbody">

                                                        <?
                                                        foreach ($arResult['CANDIDATE_DATA']['FILE_UPLOAD'][YNSIR_FT_FORMATTED_RESUME] as $k => $value){
                                                            $file = File::loadById($k, array('STORAGE'));
                                                            if(!$file) continue;
                                                            $arFileInfo = CFile::GetByID($file->getFileId())->Fetch();
                                                            // id storage
                                                            $iIdStorageTmp = $file->getParentId();
                                                            if($iIdStorageTmp > 0){
                                                                $object = \Bitrix\Disk\BaseObject::loadById((int)$iIdStorageTmp, array('STORAGE'));
                                                                if (!$object) {
                                                                    $idStorage = 0;
                                                                    continue;
                                                                }
                                                            }
                                                            // end
                                                            echo '<div class="file-area">';
                                                            echo '<input id="file-doc' .$k.'" type="hidden" name="FILE_FORMATTED_RESUME[]" value="' .$k. '">';
                                                            ?>
                                                            <div id="bx-disk-filepage-FORMATTED_RESUME<?=$k?>" class="bx-disk-filepage-FORMATTED_RESUME">
                                                                <a href="#"
                                                                   data-bx-download="/disk/downloadFile/<?=$k?>/?&amp;ncc=1&amp;filename=<?=$arFileInfo['ORIGINAL_NAME']?>"
                                                                   data-bx-viewer="iframe" data-bx-title="<?=$arFileInfo['ORIGINAL_NAME']?>" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=<?=$k?>&amp;service=gvdrive&amp; bx-attach-file-id="<?=$k?>" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId=<?=$k?>&amp;service=gdrive&amp;action=<?=$k?>">
                                                                <?=$arFileInfo['ORIGINAL_NAME']?>
                                                                </a>
                                                            </div>
                                                            <span class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"
                                                                  onclick="deleteFile(this,<?=$k?>)"
                                                            ></span>
                                                            <script type="text/javascript">
                                                                BX.viewElementBind(
                                                                    'bx-disk-filepage-FORMATTED_RESUME<?=$k?>',
                                                                    {showTitle: true},
                                                                    {attr: 'data-bx-viewer'}
                                                                );
                                                            </script>
                                                            <?php
                                                            echo '</div>';
                                                        }
                                                        ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <?
                                        $APPLICATION->IncludeComponent(
                                            'ynsirecruitment:disk.file.upload',
                                            '.default',
                                            array(
                                                'BUTTON_MESSAGE' => 'Upload',
                                                'ONUPLOADDONE' => 'setFileFormmetedResume',
                                                'ALLOW_UPLOAD_EXT' => array('docx','pdf'),
                                                'HTML_ELEMENT_ID' => 'formatted_resume',
                                            )
                                        );
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                                <td class="recruitment-candidate-info-left">
                                    <div class="recruitment-candidate-info-label-wrap">
                                        <span class="recruitment-candidate-info-label-alignment"></span>
                                        <span class="recruitment-candidate-info-label"><?= GetMessage('YNSIR_CE_TITLE_COVER_LETTER').':' ?></span>
                                    </div>
                                </td>
                                <td colspan="5">
                                    <div class="recruitment-candidate-info-data-wrap">
                                        <div id="file-selectdialog-COVER_LETTER" class="file-selectdialog"
                                             style="display: block; opacity: 1;"
                                             dropzone="copy f:*/*">
                                            <div class="file-extended" <?=empty($arResult['CANDIDATE_DATA']['FILE_UPLOAD'][YNSIR_FT_COVER_LETTER])?'style="display: none;"':''?>>
                                                <div class="file-placeholder">
                                                    <table id="COVER_LETTER-list" class="files-list" cellspacing="0">
                                                        <tbody class="file-placeholder-tbody">
                                                        <?
                                                        foreach ($arResult['CANDIDATE_DATA']['FILE_UPLOAD'][YNSIR_FT_COVER_LETTER] as $k => $value){
                                                            $file = File::loadById($k, array('STORAGE'));
                                                            if(!$file) continue;
                                                            $arFileInfo = CFile::GetByID($file->getFileId())->Fetch();
                                                            // id storage
                                                            $iIdStorageTmp = $file->getParentId();
                                                            if($iIdStorageTmp > 0){
                                                                $object = \Bitrix\Disk\BaseObject::loadById((int)$iIdStorageTmp, array('STORAGE'));
                                                                if (!$object) {
                                                                    $idStorage = 0;
                                                                    continue;
                                                                }
                                                            }
                                                            // end
                                                            ?>
                                                            <div class="file-area">
                                                                <input id="file-doc <?=$k?>" type="hidden" name="FILE_COVER_LETTER[]" value="<?=$k?>">
                                                                <div id="bx-disk-filepage-COVER_LETTER<?=$k?>" class="bx-disk-filepage-COVER_LETTER">
                                                                    <a href="#"
                                                                       data-bx-download="/disk/downloadFile/<?=$k?>/?&amp;ncc=1&amp;filename=<?=$arFileInfo['ORIGINAL_NAME']?>"
                                                                       data-bx-viewer="iframe"
                                                                       data-bx-title="<?=$arFileInfo['ORIGINAL_NAME']?>"
                                                                       data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=<?=$k?>&amp;service=gvdrive&amp; bx-attach-file-id="<?=$k?>" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId=<?=$k?>&amp;service=gdrive&amp;action=<?=$k?>">
                                                                    <?=$arFileInfo['ORIGINAL_NAME']?>
                                                                    </a>
                                                                </div>
                                                                <span class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"
                                                                      onclick="deleteFile(this,<?=$k?>)"
                                                                ></span>
                                                                <script type="text/javascript">
                                                                    BX.viewElementBind(
                                                                        'bx-disk-filepage-COVER_LETTER<?=$k?>',
                                                                        {showTitle: true},
                                                                        {attr: 'data-bx-viewer'}
                                                                    );
                                                                </script>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <?
                                        $APPLICATION->IncludeComponent(
                                            'ynsirecruitment:disk.file.upload',
                                            '.default',
                                            array(
                                                'BUTTON_MESSAGE' => 'Upload',
                                                'ONUPLOADDONE' => 'setFileCoverLetter',
                                                'ALLOW_UPLOAD_EXT' => array('docx','pdf'),
                                                'HTML_ELEMENT_ID' => 'cover_letter',
                                            )
                                        );
                                        ?>
                                    </div>
                                </td>

                            </tr>
                            <tr class="recruitment-candidate-row" data-dragdrop-context="field">
                                <td class="recruitment-candidate-info-left">
                                    <div class="recruitment-candidate-info-label-wrap">
                                        <span class="recruitment-candidate-info-label-alignment"></span>
                                        <span class="recruitment-candidate-info-label"><?=GetMessage('YNSIR_CE_TITLE_OTHERS').':'?></span>
                                    </div>
                                </td>
                                <td colspan="5">
                                    <div class="recruitment-candidate-info-data-wrap">
                                        <div id="file-selectdialog-OTHERS" class="file-selectdialog"
                                             style="display: block; opacity: 1;"
                                             dropzone="copy f:*/*">
                                            <div class="file-extended" <?=empty($arResult['CANDIDATE_DATA']['FILE_UPLOAD'][YNSIR_FT_OTHERS])?'style="display: none;"':''?>>
                                                <div class="file-placeholder">
                                                    <table id="OTHERS-list" class="files-list" cellspacing="0">
                                                        <tbody class="file-placeholder-tbody">
                                                        <?
                                                        foreach ($arResult['CANDIDATE_DATA']['FILE_UPLOAD'][YNSIR_FT_OTHERS] as $k => $value){
                                                            $file = File::loadById($k, array('STORAGE'));
                                                            if(!$file) continue;
                                                            $arFileInfo = CFile::GetByID($file->getFileId())->Fetch();
                                                            // id storage
                                                            $iIdStorageTmp = $file->getParentId();
                                                            if($iIdStorageTmp > 0){
                                                                $object = \Bitrix\Disk\BaseObject::loadById((int)$iIdStorageTmp, array('STORAGE'));
                                                                if (!$object) {
                                                                    $idStorage = 0;
                                                                    continue;
                                                                }
                                                            }
                                                            // end
                                                            echo '<div class="file-area">';
                                                            echo '<input id="file-doc' .$k.'" type="hidden" name="FILE_OTHERS[]" value="' .$k. '">';
                                                            ?>
                                                            <div id="bx-disk-filepage-OTHERS<?=$k?>" class="bx-disk-filepage-OTHERS">
                                                                <a href="#"
                                                                   data-bx-download="/disk/downloadFile/<?=$k?>/?&amp;ncc=1&amp;filename=<?=$arFileInfo['ORIGINAL_NAME']?>"
                                                                   data-bx-viewer="iframe" data-bx-title="<?=$arFileInfo['ORIGINAL_NAME']?>" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=<?=$k?>&amp;service=gvdrive&amp; bx-attach-file-id="<?=$k?>" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId=<?=$k?>&amp;service=gdrive&amp;action=<?=$k?>">
                                                                <?=$arFileInfo['ORIGINAL_NAME']?>
                                                                </a>
                                                            </div>
                                                            <span class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"
                                                                  onclick="deleteFile(this,<?=$k?>)"
                                                            ></span>
                                                            <script type="text/javascript">
                                                                BX.viewElementBind(
                                                                    'bx-disk-filepage-OTHERS<?=$k?>',
                                                                    {showTitle: true},
                                                                    {attr: 'data-bx-viewer'}
                                                                );
                                                            </script>
                                                            <?php
                                                            echo '</div>';
                                                        }
                                                        ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <?
                                        $APPLICATION->IncludeComponent(
                                            'ynsirecruitment:disk.file.upload',
                                            '.default',
                                            array(
                                                'BUTTON_MESSAGE' => 'Upload',
                                                'ONUPLOADDONE' => 'setFileOthers',
                                                'ALLOW_UPLOAD_EXT' => array('docx','pdf'),
                                                'HTML_ELEMENT_ID' => 'others',
                                            )
                                        );
                                        ?>
                                    </div>
                                </td>

                            </tr>
                            <?
                            break;
                        default:
                            foreach ($value['FIELDS'] as $k => $v) {
                                ?>
                                <tr class="recruitment-candidate-row" data-dragdrop-context="field">

                                    <?php
                                    $arRequireField = array(
                                        'CURRENT_JOB_TITLE',
                                        'CURRENT_EMPLOYER',
                                        'HIGHEST_OBTAINED_DEGREE',
                                        'EMAIL',
                                        'PHONE',
                                        'CMOBILE',
                                        'FIRST_NAME',
                                        'LAST_NAME',
                                        'SOURCE'
                                    );
                                    foreach ($v as $field) {
                                        $s = in_array($field['KEY'],$arRequireField)? 'ynsir-bold':'';
                                        ?>
                                        <td class="recruitment-candidate-info-left">
                                            <div class="recruitment-candidate-info-label-wrap">
                                                <span class="recruitment-candidate-info-label-alignment"></span>
                                                <span class="recruitment-candidate-info-label <?=$s?> "><?= $field['NAME'] . ':' ?></span>
                                            </div>
                                        </td>
                                        <?php
                                        switch ($field['KEY']) {
                                            //list type
                                            case YNSIRConfig::TL_MARITAL_STATUS:
                                            case YNSIRConfig::TL_CITY:
                                            case YNSIRConfig::TL_HIGHEST_OBTAINED_DEGREE:
                                            case YNSIRConfig::TL_SOURCES:
                                            case YNSIRConfig::TL_HIGHEST_OBTAINED_DEGREE:
                                            case YNSIRConfig::TL_UNIVERSITY:
                                            case YNSIRConfig::TL_EDUCATION:
                                            case YNSIRConfig::TL_MAJOR:
                                            case YNSIRConfig::TL_ENGLISH_PROFICIENCY:
                                            case YNSIRConfig::TL_APPLY_POSITION:
                                                $arTypeList = $arResult['CONFIG'][$field['KEY']];
                                                $listContentType = YNSIRConfig::getListContentType();
                                                $additional_type = $arResult['CANDIDATE_DATA'][$field['KEY']][0]['ADDITIONAL_TYPE'];
                                                $additional_value = $arResult['CANDIDATE_DATA'][$field['KEY']][0]['ADDITIONAL_VALUE'];
                                                $content = intval($arResult['CANDIDATE_DATA'][$field['KEY']][0]['CONTENT']);
                                                if ($additional_type == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                                                    $additional_value = ($additional_value != '0000-00-00') ? $DB->FormatDate($additional_value, $arResult['FORMAT_DB_TIME'], $arResult['FORMAT_DB_BX_SHORT']) : "";
                                                }
                                                $additional_lable = $arTypeList[$content]['ADDITIONAL_INFO_LABEL_' . strtoupper(LANGUAGE_ID)];
                                                $CURRENT_TYPE_SHOW = intval($additional_type) > 0 ? intval($additional_type) : 0;
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                                                        <select class="recruitment-item-table-select"
                                                                id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                                                onchange='changeSelectList(this,"<?= $field['KEY'] ?>","<?= $arTypeList['KEY'] ?>")'
                                                                name="<?= $field['KEY'] ?><?= (YNSIRConfig::TL_CANDIDATE_STATUS != $field['KEY']) ? '[]' : ''; ?>">

                                                            <?= (YNSIRConfig::TL_CANDIDATE_STATUS != $field['KEY']) ? '<option value="-1">-None-</option>' : ''; ?>
                                                            <?php

                                                            foreach ($arResult['CONFIG'][$field['KEY']] as $iIdQ => $sNameQ) {
                                                                $sSelected = $content == $iIdQ ? 'selected' : '';
                                                                ?>
                                                                <option value="<?= $iIdQ ?>"
                                                                        item_type="<?= $sNameQ['ADDITIONAL_INFO'] ?>"
                                                                        lable_type="<?= $sNameQ['ADDITIONAL_INFO_LABEL_' . strtoupper(LANGUAGE_ID)] ?>" <?= $sSelected ?>><?= $sNameQ['NAME_' . strtoupper(LANGUAGE_ID)] ?></option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                        <div class="recruitment-candidate-item-error-label error" hidden=""
                                                             style="display: none;">
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                    <div  class="content-additional-list" id="content_<?= $field['KEY'] ?>">
                                                        <div class="content_<?= $field['KEY'] ?>"
                                                             id="content_<?= $field['KEY'] ?>_DATE"
                                                            <?= (intval($CURRENT_TYPE_SHOW) != YNSIRConfig::YNSIR_TYPE_LIST_DATE) ? 'hidden' : '' ?>>
                                                            <label id="label_<?= $field['KEY'] ?>_DATE"
                                                                   class="recruitment-candidate-info-label"><?= (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_DATE && strlen($additional_lable) > 0) ? $additional_lable.':' : GetMessage('YNSIR_TYPE_CONTENT_DATE') ?></label>
                                                            <br>
                                                            <input class="recruitment-candidate-item-inp crm-item-table-date"
                                                                   type="text" id="<?= $field['KEY'] ?>_CONTENT_DATE"
                                                                   name="<?= $field['KEY'] ?>_CONTENT_DATE"
                                                                   value="<?= (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_DATE) ? $additional_value : '' ?>"/>
                                                            <span class="profile-date-button" id="<?= $field['KEY'] ?>_date_btn"></span>
                                                        </span>
                                                            <script>
                                                                BX.ready(function () {
                                                                    BX.YSIRDateLinkField.create(BX('<?=$field['KEY']?>_CONTENT_DATE'), BX('<?= $field['KEY'] ?>_date_btn'), {
                                                                        showTime: false,
                                                                        setFocusOnShow: false
                                                                    });
                                                                });
                                                            </script>
                                                        </div>
                                                        <div style="width: 78%" class="content_<?= $field['KEY'] ?>"
                                                             id="content_<?= $field['KEY'] ?>_STRING"
                                                            <?= (intval($CURRENT_TYPE_SHOW) != YNSIRConfig::YNSIR_TYPE_LIST_STRING) ? 'hidden' : '' ?>>
                                                            <label id="label_<?= $field['KEY'] ?>_STRING"
                                                                   class="recruitment-candidate-info-label"><?= (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_STRING && strlen($additional_lable) > 0) ? $additional_lable.':' : GetMessage('YNSIR_TYPE_CONTENT_STRING') ?></label>
                                                            <br>
                                                            <textarea
                                                                    name="<?= $field['KEY'] ?>_CONTENT_STRING"
                                                                    id="<?= $field['KEY'] ?>_CONTENT_STRING"
                                                                    class="content-edit-form-field-input-textarea"><?= (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_STRING) ? $additional_value : '' ?></textarea>
                                                        </div>
                                                        <div class="content_<?= $field['KEY'] ?>"
                                                             id="content_<?= $field['KEY'] ?>_NUMBER"
                                                            <?= (intval($CURRENT_TYPE_SHOW) != YNSIRConfig::YNSIR_TYPE_LIST_NUMBER) ? 'hidden' : '' ?>>
                                                            <label id="label_<?= $field['KEY'] ?>_NUMBER"
                                                                   class="recruitment-candidate-info-label"><?= (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_NUMBER && strlen($additional_lable) > 0) ? $additional_lable.':' : GetMessage('YNSIR_TYPE_CONTENT_NUMBER') ?></label>
                                                            <br>
                                                            <input class="recruitment-candidate-item-inp"
                                                                   type="text" id="<?= $field['KEY'] ?>_CONTENT_NUMBER"
                                                                   name="<?= $field['KEY'] ?>_CONTENT_NUMBER"
                                                                   value="<?= (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_NUMBER) ? $additional_value : '' ?>"/>
                                                        </div>
                                                        <div class="content_<?= $field['KEY'] ?> ynsir_input_user"
                                                             id="content_<?= $field['KEY'] ?>_USER"
                                                            <?= (intval($CURRENT_TYPE_SHOW) != YNSIRConfig::YNSIR_TYPE_LIST_USER) ? 'hidden' : '' ?>>
                                                            <label id="label_<?= $field['KEY'] ?>_USER"
                                                                   class="recruitment-candidate-info-label"><?= (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_USER && strlen($additional_lable) > 0) ? $additional_lable.':' : GetMessage('YNSIR_TYPE_CONTENT_USER') ?></label>
                                                            <br>
<!--                                                            <input class="recruitment-candidate-item-inp"-->
<!--                                                                   type="text" id="--><?//= $field['KEY'] ?><!--_CONTENT_USER"-->
<!--                                                                   name="--><?//= $field['KEY'] ?><!--_CONTENT_USER"-->
<!--                                                                   value="--><?//= (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_USER) ? $additional_value : '' ?><!--"/>-->

                                                            <?php
                                                            $idUser = (intval($CURRENT_TYPE_SHOW) == YNSIRConfig::YNSIR_TYPE_LIST_USER) ? $additional_value : '';
                                                            $rsUser = CUser::GetByID($idUser);
                                                            $arUser = $rsUser->Fetch();
                                                            if (!empty($arUser)) {
                                                                $arFieldValue['FULL_NAME'] = CUser::FormatName(
                                                                    CSite::GetNameFormat(false),
                                                                    array(
                                                                        "NAME" => $arUser['NAME'],
                                                                        "LAST_NAME" => $arUser['LAST_NAME'],
                                                                        "SECOND_NAME" => $arUser['SECOND_NAME'],
                                                                        "LOGIN" => $arUser['LOGIN']
                                                                    )
                                                                );
                                                            } else {
                                                                $arFieldValue['FULL_NAME'] = '';
                                                            }
                                                            ob_start();
                                                            CCrmViewHelper::RenderUserCustomSearch(
                                                                array(
                                                                    'ID' =>$field['KEY'].'_CONTENT_USER',
                                                                    'SEARCH_INPUT_ID' => $field['KEY'].'_CONTENT_USER'.'_SEARCH',
                                                                    'SEARCH_INPUT_HINT' => GetMessage('CRM_FIELD_CONTACT_RESPONSIBLE_HINT'),
                                                                    'DATA_INPUT_ID' => $field['KEY'].'_CONTENT_USER',
                                                                    'COMPONENT_NAME' => $field['KEY'].'_CONTENT_USER',
                                                                    'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
                                                                    'USER' => array(
                                                                        'ID' => $idUser,
                                                                        'NAME' => $arFieldValue['FULL_NAME']
                                                                    )
                                                                )
                                                            );
                                                            $userSelectorHtml = ob_get_contents();
                                                            ob_end_clean();
                                                            echo $userSelectorHtml;
                                                            ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                            // list add table candiate
                                            case YNSIRConfig::TL_CANDIDATE_STATUS:
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                                                        <?
                                                        if($arResult['CANDIDATE_LOCK']['IS_LOCK'] == 'Y' && intval($arResult['CANDIDATE_LOCK']['ORDER_JOB_ID']) > 0) :
                                                        $pathOrder = ' /recruitment/job-order/detail/#job_order_id#/';
                                                        $orderLink = CComponentEngine::MakePathFromTemplate($pathOrder, array('job_order_id' => $arResult['CANDIDATE_LOCK']['ORDER_JOB_ID']));
                                                        $HtmlOrder = '<span>&rarr;</span><a href="' . $orderLink . '" title="' . $arResult['CANDIDATE_LOCK']['ORDER_JOB_TITLE'] . '">' . $arResult['CANDIDATE_LOCK']['ORDER_JOB_TITLE'] . '</a>';
                                                        $classLock = 'ynsir-associate-candidate-lock-edit';
                                                        ?>
                                                        <span class="recruitment-candidate-info-label-alignment"></span>
                                                        <span class="<?=$classLock?>"><?=$arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS][$arResult['CANDIDATE_LOCK']['STATUS_ID']] .  $HtmlOrder?></span>
                                                        <div hidden>
                                                        <select class="recruitment-item-table-select" hidden
                                                                id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                                                onchange='changeSelectList(this,"<?= $field['KEY'] ?>","<?= $arTypeList['KEY'] ?>")'
                                                                name="<?= $field['KEY'] ?>">
                                                            <?
                                                            else:?>
                                                            <select class="recruitment-item-table-select"
                                                                    id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                                                    onchange='changeSelectList(this,"<?= $field['KEY'] ?>","<?= $arTypeList['KEY'] ?>")'
                                                                    name="<?= $field['KEY'] ?>">
                                                            <?endif;
                                                            foreach ($arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS] as $iIdQ => $sNameQ) {
                                                                $sSelected = $arResult['CANDIDATE_DATA']['~'.$field['KEY']] == $iIdQ ? 'selected' : '';
                                                                ?>
                                                                <option value="<?= $iIdQ ?>" <?= $sSelected ?>><?= $sNameQ ?></option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                        <?if($arResult['CANDIDATE_LOCK']['IS_LOCK'] == 'Y' && intval($arResult['CANDIDATE_LOCK']['ORDER_JOB_ID']) > 0) :?>
                                                        </div>
                                                        <?endif?>
                                                        <div class="recruitment-candidate-item-error-label error" hidden=""
                                                             style="display: none;">
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                            // string multiple
                                            case YNSIRConfig::TL_CURRENT_JOB_TITLE:
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <?php
                                                    $i = 0;
                                                    foreach ($arResult['CANDIDATE_DATA'][$field['KEY']] as $vf) {
                                                        ?>
                                                        <div class="recruitment-candidate-info-data-wrap"
                                                             style="padding-bottom: 5px"
                                                             id="<?= strtolower($field['KEY']) ?>_field">
                                                            <input type="text" class="recruitment-candidate-item-inp"
                                                                   id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                                                   name="<?= $field['KEY'] ?>[]"
                                                                   value="<?= htmlspecialchars($vf['CONTENT'], ENT_QUOTES) ?>"
                                                                   size="50">
                                                            <div class="recruitment-candidate-item-error-label error" hidden=""
                                                                 style="display: none;">
                                                                <span></span>
                                                            </div>
                                                            <?php
                                                            if ($i > 0) {
                                                                ?>
                                                                <span class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"
                                                                      onclick="removeElement(this)"
                                                                ></span>
                                                                <?php
                                                            }
                                                            ?>
                                                        </div>
                                                        <?php
                                                        $i++;
                                                    }
                                                    ?>

                                                    <div class="phone-item-link-wrap group-field-swrap"
                                                         id="email_group_field">

                                                </td>
                                                <?php

                                                break;
                                            // text box
                                            case 'ADDITIONAL_INFO':
                                            case 'SKILL_SET':
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                            <textarea class="recruitment-edit-text-area" id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                      name=<?= $field['KEY'] ?>
                                      cols="30"
                                      rows="7"><?= htmlspecialchars($arResult['CANDIDATE_DATA'][$field['KEY']], ENT_QUOTES) ?></textarea>
                                                        <div class="recruitment-candidate-item-error-label error" hidden="">
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                            case 'EMAIL_OPT_OUT':
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                                                        <input class="crm-offer-checkbox" type="checkbox" id="ynsirc_email_opt_out"
                                                               name="EMAIL_OPT_OUT"
                                                               value="Y" <? if (isset($arResult['CANDIDATE_DATA']['EMAIL_OPT_OUT']) && intval($arResult['CANDIDATE_DATA']['EMAIL_OPT_OUT']) == 1 || !isset($arResult['CANDIDATE_DATA']['EMAIL_OPT_OUT'])) echo 'checked'; else echo ''; ?>>
                                                        <div class="recruitment-candidate-item-error-label error" hidden="">
                                                            <span></span>
                                                        </div>
                                                    </div>

                                                </td>
                                                <?php
                                                break;
                                            case 'FIRST_NAME':
                                                ?>

                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                                                        <input type="text" class="recruitment-candidate-item-inp"
                                                               id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                                               name=<?= $field['KEY'] ?>
                                                               value="<?= htmlspecialchars($arResult['CANDIDATE_DATA']['FIRST_NAME'], ENT_QUOTES) ?>"
                                                               size="50">
                                                        <div class="recruitment-candidate-item-error-label error" hidden=""
                                                             style="display: none;">
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                            case 'DOB':
                                                $arResult['CANDIDATE_DATA']['DOB'] = ($arResult['CANDIDATE_DATA']['DOB'] != '0000-00-00') ? $DB->FormatDate($arResult['CANDIDATE_DATA']['DOB'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_BX_SHORT']) : "";
                                                ?>

                                                <td class="recruitment-candidate-info-right">

                                                    <div class="recruitment-candidate-info-data-wrap" style="padding-bottom: 5px"
                                                         id="dob_field">
                                                <span class="profile-item-table-date">
                                                        <input style="width: 50%" type="text"
                                                               placeholder="<?=$arResult['FORMAT_DB_BX_SHORT']?>"
                                                               class="recruitment-candidate-item-inp" id="ynsirc_dob"
                                                               name="DOB"
                                                               value="<?= $arResult['CANDIDATE_DATA']['DOB'] ?>"
                                                               size="50">
                                                        <span class="profile-date-button" id="dob_date_btn"></span>
                                                        <div class="recruitment-candidate-item-error-label error" hidden=""
                                                             style="display: none;">
                                                            <span></span>
                                                        </div>

                                                    </span>

                                                        <script>
                                                            BX.ready(function () {
                                                                BX.YSIRDateLinkField.create(BX('ynsirc_dob'), BX('dob_date_btn'), {
                                                                    showTime: false,
                                                                    setFocusOnShow: false
                                                                });
                                                            });
                                                        </script>

                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                            case 'CANDIDATE_OWNER':
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                                                        <?php
                                                        $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'] = $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'] > 0 ? $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'] : $USER->GetID();
                                                        $rsUser = CUser::GetByID($arResult['CANDIDATE_DATA']['CANDIDATE_OWNER']);
                                                        $arUser = $rsUser->Fetch();
                                                        if (!empty($arUser)) {
                                                            $arFieldValue['FULL_NAME'] = CUser::FormatName(
                                                                CSite::GetNameFormat(false),
                                                                array(
                                                                    "NAME" => $arUser['NAME'],
                                                                    "LAST_NAME" => $arUser['LAST_NAME'],
                                                                    "SECOND_NAME" => $arUser['SECOND_NAME'],
                                                                    "LOGIN" => $arUser['LOGIN']
                                                                )
                                                            );
                                                        } else {
                                                            $arFieldValue['FULL_NAME'] = '';
                                                        }
                                                        ob_start();
                                                        CCrmViewHelper::RenderUserCustomSearch(
                                                            array(
                                                                'ID' => 'CANDIDATE_OWNER',
                                                                'SEARCH_INPUT_ID' => 'CANDIDATE_OWNER_SEARCH',
                                                                'SEARCH_INPUT_HINT' => GetMessage('CRM_FIELD_CONTACT_RESPONSIBLE_HINT'),
                                                                'DATA_INPUT_ID' => 'CANDIDATE_OWNER',
                                                                'COMPONENT_NAME' => 'CANDIDATE_OWNER',
                                                                'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
                                                                'USER' => array(
                                                                    'ID' => $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'],
                                                                    'NAME' => $arFieldValue['FULL_NAME']
                                                                )
                                                            )
                                                        );
                                                        $userSelectorHtml = ob_get_contents();
                                                        ob_end_clean();
                                                        echo $userSelectorHtml;
                                                        ?>
                                                        <div class="recruitment-candidate-item-error-label error" hidden=""
                                                             style="display: none;">
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                            case 'GENDER':
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                                                        <select class="recruitment-item-table-select"
                                                                id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                                                onchange='changeSelectList(this,"<?= $field['KEY'] ?>","<?= $arTypeList['KEY'] ?>")'
                                                                name="<?= $field['KEY'] ?>">

                                                            <option value="">-None-</option>
                                                            <?php

                                                            foreach (YNSIRConfig::getListConfig($field['KEY']) as $iIdQ => $sNameQ) {
                                                                $sSelected = $arResult['CANDIDATE_DATA'][$field['KEY']] == $iIdQ ? 'selected' : '';
                                                                ?>
                                                                <option value="<?= $iIdQ ?>"
                                                                        item_type="<?= $sNameQ['ADDITIONAL_INFO'] ?>"
                                                                        lable_type="<?= $sNameQ['ADDITIONAL_INFO_LABEL_' . strtoupper(LANGUAGE_ID)] ?>" <?= $sSelected ?>><?= $sNameQ ?></option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                        <div class="recruitment-candidate-item-error-label error" hidden=""
                                                             style="display: none;">
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                            case 'EMAIL':
                                            case 'CMOBILE':
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <?php
                                                    $i = 0;
                                                    foreach ($arResult['CANDIDATE_DATA'][$field['KEY']] as $vf) {
                                                        ?>
                                                        <div class="recruitment-candidate-info-data-wrap"
                                                             style="padding-bottom: 5px"
                                                             id="<?= strtolower($field['KEY']) ?>_field">
                                                            <input type="text" class="recruitment-candidate-item-inp"
                                                                   id="ynsirc_<?= strtolower($field['KEY']) ?>__<?= strtotime(date('Y-m-d H:i:s')) + $i ?>"
                                                                   name="<?= $field['KEY'] ?>[<?= strtotime(date('Y-m-d H:i:s')) + $i ?>]"
                                                                   value="<?= htmlspecialchars($vf['CONTENT'], ENT_QUOTES) ?>"
                                                                   size="50">
                                                            <?php
                                                            if ($i > 0) {
                                                                ?>
                                                                <span class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"
                                                                      onclick="$(this).parent().remove()"
                                                                ></span>
                                                                <?php
                                                            }
                                                            ?>
                                                            <div class="recruitment-candidate-item-error-label error" hidden=""
                                                                 style="display: none;">
                                                                <span></span>
                                                            </div>
                                                        </div>
                                                        <?php
                                                        $i++;
                                                    }
                                                    ?>

                                                    <div class="phone-item-link-wrap group-field-swrap"
                                                         id="email_group_field">
                                        <span id="section_phone_info_add_field"
                                              class="recruitment-candidate-info-link" onclick="moreField(this)"><?=GetMessage('YNSIR_CE_TITLE_ADD_MORE')?></span>
                                                    </div>
                                                </td>
                                                <?php

                                                break;
                                            case 'EXPECTED_SALARY':
                                            case 'CURRENT_SALARY'://onkeyup="ProfileUser.addSpacesEvent(this)
                                                $value = htmlspecialchars($arResult['CANDIDATE_DATA'][$field['KEY']], ENT_QUOTES);
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                                                        <input type="text" class="recruitment-candidate-item-inp"
                                                               id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                                               onkeyup="addSpacesEvent(this)"
                                                               name=<?= $field['KEY'] ?>
                                                               value="<?= $value ?>"
                                                               size="50">
                                                        <span class="icon-currency" ><?=GetMessage('YNSIR_GENERAL_CURRENCY')?></span>

                                                        <div class="recruitment-candidate-item-error-label error"
                                                             hidden=""
                                                             style="display: none;">
                                                            <span></span>
                                                        </div>
                                                        <script>
                                                            $(document).ready(function () {
                                                                $('#ynsirc_<?=strtolower($field['KEY'])?>').val(addSpaces('<?=$value?>'));
                                                            });
                                                        </script>
                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                            default:
                                                ?>
                                                <td class="recruitment-candidate-info-right">
                                                    <div class="recruitment-candidate-info-data-wrap">
                                                        <input type="text" class="recruitment-candidate-item-inp"
                                                               id="ynsirc_<?= strtolower($field['KEY']) ?>"
                                                               name=<?= $field['KEY'] ?>
                                                               value="<?= htmlspecialchars($arResult['CANDIDATE_DATA'][$field['KEY']], ENT_QUOTES) ?>"
                                                               size="50">
                                                        <div class="recruitment-candidate-item-error-label error" hidden=""
                                                             style="display: none;">
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php
                                                break;
                                        }
                                    }
                                    ?>
                                    <td class="recruitment-candidate-last-td"></td>
                                </tr>
                                <?php
                            }
                            break;
                    endswitch;?>
                    <tr>
                        <td class="recruitment-candidate-info-left"></td>
                        <td class="recruitment-candidate-info-right"></td>
                        <td class="recruitment-candidate-info-left"></td>
                        <td class="recruitment-candidate-info-right"></td>
                        <td class="recruitment-candidate-last-td"></td>
                    </tr>
                    <?
                endforeach;
                ?>

                </tbody>
            </table>
            <input name="ACTION" value="SAVE_CANDIDATE" hidden>

        </div>
        <div class="webform-buttons ">
            <input type="text" name="CANDIDATE_ID" value="<?= $arResult['ID'] ?>" hidden>
            <span class="webform-button webform-button-create">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="SAVE" value="SAVE" onclick="setCAction(1)">
				<span class="webform-button-right"></span>
			</span>
            <span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="SAVE_AND_NEW" value="Save &amp; New"
                       onclick="setCAction(2)">
				<span class="webform-button-right"></span>
			</span>
            <span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="button" name="CANCEL"
                       onclick="window.location='<?= $arResult["URL_LIST_CANDIDATE"] ?>'" value="Cancel">
				<span class="webform-button-right"></span>
			</span>
        </div>
    </form>
</div>
<script>
    JSObject = <?=json_encode($arResult['CONFIG'])?>;
    $(document).ready(function() {
        $("#ynsirc_experience").keydown(function (e) {
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                // Allow: Ctrl/cmd+A
                (e.keyCode == 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                // Allow: Ctrl/cmd+C
                (e.keyCode == 67 && (e.ctrlKey === true || e.metaKey === true)) ||
                // Allow: Ctrl/cmd+X
                (e.keyCode == 88 && (e.ctrlKey === true || e.metaKey === true)) ||
                // Allow: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                // let it happen, don't do anything
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    });
    function removeElement(item,hash_id) {
        $(item).parent().remove();
        $('#'+hash_id).remove();
    }

</script>