// ========== General configuration ======================================================
var obj_participant_user;
var _url_path_template = '/bitrix/components/ynsirecruitment/job_order.edit/ajax.php?get_template_content=1&id=';
var _url_path_save_template = '/bitrix/components/ynsirecruitment/config.order.template/ajax.php?id=0';
var _type_select_user;
var _jo_action = 1;
// ========== Interview round ============================================================

function deleteRound(e) {
    $(e).parents('.interview-round').remove();
    resetRoundName();
}

function addRound() {
    var timestamp_now = new Date().getTime();
    $('#interview-round').append($('<div>', {
            'class': 'interview-round',
            'role-name-participant': 'INTERVIEW_PARTICIPANT[' + timestamp_now + '][]'
        })
            .append($('<div>', {'class': 'interview-round-lable'}))
            .append($('<div>', {'class': 'interview-round-content'})
                .append($('<div>', {'class': 'interview-round-participant-text'})
                    .append($('<span>').text(JSJOEMess.YNSIR_CJOE_PARTICIPANTS + ': ')))
                .append($('<div>', {'class': 'interview-round-participant-data'})
                    .append($('<div>', {'class': 'participant-profile-info'}))
                    .append($('<div>', {'class': 'add-user-participant'})
                        .append($('<a>', {
                                'href': 'javascript:void(1);',
                                'onclick': 'showUserPopup(this, \'PARTICIPANT\')'
                            }).text('+ ' + JSJOEMess.YNSIR_CJOE_BTN_ADD)
                        )
                    )
                )
                .append($('<div>', {'class': 'interview-round-note-text'})
                    .append($('<span>').text(JSJOEMess.YNSIR_CJOE_NOTE + ': ')))
                .append($('<div>', {'class': 'interview-round-note-data'})
                    .append($('<textarea>', {'name': 'INTERVIEW_NOTE[' + timestamp_now + ']'})))
                .append($('<div>', {'class': 'interview-round-delete', 'onclick': 'deleteRound(this)'}))
            )
    );
    resetRoundName();
}

function resetRoundName(){
    $('#interview-round .interview-round').each(function(index){
        $(this).find('.interview-round-lable').text(JSJOEMess.YNSIR_CJOE_ROUND + ' ' + (index+1) + ':');
    });
}

function deleteParticipant(e){
    $(e).parents('.ynsir-list-summary-wrapper').remove();
}

function showUserPopup(e, type){
    _type_select_user = type;
    var position = $(e).offset();
    if(type == 'PARTICIPANT'){
        position = $(e).position();
        obj_participant_user = $(e).parents('.interview-round-participant-data').find('.participant-profile-info').first();
    }
    $("#job-order-select-user").css({top: position.top + 25, left: position.left, position: 'absolute'});
    $('#job-order-select-user').show();
}

function onSelectedUser(data){
    switch (_type_select_user){
        case 'SUBORDANATE':
            //$('#job_order_subordinate')
            var class_input = 'subordinate-user-' + data.id;
            var exited_subordinate = $('#job_order_subordinate').find('.' + class_input).length;
            if(exited_subordinate == 0){
                var timestamp_now = new Date().getTime();
                data.name = (data.name).substring(0, 30);
                var id_tooltip = 'job_order_subordinate_' + timestamp_now + '_' + data.id;
                var objAddNew = $('<div>', {'class': 'ynsir-list-summary-wrapper'})
                    .append($('<div>', {'class': 'ynsir-list-photo-wrapper'})
                        .append($('<div>', {'class': 'ynsir-list-def-pic'})
                            .append($('<img>', {
                                'alt': 'Author Photo',
                                'src': data.photo
                            }))
                        )
                    )
                    .append($('<div>', {'class': 'ynsir-list-info-wrapper'})
                        .append($('<div>', {'class': 'ynsir-list-title-wrapper'})
                            .append($('<a>', {
                                'href': '/company/personal/user/' + data.id + '/',
                                'id': id_tooltip
                            }).text(BX.util.htmlspecialchars(data.name)))
                        )
                    )
                    .append($('<div>', {'class': 'ynsir-list-info-wrapper'})
                        .append($('<div>', {
                            'class': 'interview-round-delete user-delete',
                            'onclick': 'deleteParticipant(this)'
                        })))
                    .append($('<input>', {
                            'class': class_input,
                            'name': 'SUBORDINATE[]',
                            'value': data.id
                        }).hide()
                    );
                $('#job_order_subordinate').append(objAddNew);
                BX.tooltip(data.id, id_tooltip, "");
            }
            break;
        default:
            var class_input = 'participant-user-' + data.id;
            var exited_participant = $(obj_participant_user).find('.' + class_input).length;
            if(exited_participant == 0){
                var name_input_data = $(obj_participant_user).parents('.interview-round').attr('role-name-participant');
                var timestamp_now = new Date().getTime();
                var id_tooltip = 'job_order_participant_' + timestamp_now + '_' + data.id;
                data.name = (data.name).substring(0, 30);
                var objAddNew = $('<div>', {'class': 'ynsir-list-summary-wrapper'})
                // image
                    .append($('<div>', {'class': 'ynsir-list-photo-wrapper'})
                        .append($('<div>', {'class': 'ynsir-list-def-pic'})
                            .append($('<img>', {
                                    'class': 'ynsir-list-def-pic',
                                    'alt': 'Author Photo',
                                    'src': data.photo,
                                })
                            )
                        )
                    )
                    // name, tooltip
                    .append($('<div>', {'class': 'ynsir-list-info-wrapper'})
                        .append($('<div>', {'class': 'ynsir-list-title-wrapper'})
                            .append($('<a>', {
                                    'href': '/company/personal/user/' + data.id + '/',
                                    'id': id_tooltip,
                                    'target': '_blank'
                                }).text(BX.util.htmlspecialchars(data.name))
                            )
                        )
                    )
                    // button delete
                    .append($('<div>', {'class': 'ynsir-list-info-wrapper'})
                        .append($('<div>', {
                            'class': 'interview-round-delete user-delete',
                            'onclick': 'deleteParticipant(this)'})
                        )
                    )
                    .append($('<input>', {
                        'class': class_input,
                        'name': name_input_data,
                        'value': data.id,
                    }).hide());
                $(obj_participant_user).append(objAddNew);
                BX.tooltip(data.id, id_tooltip, "");
            }
            break;
    }
    $('#job-order-select-user').hide();
}

// ========= hide popup when click out of user popup
$(document).click(function (event) {
    if (!$(event.target).is("#job-order-select-user, #job-order-select-user *, .add-user-participant a, .add-user-subordinates a")) {
        $('#job-order-select-user').hide();
    }
});

// ========== Description : template =====================================================

function changeTemplate() {
    var id_template = parseInt($('#ynsir_jo_TEMPLATE_ID').val());
    if (id_template >= 0)
        BX.ajax.insertToNode(_url_path_template + id_template, BX('ynsir_jo_DESCRIPTION'));
}

function saveTemplate() {
    $('#error-template-save').text('');
    $('#name_template').val('');
    popupform.setTitleBar(JSJOEMess.YNSIR_CJOE_TEMPLATE_SAVE_TITLE);
    popupform.show();
}

function validateData() {
    $bResult = true;
    $('#error-template-save').html('');
    try {
        var name = $('#name_template').val();
        var _str_alert = '';
        name = name.trim();
        if (name.length < 10) {
            $bResult = false;
            $('#error-template-save').append($('<p>').text(JSJOEMess.YNSIR_CJOE_T_NAME_VALIDATE));
        }
        var content = $('#bxed_idLHE_DESCRIPTION').val();
        content = content.trim();
        if (content.length < 100) {
            $bResult = false;
            $('#error-template-save').append($('<p>').text(JSJOEMess.YNSIR_CJOE_T_CONTENT_VALIDATE));
        }
    }
    catch (e) {
        location.reload();
    }
    return $bResult;
}

function changeTCategory(event) {
    $('#ynsir_jo_TEMPLATE_ID').val(0);
    var template_cate_id = parseInt($(event).val());
    $('#ynsir_jo_TEMPLATE_ID').find('option').each(function(){
        if(parseInt($(this).attr('value')) != 0)
            $(this).remove();
    });
    for(const key in JSJOTemplate) {
        if(parseInt(JSJOTemplate[key].CATEGORY) == template_cate_id) {
            $('#ynsir_jo_TEMPLATE_ID').append($('<option>', {value: JSJOTemplate[key].ID}).text(JSJOTemplate[key].NAME_TEMPLATE));
        }
    }
}

// ========== Submit form ================================================================

function submitJobOrderForm(form) {
    prepareSubmit();
    $('.recruitment-jo-item-error-label').hide();
    if(_jo_action == 1){
        $('#submit-save').addClass('bp-button-wait').parent().addClass('bp-button-wait');
    }
    else {
        $('#submit-save-and-new').addClass('bp-button-wait').parent().addClass('bp-button-wait');
    }
    var _data_form = $(form).serialize();
    _data_form = _data_form + '&AJAX=Y';
    BX.ajax.post($(form).attr('action'), _data_form, function (result) {
        try {
            var objResult = JSON.parse(result);
            if(objResult.ERROR !== undefined){
                var is_scroll = false;
                for (var key in objResult.ERROR) {
                    if(!is_scroll) {
                        is_scroll = true;
                        $('html, body').animate({scrollTop:$('#ynsir_jo_' + key).offset().top - 10},'50');
                    }
                    $('#ynsir_jo_' + key).parent().find('.recruitment-jo-item-error-label').text(objResult.ERROR[key]).show();
                };
            }
            else {
                var url_redirect = '';
                url_redirect = JSJOEData.URL.DETAIL;
                url_redirect = url_redirect.replace('#id#', objResult.ID);
                /*if(_jo_action == 1){
                    url_redirect = JSJOEData.URL.DETAIL;
                    url_redirect = url_redirect.replace('#id#', objResult.ID);
                }
                else {
                    url_redirect = JSJOEData.URL.EDIT;
                    url_redirect = url_redirect.replace('#id#', 0);
                }*/
                window.location = url_redirect;
            }
            $('#submit-save').removeClass('bp-button-wait').parent().removeClass('bp-button-wait');
            $('#submit-save-and-new').removeClass('bp-button-wait').parent().removeClass('bp-button-wait');
        }
        catch (e){
            location.reload();
        }
    });
    return false;
}

function prepareSubmit(){
    // basic field
    for(var index in JSJOEData.PREPARE){
        let jo_prepare = $('#ynsir_jo_' + JSJOEData.PREPARE[index]).val();
        jo_prepare = jo_prepare.replace(/<(\s)*script(\s)*>/g, "<scr ipt>");
        jo_prepare = jo_prepare.replace(/<\/(\s)*script(\s)*>/g, "</scr ipt>");
        $('#ynsir_jo_' + JSJOEData.PREPARE[index]).val(jo_prepare);
    }
    // special case : note of interview
    $('#interview-round .interview-round-content .interview-round-note-data textarea').each(function(){
        let jo_prepare = $(this).val();
        jo_prepare = jo_prepare.replace(/<(\s)*script(\s)*>/g, "<scr ipt>");
        jo_prepare = jo_prepare.replace(/<\/(\s)*script(\s)*>/g, "</scr ipt>");
        $(this).val(jo_prepare);
    });
}

function setJOAction(type = 1){
    _jo_action = parseInt(type);
    if(_jo_action == 1){
        $('#io_action').val('SAVE');
    }
    else {
        $('#io_action').val('SUBMIT');
    }
}

// ========== Salary format ==============================================================

function addSpacesEvent(event, limit) {
    var inputNum = $(event).val();
    inputNum = inputNum.replace(/([^0-9\s]+)/g, '').trim().split(" ").join("");
    if(inputNum.length > limit) {
        inputNum = inputNum.substr(0, limit);
    }
    if (inputNum.length > 0) {
        var remainder = inputNum.length % 3;
        inputNum = (inputNum.substr(0, remainder) + inputNum.substr(remainder).replace(/(\d{3})/g, ' $1')).trim();
    }
    $(event).val(inputNum);
}

function numberInput(event, limit) {
    var inputNum = $(event).val();
    inputNum = inputNum.replace(/([^0-9\s]+)/g, '').trim().split(" ").join("");
    if(inputNum.length > limit) {
        inputNum = inputNum.substr(0, limit);
    }
    $(event).val(inputNum);
}

// ========== Date field =================================================================

BX.YSIRDateLinkField = function () {
    this._dataElem = null;
    this._viewElem = null;
    this._settings = {};
};
BX.YSIRDateLinkField.prototype = {
    initialize: function (dataElem, viewElem, settings) {
        if (!BX.type.isElementNode(dataElem)) {
            throw "BX.YSIRDateLinkField: 'dataElem' is not defined!";
        }
        this._dataElem = dataElem;
        this._viewElem = viewElem;
        BX.bind(viewElem, 'click', BX.delegate(this._onViewClick, this));
        BX.bind(dataElem, 'click', BX.delegate(this._onViewClick, this));
        this._settings = settings ? settings : {};
    },
    getSetting: function (name, defaultval) {
        return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
    },
    _onViewClick: function (e) {
        BX.calendar({
            value: this.getSetting('defaultTime', ''),
            node: (this._dataElem ? this._dataElem : this._viewElem),
            field: this._dataElem,
            bTime: this.getSetting('showTime', true),
            bSetFocus: this.getSetting('setFocusOnShow', true),
            callback: BX.delegate(this._onCalendarSaveValue, this)
        });
    },
    _onCalendarSaveValue: function (value) {
        var s = BX.calendar.ValueToString(value, this.getSetting('showTime', true), false);
        this._dataElem.value = s;
    }
};
BX.YSIRDateLinkField.create = function (dataElem, viewElem, settings) {
    var self = new BX.YSIRDateLinkField();
    self.initialize(dataElem, viewElem, settings);
    return self;
};

// ========== Ready ======================================================================

BX.ready(function () {
    // popup template
    popupform = new BX.PopupWindow("schema", null, {
        content: BX('popup_template'),
        zIndex: 100,
        offsetLeft: 0,
        offsetTop: 0,
        draggable: {restrict: true},
        overlay: true,
        titleBar: {content: BX.create("span", {html: 'Edit Job Description Template'})},
        closeIcon: {right: "12px", top: "12px"},
        buttons: [
            new BX.PopupWindowButton({
                text: JSJOEMess.YNSIR_CJOE_SAVE_BUTTON,
                className: "",
                id: "add_list_key",
                events: {
                    click: function () {
                        var check_validate = validateData();
                        if (check_validate == true) {
                            var name = $('#name_template').val();
                            name = name.replace("<script>", "<scri pt>");
                            name = name.replace("</script>", "</scri pt>");
                            var content = $('#bxed_idLHE_DESCRIPTION').val();
                            content = content.replace("<script>", "<scri pt>");
                            content = content.replace("</script>", "</scri pt>");
                            BX.ajax({
                                url: _url_path_save_template + 0,
                                data: {
                                    SAVE_TEMPLATE: true,
                                    NAME_TEMPLATE: name,
                                    ACTIVE: 0,
                                    CONTENT_TEMPLATE: content,
                                    ID_TEMPLATE: 0,
                                    CATEGORY: parseInt($('#category_template').val()),
                                    SAVE_FROM_JOB_ORDER: 1,
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
                                onsuccess: function (result) {
                                    if (result.DATA !== undefined) {
                                        $('#ynsir_jo_TEMPLATE_ID').append($('<option>', {
                                            value: result.DATA.ID,
                                            'cate-id': parseInt($('#category_template').val()),
                                        }).html(BX.util.htmlspecialchars(result.DATA.NAME_TEMPLATE)));
                                        $('#ynsir_jo_TEMPLATE_ID').val(result.DATA.ID);
                                        popupform.close();
                                    }
                                    else {
                                        $('#error-template-save').append($('<p>').text(result.ERROR));
                                    }
                                },
                                onfailure: function () {
                                    alert('Error!!!');
                                    popupform.close();
                                }
                            });
                        }
                    }
                }
            }),
            new BX.PopupWindowButton({
                text: JSJOEMess.YNSIR_CJOE_CANCEL_BUTTON,
                className: "popup-window-button-link",
                events: {
                    click: function () {
                        popupform.close();
                    }
                }
            })
        ]
    });
});
// =======================================================================================
function setDefalutExtentionData(_id){
    //$('#extends-data-item-' + $('#' + _id).val()).show();
}

function onchangeExtendsData(event){
    $('.extends-data-item').hide();
    //$('#extends-data-item-' + $(event).val()).show();
}

function validateExtendsFieldData(type) {
    switch (type) {
        case 'number':
            break;
        case 'date':
            break;
        case 'user':
            break;
        default :
            break;
    }
}

BX.ready(function() {
    BX.addCustomEvent(window, 'OnCreateIframeAfter', function(editor){
        $('#ynsir_jo_TITLE').focus();
    });
});

$(document).ready(function () {
    var arNewPlaceIssue = [];
    var arOldPlaceIssue = [];

    $("#ynsir_jo_DEPARTMENT").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#category_template").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsir_jo_TEMPLATE_ID").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsir_jo_STATUS").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsir_jo_LEVEL").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });

    function formatState(state) {
        if (!state.id) {
            return state.text;
        }
        var $state = $(
            '<span>' + state.text + '</span>'
        );
        return $state;
    };

    function formatRepoSelection(state) {
        return state.text;
    }
});