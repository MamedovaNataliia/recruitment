<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init(array("jquery"));

$sFileExt = implode(', ', $arResult['ALLOW_UPLOAD_EXT']);
?>
<div class="bx-disk-container posr" id="bx-disk-container-<?=$arResult['HTML_ELEMENT_ID']?>">
	<div id="folder_toolbar-<?=$arResult['HTML_ELEMENT_ID']?>" class="hide-form">
		<label class="bx-disk-context-button" title="<?=$arResult['BUTTON_MESSAGE']?>" for="inputContainerFolderList<?=$arResult['HTML_ELEMENT_ID']?>">
			<span class="bx-disk-context-button-icon element-upload"></span>
			<span class="bx-disk-context-button-text"><?=$arResult['BUTTON_MESSAGE']?></span>
		</label>
	</div>
    <div id="ynsir_show_ext_file_cv">
        <div class="ynsir-show-ext-file-cv">
            <?=str_replace('#ALLOW_UPLOAD_EXT#', rtrim($sFileExt, ','),GetMessage("YNSIR_TDFU_ALLOW_UPLOAD_EXT"))?>
        </div>
    </div>
</div>
<?php
$folder = \Bitrix\Disk\Folder::loadById($arResult['TARGET_FOLDER']);
$APPLICATION->IncludeComponent(
	'bitrix:disk.file.upload',
	'',
	array(
		'STORAGE' => array(),
		'FOLDER' => $folder,
		'CID' => 'FolderList' . $arResult['HTML_ELEMENT_ID'],
		'INPUT_CONTAINER' => '((BX.__tmpvar=BX.findChild(BX("folder_toolbar-'.$arResult['HTML_ELEMENT_ID'].'"), {className : "element-upload"}, true))&&BX.__tmpvar?BX.__tmpvar.parentNode:null)',
		'DROPZONE' => 'BX("bx-disk-container-'.$arResult['HTML_ELEMENT_ID'].'")'
	)
);
?>
<script>
    var _html_element_id = '<?=$arResult['HTML_ELEMENT_ID']?>';
    var _user_id = <?=$USER->GetID()?>;
    if(YNSIRDFUJSData === undefined){
        var YNSIRDFUJSData = {};
    }
    YNSIRDFUJSData[_html_element_id] = {
        _onUploadDone: '<?=$arParams["ONUPLOADDONE"]?>',
        _allow_upload_ext: <? echo json_encode($arResult['ALLOW_UPLOAD_EXT']);?>,
        _multiple_file: <?=$arResult['FILE_MULTIPLE']?>,
        _count_file: 0,
        _text_replace: '<?=GetMessage('YNSIR_TDFU_REPLACE')?>',
    };
</script>
