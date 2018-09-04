<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$APPLICATION->SetTitle(GetMessage("YNSIR_JOL_T_JOB_ORDER_TITLE"));
// =================== general import ==========================================
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');
if (SITE_TEMPLATE_ID === 'bitrix24') {
    $APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable()) {
    CBitrix24::initLicenseInfoPopupJS();
}
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/ynsirecruitment/css/autorun_proc.css');
// =================== button add new ===========================================
?>

<?
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

// =================== list grid ================================================
$arResult['GRID_DATA'] = array();

$userPermissions = $arResult['USER_PERMISSIONS'];

foreach ($arResult['FEEDBACK_DATA'] as $iKeyFB => $arItemFB) {
    // permission here
    $bDetail =true;
    $bEdit = true;

    // convert data to text
    $arFBTempData = array();
    foreach ($arItemFB as $sKeyField => $itemValue) {
        $bFieldPermission = true;
        if ($bFieldPermission == true) {
            switch ($sKeyField) {
                // unique user
                case 'CREATED_BY':
                case 'MODIFIED_BY':
                    $arResult['DATA_USER'] = YNSIRGeneral::getUserInfo($itemValue);
                    $arFBTempData[$sKeyField] = YNSIRGeneral::tooltipUser(
                        $arResult['DATA_USER'][$itemValue], 0,
                        'user_tooltip_' . $iKeyFB . '_' . $sKeyField . '_' . $arResult['DATA_USER'][$itemValue]['ID']
                    );
                    break;
                // template
                case 'MODIFIED_DATE':
                case 'CREATED_DATE':
                    $arFBTempData[$sKeyField] = FormatDateEx($itemValue, $arResult['FORMAT_DB_BX_FULL'], $arResult['DATE_TIME_FORMAT']);
                    break;
                default:
                    $arFBTempData[$sKeyField] = $itemValue;
                    break;
            }
        } else {
            $arFBTempData[$sKeyField] = '';
        }
    }
    // action on row data
    $arActions = array();
    if ($bDetail == true) {

        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_FEEDBACK_DETAIL'),
            'TEXT' => GetMessage('YNSIR_FEEDBACK_DETAIL'),
            'ONCLICK' => "addFeedbackElement('VIEW',".$arItemFB['ID'].")",
            'DEFAULT' => true
        );
    }

    // item grid data
    $arResult['GRID_DATA'][] = array(
        'id' => $arItemFB['ID'],
        'actions' => $arActions,
        'data' => $arFBTempData,
        'editable' => 'N',
        'DEFAULT' => true
    );
}

//region Action Panel
$allowDelete = $arResult['PERMS']['DELETE'];
$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));
if ($allowDelete) {
    $Removebtn = $snippet->getRemoveButton();
    $Removebtn['ONCHANGE'][0]['CONFIRM_MESSAGE'] = GetMessage('YNSIR_JOB_T_DELETE_CONFIRM');
    $controlPanel['GROUPS'][0]['ITEMS'][] = $Removebtn;
    $actionList[] = $snippet->getRemoveAction();
}
//$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
if ($arResult['INTERNAL']) {
    $APPLICATION->ShowViewContent('feedback-interal-filter');
}
$activityEditorID = '';
$gridManagerID = $arResult['GRID_ID'] . '_MANAGER';
$APPLICATION->IncludeComponent(
    'ynsirecruitment:ynsir.interface.grid',
    'titleflex',
    array(
        'GRID_ID' => $arResult['GRID_ID'],
        'HEADERS' => $arResult['HEADERS'],
        'SORT' => $arResult['SORT'],
        'SORT_VARS' => $arResult['SORT_VARS'],
        'ROWS' => $arResult['GRID_DATA'],
        'FORM_ID' => $arResult['FORM_ID'],
        'TAB_ID' => $arResult['TAB_ID'],
        'AJAX_ID' => $arResult['AJAX_ID'],
        'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
        'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
        'FILTER' => $arResult['FILTER'],
        'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
        'ENABLE_LIVE_SEARCH' => true,
        'RENDER_FILTER_INTO_VIEW' => $arResult['INTERNAL'] ? 'feedback-interal-filter' : '',
        'ACTION_PANEL' => $controlPanel,
        'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
            ? $arResult['PAGINATION'] : array(),
        'ENABLE_ROW_COUNT_LOADER' => true,
        'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
        'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
        'SHOW_CHECK_ALL_CHECKBOXES' => false, // checkbox : check all
        'SHOW_ROW_CHECKBOXES' => true, // checkbox : check for all record in database
        'EXTENSION' => array(
            'ID' => $gridManagerID,
            'CONFIG' => array(
                'ownerTypeName' => YNSIR_OWNER_TYPE_ORDER,
                'gridId' => $arResult['GRID_ID'],
                'activityEditorId' => $activityEditorID,
                'taskCreateUrl' => isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
                'serviceUrl' => '/bitrix/components/ynsirecruitment/feedback.list/list.ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
                'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
            ),
            'MESSAGES' => array(
                'deletionDialogTitle' => GetMessage('YNSIR_JOB_T_DELETE_TITLE'),
                'deletionDialogMessage' => GetMessage('YNSIR_JOB_T_DELETE_CONFIRM'),
                'deletionDialogButtonTitle' => GetMessage('YNSIR_JOB_T_ORDER_DELETE')
            )
        )
    )
);
?>

    <div id="popupAddFeedback" class="ajax-popup"  hidden>

    </div>
    <div id = "popupnonification" class="feed-add-success" style="display: none;width: 300px;">
        <div id="div_success">
        </div>
    </div>
    <div id = "popupnopermission" class="feed-add-success" style="display: none;width: 300px;">
        <div id="">
        </div>
    </div>

    <script>
        function addFeedbackElement(action='',id = 0) {
            $("#closed_feedback").hide();
            if (action !='EDIT' && action !='ADD' && action !='VIEW'){
                BX.ajax.insertToNode('/recruitment/feedback/edit/0/?candidate_id=<?=$arParams['CANDIDATE_ID']?>&job_order_id=<?=$arParams['JOB_ORDER_ID']?>&round_id=<?=$arParams['ROUND_ID']?>&action='+action, BX('popupnopermission'));
                actionPermossionPopup.setTitleBar({content: BX.create("span", {html: JSMess.YNSIR_ADD_LIST_TITLE})});
                actionPermossionPopup.show();
            }else{
                $('#strErr span.error').html('');
                if(action =='VIEW'){
                    BX.ajax.insertToNode('/recruitment/feedback/detail/'+id+'/?action=VIEW', BX('popupAddFeedback'));
                    $("#add_feedback").hide();
                    $("#cancel_feedback").hide();
                    $("#closed_feedback").show();
                }else{
                    BX.ajax.insertToNode('/recruitment/feedback/edit/0/?candidate_id=<?=$arParams['CANDIDATE_ID']?>&job_order_id=<?=$arParams['JOB_ORDER_ID']?>&round_id=<?=$arParams['ROUND_ID']?>&action='+action, BX('popupAddFeedback'));
                    $("#add_feedback").show();
                }
                popupform.setTitleBar({content: BX.create("span", {html: JSMess.YNSIR_ADD_LIST_TITLE})});
                $('#add_feedback').text(JSMess.YNSIR_LIST_ADD_BTN);

                popupform.show();
                $("#popupAddFeedback").css("height", "auto");
            }

        }

        var JSMess = {
            YNSIR_ADD_LIST_TITLE: '<?=GetMessage('YNSIR_ADD_LIST_TITLE', array('#ENTITY#' => $arResult['TYPE_LIST']['NAME']))?>',
            YNSIR_EDIT_LIST_TITLE: '<?=GetMessage('YNSIR_EDIT_LIST_TITLE', array('#ENTITY#' => $arResult['TYPE_LIST']['NAME']))?>',
            YNSIR_LIST_ADD_BTN: '<?=GetMessage('YNSIR_'.$arResult['FEEDBACK_ACTION'].'_BTN')?>',
            YNSIR_LIST_EDIT_BTN: '<?=GetMessage('YNSIR_EDIT_BTN')?>',
            YNSIR_SUCCESS_MESSAGE: '<?=GetMessage('YNSIR_SUCCESS_MESSAGE')?>',
            YNSIR_LIST_CANCEL_BTN: '<?=GetMessage('YNSIR_CANCEL_BTN')?>',
            YNSIR_LIST_KEY_EN_NULL: "<?=GetMessage('YNSIR_KEY_EN_NULL')?>",
            YNSIR_TITLE_NOTIFICATION: "<?=GetMessage('YNSIR_KEY_NOTIFICATION')?>",
            YNSIR_FEEDBACK_DELETE_CONFIRM: "<?=GetMessage('YNSIR_FEEDBACK_DELETE_CONFIRM')?>"
        }
        BX.ready(function () {
            var Height = Math.max(450, window.innerHeight - 500);
            var Width = Math.max(750, window.innerWidth - 600);
            var minHeight = 400;
            var minWidth = 600;
            popupform = new BX.PopupWindow("schema", null, {
                content: BX('popupAddFeedback'),
                zIndex: 100,
                offsetLeft: 0,
                offsetTop: 0,
                height: Height,
                width: Width,
                className: 'fixed-position',
                minHeight: minHeight,
                minWidth: minWidth,
                draggable: {restrict: true},
                overlay: true,
                "titleBar":
                    {
                        "content": BX.create("SPAN", {
                            "attrs":
                                {"className": "popup-window-titlebar-text"},
                            "text": JSMess.YNSIR_ADD_OTS_TITLE
                        })

                    },
                closeIcon: {right: "12px", top: "12px"},
                buttons: [
                    new BX.PopupWindowButton({
                        text: JSMess.YNSIR_LIST_ADD_BTN,
                        className: "",
                        id: "add_feedback",
                        events: {
                            click: function () {
                                try {
                                    var popup = this;
                                    var error = false;
                                    var error_div = $("#feedback_error");
                                    error_div.hide();
                                    $(".error-validate").hide();
                                    var error_msg = '';
                                    var btn = $('#add_feedback');
                                    btn.css("pointer-events", "none");
                                    var title = $("#feedback_title").val();
                                    var candidate_id = $("#feedback_candidate_id").val();
                                    var job_order_id = $("#feedback_job_order_id").val();
                                    var round_id = $("#feedback_round_id").val();
                                    var description = $("#bxed_feedback_description").val();
                                    if(title == ''){
                                        error = true;
                                        $("#feedback_title").addClass('input-error');
                                        error_msg = 'Title is not specified.';
                                    }
                                    if(candidate_id <= 0){
                                        error = true;
                                        $("#feedback_job_order_id").addClass('input-error');
                                        error_msg = 'Candidate is not specified.';
                                    }
                                    if(job_order_id <= 0){
                                        error = true;
                                        $("#feedback_job_order_id").addClass('input-error');
                                        error_msg = 'Job Order is not specified.';
                                    }
                                    if(round_id <= 0){
                                        error = true;
                                        $("#feedback_round_id").addClass('input-error');
                                        error_msg = 'Round is not specified.';
                                    }
                                    if(description == ''){
                                        error = true;
                                        $("#popupAddFeedback .feed-add-post-form").addClass('input-error');
                                        error_msg = 'Description is not specified.';
                                        $("#ERROR_MSG_DESCRIPTION").html(error_msg);
                                        $("#ERROR_MSG_DESCRIPTION").show();
                                    }
                                    if (error) {
                                        btn.css("pointer-events", "auto");
                                    }
                                    else {
                                        BX.showWait();
                                        BX.ajax.submit(BX("ynsirecruitment-config-list"), function (data) {
                                            data = $.parseJSON(data);
                                            BX.closeWait();
                                            if (data.STATUS != 'SUCCESS') {
                                                var error_msg = '';
                                                for(var i in data.ERROR) {
                                                    $("#ERROR_MSG_"+i).html(data.ERROR[i]['msg']);
                                                    $("#ERROR_MSG_"+i).show();
                                                    error_msg += data.ERROR[i]['msg']+'<br>';
                                                }

                                                error_div.html(error_msg);
                                                error_div.show();
                                                btn.css("pointer-events", "auto");
                                            }
                                            else {
                                                if (data.EDIT) {
                                                    $('#div_success').text(JSMess.YNSIR_EDIT_LIST_TITLE + ' ' + JSMess.YNSIR_SUCCESS_MESSAGE);
                                                } else {
                                                    $('#div_success').text(JSMess.YNSIR_ADD_LIST_TITLE + ' ' + JSMess.YNSIR_SUCCESS_MESSAGE);
                                                }
                                                actionSuccessPopup.show();
                                                setTimeout(function () {
                                                    actionSuccessPopup.close();
                                                    BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
                                                }, 2000);
                                                popup.popupWindow.close();
                                                btn.css("pointer-events", "auto");
                                            }
                                        });
                                    }
                                } catch (e) {
                                    console.log(e);
//                                window.location.reload();
                                }
                            }
                        }
                    }),
                    new BX.PopupWindowButton({
                        text: JSMess.YNSIR_LIST_CANCEL_BTN,
                        className: "popup-window-button-link",
                        id:'cancel_feedback',
                        events: {
                            click: function () {
                                var popup = this;
                                popup.popupWindow.close();
                                var btn = $('#add_feedback');
                                btn.css("pointer-events", "auto");
                                $('#item-error-label').text("");
                            }
                        }
                    }),
                    new BX.PopupWindowButton({
                        text: "<?=GetMessage('FEEDBACK_BUTTON_CLOSE')?>",
                        className: "popup-window-button-link",
                        id:'closed_feedback',
                        events: {
                            click: function () {
                                var popup = this;
                                popup.popupWindow.close();
                                $('#add_feedback').show();
                                $('#cancel_feedback').show();
                                $('#closed_feedback').hide();
                            }
                        }
                    })
                ]
            });
            oPopupDeleteConfirm = new BX.PopupWindow('popup_edit', window.body, {
                content: BX('popupdeleteconfirm'),
                autoHide: true,
                offsetTop: 1,
                overlay: false,
                offsetLeft: 0,
                lightShadow: true,
                overlay: true,
                closeByEsc: true,
                draggable: {restrict: false},
                titleBar: {content: BX.create("span", {html: JSMess.YNSIR_FEEDBACK_DELETE_CONFIRM})},
                closeIcon: {right: "18px", top: "18px"},
                buttons: [
                    new BX.PopupWindowButton({
                        text: "<?=GetMessage("YNSIR_FEEDBACK_DELETE_BTN")?>",
                        className: "",
                        id: "del_status_button",
                        events: {
                            click: function () {
                                var popup = this;
                                var button = $("#del_status_button");
                                button.css("pointer-events", "none");
                                try {
                                    BX.showWait();
                                    BX.ajax.submit(BX("ynsirecruitment-config-list-delete"), function (data) {
                                        data = $.parseJSON(data);
                                        if (data.SUCCESS) {
                                            button.css("pointer-events", "auto");
                                            BX.closeWait();
                                            popup.popupWindow.close();
                                            $('#div_success').text("<?=GetMessage('YNSIR_FEEDBACK_DELETE_SUCCESS')?>");
                                            actionSuccessPopup.show();
                                            setTimeout(function () {
                                                actionSuccessPopup.close();
                                                BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
                                            }, 2000);
                                        } else {
                                            BX.closeWait();
                                            $('#div_error_del').show().find("span").text("<?=GetMessage('YNSIR_FEEDBACK_ERROR')?>");
                                            button.css("pointer-events", "auto");
                                        }
                                    });
                                } catch (e) {
                                    $('#div_error_del').show().find("span").text("<?=GetMessage('YNSIR_FEEDBACK_ERROR')?>");
                                    button.css("pointer-events", "auto");
                                }
                            }
                        }
                    })
                    ,
                    new BX.PopupWindowButton({
                        text: "<?=GetMessage("YNSIR_CANCEL_BTN")?>",
                        className: "popup-window-button-link",
                        events: {
                            click: function () {
                                this.popupWindow.close();
                                var button = $("#del_status_button");
                                $('#div_error_del').show().find("span").text("");
                                button.css("pointer-events", "auto");
                            }
                        }
                    })
                ]
            });
            actionSuccessPopup = new BX.PopupWindow('popup_info', window.body, {
                content: BX('popupnonification'),
                autoHide: true,
                offsetTop: 1,
                offsetLeft: 0,
                overlay: true,
                closeByEsc: true,
                draggable: {restrict: true},
                titleBar: {content: BX.create("span", {html: JSMess.YNSIR_TITLE_NOTIFICATION})},
                closeIcon: {right: "12px", top: "12px"},
            });
            actionPermossionPopup = new BX.PopupWindow('popup_info', window.body, {
                content: BX('popupnopermission'),
                autoHide: true,
                offsetTop: 1,
                offsetLeft: 0,
                overlay: true,
                closeByEsc: true,
                draggable: {restrict: true},
                titleBar: {content: BX.create("span", {html: JSMess.YNSIR_ADD_OTS_TITLE})},
                closeIcon: {right: "12px", top: "12px"},
            });

        });
    </script>
<?
if( $arResult['REPARE_FEEDBACK_DATA'] === true && ($arResult['FEEDBACK_ACTION'] == 'EDIT' || $arResult['FEEDBACK_ACTION'] == 'ADD' )){
    //check action add or edit feedback
    ?>
    <script>
        BX.ready(function()
            {
                addFeedbackElement("<?=$arResult['FEEDBACK_ACTION']?>");
            }
        );
    </script>
    <?
}
?>