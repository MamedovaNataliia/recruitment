var _id_edit = 0;
var _id_selected = 0;
var _url_path = '/bitrix/components/ynsirecruitment/config.order.template/ajax.php?id=';

function selectTemplateTab(id){
    $('#name-error-message').text('').hide();
    $('#description-error-message').text('').hide();
    var flag = false;
    _id_selected = 0;
    $('#template-content').html('');
    $('#template-content').text('');
	for(var key in JSCOTData){
		if(JSCOTData[key].ID == id){
            $('#template-content').html(JSCOTData[key].CONTENT_HTML);
            $('.status_tab').removeClass('status_tab_active');
            $('#template_tab_' + id).addClass('status_tab_active');
            $('#template-active').removeAttr('checked');
            $('#name_template').val(JSCOTData[key].NAME_TEMPLATE);
            if(JSCOTData[key].ACTIVE == 1){
                $('#template-active').attr('checked', 'checked');
            }
            _id_selected = id;
            popupform.setTitleBar(JSCOTMess.YNSIR_COT_T_TEMPLATE_TITLE + ': ' + JSCOTData[key].NAME_TEMPLATE);
            break;
		}
	}
	$('#template-configs-footer').show();
	if(JSCOTData.length <= 0){
        $('#template-configs-footer').hide();
    }
}

function editTemplate(){
    BX.ajax.insertToNode(_url_path + _id_selected, BX('content-template-design'));
    for(var key in JSCOTData){
        if(JSCOTData[key].ID == _id_selected){
            $('#template-active').removeAttr('checked');
            $('#name_template').val(JSCOTData[key].NAME_TEMPLATE);
            if(JSCOTData[key].ACTIVE == 1){
                $('#template-active').attr('checked', 'checked');
            }
        }
    }
    _id_edit = _id_selected;
    popupform.show();
    LHEPostForm.getHandler('idLHE_JOB_ORDER_TEMPLATE').showPanelEditor();
}

function deleteTemplate(){
    popupConfirm.setTitleBar(JSCOTMess.YNSIR_COT_T_DELETE_TEMPLATE_TITLE);
    popupConfirm.show();
}

function validateData(){
    $('.error').hide();
    $bResult = true;
    try{
        var name = $('#name_template').val();
        name = name.trim();
        if(name.length < 10){
            $bResult = false;
            $('#name-error-message').text(JSCOTMess.YNSIR_COT_T_NAME_VALIDATE).show();
        }
        var content = $('#bxed_idLHE_JOB_ORDER_TEMPLATE').val();
        content = content.trim();
        if(content.length < 100){
            $bResult = false;
            $('#description-error-message').text(JSCOTMess.YNSIR_COT_T_CONTENT_VALIDATE).show();
        }
    }
    catch(e){
        location.reload();
    }
    return $bResult;
}

function addNewTemplate(){
    $('#name-error-message').hide();
    $('#description-error-message').hide();
    popupCate.setTitleBar(JSCOTMess.YNSIR_COT_T_CATEGORY_TITLE);
    if(_list_cate.length <= 0){
        popupCate.show();
    }
    else {
        var temp_cate_id = parseInt($('#teamplate_cate').val());
        if(_list_cate.indexOf(temp_cate_id) < 0){
            popupCate.show();
        }
        else {
            BX.ajax.insertToNode(_url_path + 0, BX('content-template-design'));
            LHEPostForm.getHandler('idLHE_JOB_ORDER_TEMPLATE').showPanelEditor();
            popupform.setTitleBar(JSCOTMess.YNSIR_COT_T_ADD_NEW_TITLE);
            _id_edit = 0;
            $('#name_template').val('');
            $('#template-active').removeAttr('checked');
            popupform.show();
        }
    }
}

BX.ready(function () {
    popupform = new BX.PopupWindow("schema", null, {
        content: BX('popup_template'),
        zIndex: 100,
        offsetLeft: 0,
        offsetTop: 0,
        draggable: {restrict: true},
        overlay: true,
        titleBar: {content: BX.create("span", {html: 'Edit job order template'})},
        closeIcon: {right: "12px", top: "12px"},
        buttons: [
            new BX.PopupWindowButton({
                text: JSCOTMess.YNSIR_COT_T_SAVE_BTN,
                className: "",
                id: "add_list_key",
                events: {
                    click: function () {
                        $('.error').hide();
                        var check_validate = validateData();
                        if(check_validate == true){
                            var active = $('#template-active').is(":checked");
                            var name = $('#name_template').val();
                            name = name.replace("<script>", "<scri pt>");
                            name = name.replace("</script>", "</scri pt>");
                            var content = $('#bxed_idLHE_JOB_ORDER_TEMPLATE').val();
                            content = content.replace("<script>", "<scri pt>");
                            content = content.replace("</script>", "</scri pt>");
                            var temp_cate_id = parseInt('0' + $('#teamplate_cate').val());
                            BX.ajax({
                                url: _url_path + _id_edit,
                                data: {
                                    SAVE_TEMPLATE: true,
                                    NAME_TEMPLATE: name,
                                    ACTIVE: active == true ? 1 : 0,
                                    CONTENT_TEMPLATE: content,
                                    ID_TEMPLATE: _id_edit,
                                    CATEGORY: temp_cate_id
                                },
                                method: 'POST',
                                dataType: 'json',
                                timeout: 30,
                                async: true,
                                processData: true,
                                scriptsRunFirst: true,
                                emulateOnload: true,
                                start: true,
                                cache: false,
                                onsuccess: function(result){
                                    if(result.DATA !== undefined){
                                        if(_id_edit == 0){
                                            JSCOTData.push(result.DATA);
                                            $('#template_box').append($('<a>', {
                                                href: 'javascript:void(0)',
                                                id: 'template_tab_' + result.DATA.ID,
                                                'class': 'status_tab',
                                                title: result.DATA.NAME_TEMPLATE,
                                                'onclick': 'selectTemplateTab(' + result.DATA.ID + ')',
                                                title: BX.util.htmlspecialchars(result.DATA.NAME_TEMPLATE)
                                            }).append($('<span>').html(BX.util.htmlspecialchars(result.DATA.NAME_TEMPLATE)))
                                                .append($('<div>', {'class': 'ynsir-active-template'})));
                                        }
                                        else {
                                            for(var key in JSCOTData){
                                                if(JSCOTData[key].ID == result.DATA.ID){
                                                    JSCOTData[key] = result.DATA;
                                                    $('#template_tab_' + result.DATA.ID + ' span').text(result.DATA.NAME_TEMPLATE);
                                                    $('#template_tab_' + result.DATA.ID + ' span').attr('title', result.DATA.NAME_TEMPLATE);
                                                    break;
                                                }
                                            }
                                        }
                                        if(active == 1)
                                            $('#template_tab_' + result.DATA.ID + ' .ynsir-active-template').removeClass('ynsir-inactive-template');
                                        else
                                            $('#template_tab_' + result.DATA.ID + ' .ynsir-active-template').addClass('ynsir-inactive-template');
                                        selectTemplateTab(result.DATA.ID);
                                        popupform.close();
                                    }
                                    else {
                                        $('#name-error-message').text(result.ERROR).show();
                                    }
                                },
                                onfailure: function(){
                                    alert('Error!!!');
                                    popupform.close();
                                }
                            });
                        }
                    }
                }
            }),
            new BX.PopupWindowButton({
                text: JSCOTMess.YNSIR_COT_T_CANCEL_BTN,
                className: "popup-window-button-link",
                events: {
                    click: function () {
                        popupform.close();
                    }
                }
            })
        ]
    });
    selectTemplateTab(_selected_default);
    $("#popup_template").css("height", "auto");

    /* confirm */
    popupConfirm = new BX.PopupWindow("schema", null, {
        content: BX('popup_confirm'),
        zIndex: 100,
        offsetLeft: 0,
        offsetTop: 0,
        draggable: {restrict: true},
        overlay: true,
        titleBar: {content: BX.create("span", {html: 'Delete template'})},
        closeIcon: {right: "12px", top: "12px"},
        buttons: [
            new BX.PopupWindowButton({
                text: JSCOTMess.YNSIR_COT_T_DELETE_BTN,
                className: "",
                id: "delete_confirm",
                events: {
                    click: function () {
                        BX.ajax({
                            url: _url_path + _id_edit,
                            data: {
                                DELETE_TEMPLATE: true,
                                ID_TEMPLATE: _id_selected,
                            },
                            method: 'POST',
                            dataType: 'json',
                            onsuccess: function(result){
                                if(result.SUCCESS == 1){
                                    $('#template-content').html('');
                                    $('#template-content').text('');
                                    var new_select = 0;
                                    var count_index = 0;
                                    for(var key in JSCOTData){
                                        if(new_select == 0)
                                            new_select = JSCOTData[key].ID;
                                        if(JSCOTData[key].ID == _id_selected){
                                            $('#template_tab_' + _id_selected).remove();
                                            new_select = new_select == _id_selected ? 0 : new_select;
                                            JSCOTData.splice(count_index, 1);
                                        }
                                        count_index++;
                                    }
                                    if(new_select == 0 && JSCOTData.length > 0) {
                                        new_select = JSCOTData[0].ID;
                                    }
                                    selectTemplateTab(new_select);
                                }
                                else {
                                    alert(result.ERROR);
                                }
                                popupConfirm.close();
                            },
                            onfailure: function(){
                                alert('Error!!!');
                                popupConfirm.close();
                            }
                        });
                    }
                }
            }),
            new BX.PopupWindowButton({
                text: JSCOTMess.YNSIR_COT_T_CANCEL_BTN,
                className: "popup-window-button-link",
                events: {
                    click: function () {
                        popupConfirm.close();
                    }
                }
            })
        ]
    });
    $("#popup_confirm").css("height", "auto");

    /* confirm */
    popupCate = new BX.PopupWindow("schema", null, {
        content: BX('cate_template'),
        zIndex: 100,
        offsetLeft: 0,
        offsetTop: 0,
        draggable: {restrict: true},
        overlay: true,
        titleBar: {content: BX.create("span", {html: 'Delete template'})},
        closeIcon: {right: "12px", top: "12px"},
        buttons: [
            new BX.PopupWindowButton({
                text: JSCOTMess.YNSIR_COT_T_CATEGORY_TITLE,
                className: "",
                id: "list_category",
                events: {
                    click: function () {
                        window.location = _link_category;
                    }
                }
            }),
            new BX.PopupWindowButton({
                text: JSCOTMess.YNSIR_COT_T_CATEGORY_BTN_CLOSE,
                className: "popup-window-button-link",
                events: {
                    click: function () {
                        popupCate.close();
                        //location.reload();
                    }
                }
            })
        ]
    });
    var _id_template = window.location.hash;
    _id_template = _id_template.replace('#', '');
    $('#template_tab_' + _id_template).click();
});
