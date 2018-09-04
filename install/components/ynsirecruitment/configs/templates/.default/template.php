<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$APPLICATION->SetTitle(GetMessage('YNSIR_SETTING_TITLE'));
$APPLICATION->SetAdditionalCSS("/bitrix/js/crm/css/crm.css");
CJSCore::Init(array("pin"));
CJSCore::Init(array("jquery"));

$arResult['RAND_STRING'] = 'ynsirecruitment';
$tabs = array();
$tabs['config_general'] = GetMessage('YNSIR_SETTING_GENERAL_TITLE');

$contentDescription['tab_content_config_general'] = GetMessage('YNSIR_SETTING_GENERAL_CONTENT');
$items['tab_content_config_general']['lists']['URL'] = '/recruitment/config/lists/order_job_status/';
$items['tab_content_config_general']['lists']['ICON_CLASS'] = 'img-properties';
$items['tab_content_config_general']['lists']['NAME'] = GetMessage('YNSIR_SETTING_GENERAL_LISTS');

$items['tab_content_config_general']['order_template']['URL'] = '/recruitment/config/job-order-template/0/';
$items['tab_content_config_general']['order_template']['ICON_CLASS'] = 'img-fields';
$items['tab_content_config_general']['order_template']['NAME'] = GetMessage('YNSIR_SETTING_GENERAL_JOB_ORDER_TEMPLATE');

$items['tab_content_config_general']['settings']['URL'] = '/recruitment/config/configs/';
$items['tab_content_config_general']['settings']['ICON_CLASS'] = 'img-automation';
$items['tab_content_config_general']['settings']['NAME'] = GetMessage('YNSIR_SETTING_GENERAL_CONFIGS_TEMPLATE');



if ($arResult['PERM_LIST']):
//    $tabs['config_type_list'] = GetMessage('YNSIR_SETTING_TYPE_LIST_TITLE');
//    $contentDescription['tab_content_config_type_list'] = GetMessage('YNSIR_SETTING_TYPE_LIST_CONTENT');
//
//    $arTypeList = YNSIRConfig::getTypeList();
//    foreach ($arTypeList as $key => $itemTypeList) {
//        $items['tab_content_config_type_list'][$itemTypeList['CODE']]['URL'] = '/recruitment/config/lists/' . $itemTypeList['CODE'] . '/';
//        $items['tab_content_config_type_list'][$itemTypeList['CODE']]['ICON_CLASS'] = 'img-fields';
//        $items['tab_content_config_type_list'][$itemTypeList['CODE']]['NAME'] = $itemTypeList['NAME'];
//    }
endif;
if ($arResult['PERM_CONFIG']):
    $tabs['config_permissions_ynsirecruitment'] = GetMessage('YNSIR_SETTING_PERMISSION_TITLE');
    $contentDescription['tab_content_config_permissions_ynsirecruitment'] = GetMessage('YNSIR_SETTING_PERMISSION_CONTENT');
    $items['tab_content_config_permissions_ynsirecruitment']['perm_ynsirecruitment']['URL'] = $siteDir . '/recruitment/config/perms/';
    $items['tab_content_config_permissions_ynsirecruitment']['perm_ynsirecruitment']['ICON_CLASS'] = 'img-permissions';
    $items['tab_content_config_permissions_ynsirecruitment']['perm_ynsirecruitment']['NAME'] = GetMessage('YNSIR_SETTING_PERM_RECRUITMENT');
endif;

$tabs['config_automation'] = GetMessage('YNSIR_SETTING_AUTOMATION_TITLE');
$contentDescription['tab_content_config_automation'] = GetMessage('YNSIR_SETTING_AUTOMATION_CONTENT');
$items['tab_content_config_automation']['config_bussiness_process']['URL'] = $siteDir . '/recruitment/config/bp/';
$items['tab_content_config_automation']['config_bussiness_process']['ICON_CLASS'] = 'img-bp';
$items['tab_content_config_automation']['config_bussiness_process']['NAME'] = GetMessage('YNSIR_SETTING_BUSSINESS_PROCESS');

$tabs['config_email'] = GetMessage('YNSIR_SETTING_EMAIL');

$items['tab_content_config_email']['mailtemplate']['URL'] = '/recruitment/config/mailtemplate/';
$items['tab_content_config_email']['mailtemplate']['ICON_CLASS'] = 'img-email';
$items['tab_content_config_email']['mailtemplate']['NAME'] = GetMessage('YNSIR_EMAIL_TEMPLATE');
?>

<div class="crm-container">
    <div class="view-report-wrapper-container">
        <? if (!empty($tabs)): ?>
            <div class="view-report-wrapper-wrapp">
                <div class="view-report-wrapper-shell">

                    <div class="view-report-sidebar view-report-sidebar-settings">
                        <? $counter = 0; ?>
                        <? foreach ($tabs as $tabId => $tabName): ?>
                            <? $class = (!$counter) ? 'sidebar-tab sidebar-tab-active' : 'sidebar-tab' ?>
                            <a href="javascript:void(0)" class="<?= $class ?>" id="tab_<?= $tabId ?>"
                               onclick="javascript:BX['YNSIRConfigClass_<?= $arResult['RAND_STRING'] ?>'].selectTab('<?= $tabId ?>');">
                                <?= $tabName ?>
                            </a>
                            <? $counter++; ?>
                        <? endforeach; ?>
                    </div>

                    <div class="view-report-wrapper">
                        <? $counter = 0; ?>
                        <? foreach ($items as $contentId => $contentList): ?>
                            <? $class = (!$counter) ? 'view-report-wrapper-inner active' : 'view-report-wrapper-inner' ?>
                            <div class="<?= $class ?>" id="<?= $contentId ?>">
                                <? foreach ($contentList as $itemData): ?>
                                    <a href="<?= $itemData['URL'] ?>" class="view-report-wrapper-inner-item">
                                        <span class="view-report-wrapper-inner-img <?= $itemData['ICON_CLASS'] ?>"></span>
                                        <span class="view-report-wrapper-inner-title"><?= $itemData['NAME'] ?></span>
                                    </a>
                                <? endforeach; ?>
                                <div class="view-report-wrapper-inner-clarification">
                                    <?= $contentDescription[$contentId] ?>
                                </div>
                            </div>
                            <? $counter++; ?>
                        <? endforeach; ?>
                    </div>

                </div>
            </div>
        <? else: ?>
            <div class="crm-configs-error-container"><?= GetMessage("YNSIR_CONFIGS_NO_ACCESS_ERROR") ?></div>
        <? endif; ?>
    </div>
</div>

<script type="text/javascript">
    BX(function () {
        BX['YNSIRConfigClass_<?= $arResult['RAND_STRING']?>'] = new BX.YNSIRConfigClass({
            randomString: '<?= $arResult['RAND_STRING'] ?>',
            tabs: <?=CUtil::PhpToJsObject(array_keys($tabs))?>
        });
    });
</script>