<?
use \Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest()->toArray();

$parameters = array();
if (is_array($arParams['FORM_PARAMETERS'])) {
    $parameters = $arParams['FORM_PARAMETERS'];
}
$edit = $arParams['ACTION'] == 'edit';
$candidate_id = $arParams['CANDIDATE_ID'];
//echo $candidate_id;die;
$existingTask = intval($parameters['ID']) > 0;
$isIFrame = $arResult['IFRAME'] == 'Y';
$iFrameType = $arResult['IFRAME_TYPE'];
$isSideSlider = $iFrameType == 'SIDE_SLIDER';

?>

<? if ($isIFrame): ?>

    <?
    // to stay inside iframe after form submit and also after clicking on "to task" links
    // for other links target="_top" is used
    $urlParameters = array('IFRAME' => 'Y');
    if ($iFrameType != '') {
        $urlParameters['IFRAME_TYPE'] = $iFrameType;
    }

    $parameters['TASK_URL_PARAMETERS'] = $urlParameters;

    global $APPLICATION;
    $APPLICATION->RestartBuffer();
    ?>

    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= LANGUAGE_ID ?>" lang="<?= LANGUAGE_ID ?>">
    <head>
        <script type="text/javascript">
            // Prevent loading page without header and footer
            if (window == window.top) {
                window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam("", array("IFRAME", "IFRAME_TYPE"))); ?>";
            }
        </script>
        <? $APPLICATION->ShowHead(); ?>
    </head>
    <body id="tasks-iframe-popup-scope" class="
			template-<?= SITE_TEMPLATE_ID ?> <? $APPLICATION->ShowProperty("BodyClass"); ?> <? if ($isSideSlider): ?>task-iframe-popup-side-slider<?endif ?>" onload="window.top.BX.onCustomEvent(window.top, 'tasksIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'tasksIframeUnload');">

<? if ($isSideSlider): ?>
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
                            title="<?= Loc::getMessage('TASKS_TIP_TEMPLATE_COPY_CURRENT_URL') ?>"></span><?endif ?></span>
                </div>
            </div>
        </div>
    </div>
<?// side slider needs for an additional controller, but in case of standard iframe there is a controller already: tasks.iframe.popup default template?>

<?endif ?>

    <div class="task-iframe-workarea <? if ($isSideSlider): ?>task-iframe-workarea-own-padding<?endif ?>" id="tasks-content-outer">
    <div class="task-iframe-sidebar">
        <? $APPLICATION->ShowViewContent("sidebar"); ?>
    </div>
    <div class="task-iframe-content">
    <?
endif;
$APPLICATION->IncludeComponent(
    "ynsirecruitment:candidate.candidate",
    $template,
    array('CANDIDATE_ID' => $candidate_id)

);

if ($isIFrame):?>
    </div>
    </div>
    </body>
    </html><?
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
    die(); ?>

<? endif ?>