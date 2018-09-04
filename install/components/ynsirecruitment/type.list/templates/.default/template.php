<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init(array("jquery"));
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/autorun_proc.js');
$APPLICATION->SetTitle($arResult['TYPE_LIST']['NAME']);
$APPLICATION->ShowViewContent('crm-grid-filter');
$APPLICATION->ShowViewContent('pagetitle-flexible-space');
//if($arResult['ACCESS_PERMS']['ADD'] ) {
//    $arResult['BUTTONS'][] = array(
//        'TEXT' => GetMessage('YNSIR_ADD_BTN'),
//        'TITLE' => GetMessage('YNSIR_ADD_KEY_TITLE',array('#ENTITY#'=>$arResult['TYPE_LIST']['NAME'])),
//        'ONCLICK' => "addListElement()",
//        'ID' => 'bx-sharepoint-sync'
//    );
//}
$this->SetViewTarget("pagetitle-flexible-space", 50);
?>
<div  class=" add-container pagetitle-container pagetitle-align-right-container"><a onclick="addListElement()" >
        <span class="webform-small-button webform-small-button-blue crm-deal-add-button-plus">+</span>
        <span class="webform-small-button webform-small-button-blue bx24-top-toolbar-add crm-deal-add-button">
			<?=GetMessage('YNSIR_ADD_BTN')?>		</span>
    </a></div>
<?
$this->EndViewTarget();

$APPLICATION->IncludeComponent(
    'bitrix:crm.interface.toolbar',
    'title',
    array(
        'TOOLBAR_ID' => strtolower($arResult['GRID_ID']) . '_toolbar',
        'BUTTONS' => $arResult['BUTTONS']
    ),
    $component,
    array('HIDE_ICONS' => 'Y')
);
//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));
$yesnoList = array(
    array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
    array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
);

$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$applyButton = $snippet->getApplyButton(
    array(
        'ONCHANGE' => array(
            array(
                'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processApplyButtonClick('{$gridManagerID}')"))
            )
        )
    )
);

$actionList = array(array('NAME' => GetMessage('YNSIR_CONTACT_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

if ($arResult['ACCESS_PERMS']['DELETE']) {
    $controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
    $actionList[] = $snippet->getRemoveAction();
//    $controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
}

$APPLICATION->IncludeComponent(
    'ynsirecruitment:ynsir.interface.grid',
    'titleflex',
    array(
        'GRID_ID' => $arResult['GRID_ID'],
        'HEADERS' => $arResult['HEADERS'],
        'SORT' => $arResult['SORT'],
        'SORT_VARS' => $arResult['SORT_VARS'],
        'ROWS' => $arResult['ROWS'],
//        'TAB_ID' => $arResult['TAB_ID'],
        'RENDER_FILTER_INTO_VIEW' => 'crm-grid-filter',
        'AJAX_OPTION_JUMP' => 'N',//$arResult['AJAX_OPTION_JUMP'],
        'AJAX_OPTION_HISTORY' => 'N',//$arResult['AJAX_OPTION_HISTORY'],
        'FILTER' => $arResult['FILTER'],
        'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
        'ENABLE_LIVE_SEARCH' => true,
        'ACTION_PANEL' => $controlPanel,
        'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
            ? $arResult['PAGINATION'] : array(),
        'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
        'ENABLE_ROW_COUNT_LOADER' => true,
        'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
        'EXTENSION' => array(
            'ID' => $gridManagerID,
            'CONFIG' => array(
                'gridId' => $arResult['GRID_ID'],
                'serviceUrl' => '/bitrix/components/ynsirecruitment/type.list/list.ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
                'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParamcontacts['AJAX_LOADER'] : null
            ),
            'MESSAGES' => array(
                'deletionDialogTitle' => GetMessage('YNSIR_CONTACT_DELETE_TITLE'),
                'deletionDialogMessage' => GetMessage('YNSIR_CONTACT_DELETE_CONFIRM'),
                'deletionDialogButtonTitle' => GetMessage('YNSIR_CONTACT_DELETE')
            )
        )
    )
);

?>
    <div id="popupAddList" class="ajax-popup" style="width: 510px;height:150px" hidden></div>
<div id = "popupnonification" class="feed-add-success" style="display: none;width: 300px;">
    <div id="div_success">
    </div>
</div>
<div id = "popupdeleteconfirm" style="width: 350px;height:15px;display: none">
    <div id="name_delete"> </div>
    <div id="div_error_del"  style="display: none">
        <span></span>
    </div>
</div>
<div id = "popupaddSkill" style="display: none">
</div>
<script type="text/javascript">
    function testReload() {
        BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
    }

</script>
<script type="text/javascript">
    var JSMess = {
        YNSIR_ADD_LIST_TITLE:'<?=GetMessage('YNSIR_ADD_LIST_TITLE',array('#ENTITY#' => $arResult['TYPE_LIST']['NAME']))?>',
        YNSIR_EDIT_LIST_TITLE: '<?=GetMessage('YNSIR_EDIT_LIST_TITLE',array('#ENTITY#' => $arResult['TYPE_LIST']['NAME']))?>',
        YNSIR_LIST_ADD_BTN:'<?=GetMessage('YNSIR_ADD_BTN')?>',
        YNSIR_LIST_EDIT_BTN:'<?=GetMessage('YNSIR_EDIT_BTN')?>',
        YNSIR_SUCCESS_MESSAGE:'<?=GetMessage('YNSIR_SUCCESS_MESSAGE')?>',
        YNSIR_LIST_CANCEL_BTN:'<?=GetMessage('YNSIR_CANCEL_BTN')?>',
        YNSIR_LIST_KEY_EN_NULL:"<?=GetMessage('YNSIR_KEY_EN_NULL')?>",
        YNSIR_TITLE_NOTIFICATION:"<?=GetMessage('YNSIR_KEY_NOTIFICATION')?>",
        YNSIR_SKILL_DELETE_CONFIRM:"<?=GetMessage('YNSIR_SKILL_DELETE_CONFIRM')?>"
    }
    // end vn
    //en

    function Delete_key(id) {
        BX.ajax.insertToNode('/recruitment/config/lists/<?=$arParams['entity']?>/delete/'+id, BX('popupdeleteconfirm'));
        oPopupDeleteConfirm.show();
        $("#popupdeleteconfirm").css("height", "auto");
    }

    function addListElement(){
        $('#strErr span.error').html('');
        BX.ajax.insertToNode('/recruitment/config/lists/<?=$arParams['entity']?>/add/', BX('popupAddList'));
        popupform.setTitleBar({content: BX.create("span", {html: JSMess.YNSIR_ADD_LIST_TITLE})});
        $('#add_list_key').text(JSMess.YNSIR_LIST_ADD_BTN);
        popupform.show();
        $("#popupAddList").css("height", "auto");
    }

    function Edit_key(id){
        $('#strErr span.error').html('');
        BX.ajax.insertToNode('/recruitment/config/lists/<?=$arParams['entity']?>/edit/'+id, BX('popupAddList'));
        popupform.setTitleBar({content: BX.create("span", {html: JSMess.YNSIR_EDIT_LIST_TITLE})});
        $('#add_list_key').text(JSMess.YNSIR_LIST_EDIT_BTN);
        popupform.show();
        $("#popupAddList").css("height", "auto");
    }

    BX.ready(function () {
        popupform = new BX.PopupWindow("schema", null, {
            content: BX('popupAddList'),
            zIndex: 100,
            offsetLeft: 0,
            offsetTop: 0,
            draggable: {restrict: true},
            overlay: true,
            titleBar: {content: BX.create("span", {html: JSMess.YNSIR_ADD_OTS_TITLE})},
            closeIcon: {right: "12px", top: "12px"},
            buttons: [
                new BX.PopupWindowButton({
                    text: JSMess.YNSIR_LIST_ADD_BTN,
                    className: "",
                    id: "add_list_key",
                    events: {
                        click: function () {
                            try {
                                var popup = this;
                                var btn = $('#add_list_key');
                                btn.css("pointer-events", "none");
                                var NAME_KEY_END = $('#name_en_list').val();
                                var error = $('#strErr span.error');

                                error.text("");
                                if (NAME_KEY_END.length <= 0) {
                                    error.html(JSMess.YNSIR_LIST_KEY_EN_NULL);
                                    $('#trErr').show();
                                    btn.css("pointer-events", "auto");
                                }
                                else
                                    {
                                    BX.showWait();
                                    BX.ajax.submit(BX("ynsirecruitment-config-list"), function (data) {
                                        data = $.parseJSON(data);
                                        BX.closeWait();
                                        if (!data.SUCCESS) {
                                            error.html(data.MESSAGE);
                                            $('#trErr').show();
                                            btn.css("pointer-events", "auto");
                                        }
                                        else {
                                            if (data.EDIT) {
                                                $('#div_success').text(JSMess.YNSIR_EDIT_LIST_TITLE +' '+ JSMess.YNSIR_SUCCESS_MESSAGE);
                                            } else {
                                                $('#div_success').text(JSMess.YNSIR_ADD_LIST_TITLE +' '+ JSMess.YNSIR_SUCCESS_MESSAGE);
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
                    events: {
                        click: function () {
                            var popup = this;
                            popup.popupWindow.close();
                            var btn = $('#add_list_key');
                            btn.css("pointer-events", "auto");
                            $('#item-error-label').text("");
                        }
                    }
                })
            ]
        });
        oPopupDeleteConfirm = new BX.PopupWindow('popup_edit', window.body, {
            content: BX('popupdeleteconfirm'),
            autoHide : true,
            offsetTop : 1,
            overlay:true,
            offsetLeft : 0,
            lightShadow : true,
            overlay:true,
            closeByEsc : true,
            draggable: {restrict:true},
            titleBar: {content: BX.create("span", {html: JSMess.YNSIR_SKILL_DELETE_CONFIRM})},
            closeIcon: { right : "12px", top : "12px"},
            buttons: [
                new BX.PopupWindowButton({
                    text: "<?=GetMessage("YNSIR_SKILL_DELETE_BTN")?>",
                    className: "",
                    id:"del_status_button",
                    events: {click: function(){
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
                                    $('#div_success').text("<?=GetMessage('YNSIR_SKILL_DELETE_SUCCESS')?>");
                                    actionSuccessPopup.show();
                                    setTimeout(function () {
                                        actionSuccessPopup.close();
                                        BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
                                    }, 2000);
                                } else {
                                    BX.closeWait();
                                    $('#div_error_del').show().find("span").text("<?=GetMessage('YNSIR_SKILL_ERROR')?>");
                                    button.css("pointer-events", "auto");
                                }
                            });
                        } catch (e) {
                            $('#div_error_del').show().find("span").text("<?=GetMessage('YNSIR_SKILL_ERROR')?>");
                            button.css("pointer-events", "auto");
                        }}
                    }})
                ,
                new BX.PopupWindowButton({
                    text: "<?=GetMessage("YNSIR_CANCEL_BTN")?>",
                    className: "popup-window-button-link",
                    events: {click: function(){
                        this.popupWindow.close();
                        var button = $("#del_status_button");
                        $('#div_error_del').show().find("span").text("");
                        button.css("pointer-events", "auto");
                    }}
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

    });
</script>
