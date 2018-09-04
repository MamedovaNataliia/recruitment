<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<form action="/recruitment/config/lists/<?= $arParams['entity'] ?>/delete/<?=$arParams['ELEMENT_ID']?>" name="ynsirecruitment-config-list-delete" id="ynsirecruitment-config-list-delete"
      enctype="multipart/form-data" method="POST">
    <?= bitrix_sessid_post() ?>
    <div>Do you sure you want to delete item value "<?=$arResult['ELEMENT']['NAME_EN']?>"?</div>
    <input type="hidden" name="DEL" value="Y">
    <input type="hidden" name="submit" value="submit">
</form>
