<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->IncludeComponent(
    'ynsirecruitment:config.perms.relation',
    '.default',
    $arResult
);

$this->SetViewTarget("pagetitle", 100);
?>
<div class="pagetitle-container pagetitle-align-right-container">
    <a href="/recruitment/config/" class="ynsir-candidate-detail-back">
        <?=GetMessage("YNSIR_CST_BACK_TITLE")?>
    </a>
</div>
<?
$this->EndViewTarget();
?>