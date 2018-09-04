<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$arFileInfo = CFile::GetByID($_REQUEST['objectId']);
$arDetailIFile = $arFileInfo->arResult;

$APPLICATION->RestartBuffer();

$arData = array(
    'id' => $arDetailIFile[0]['EXTERNAL_ID'],
    'viewUrl' => 'https:\/\/drive.google.com/viewerng/viewer?embedded=true\u0026url=http%3A%2F%2F192.168.17.24%2Fdocs%2Fpub%2F'.$arDetailIFile[0]['EXTERNAL_ID'].'%2Fdownload%2F%3F%26',
    'neededDelete' => false,
    'neededCheckView' => true,
    'status' => 'success',
);

echo json_encode($arData);
die;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>