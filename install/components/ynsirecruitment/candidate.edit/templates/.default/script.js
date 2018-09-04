var _caction = 1;

function submitCandidateForm(form) {
    removeFileDelete();
    $("#show_error").hide();
    $('.ynsirc-input-data-error').removeClass('ynsirc-input-data-error');
    $('.recruitment-candidate-item-error-label span').html('');
    $('.recruitment-candidate-item-error-label span').hide();
    $("input[name^='SAVE']").css("pointer-events", "none");
    $("input[name^='SAVE_AND_NEW']").css("pointer-events", "none");
    $("input[name^='CANCEL']").css("pointer-events", "none");
    BX.ajax.post($(form).attr('action'), $(form).serialize(), function (result) {
        result = $.parseJSON(result);
        if (result.ERROR !== undefined) {
            if (result.ERROR == 'N') {
                if (_caction == 1)
                    window.location.href = "/recruitment/candidate/detail/" + result.CANDIDATE_ID + "/";
                else
                    window.location.href = "/recruitment/candidate/edit/0/";
            }
            else {
                $("input[name^='SAVE']").css("pointer-events", "auto");
                $("input[name^='SAVE_AND_NEW']").css("pointer-events", "auto");
                $("input[name^='CANCEL']").css("pointer-events", "auto");
                var count = 1;
                for (var key in result.MESS) {
                    let lKey = key.toLowerCase();
                    let err = result.MESS[key]['ERROR'];
                    let candidate_id = result.MESS[key]['CANDIDATE_ID'];
                    if (candidate_id > 0 && (key == 'EMAIL' || key == 'CMOBILE')) {
                        $("#show_error").show();
                        $("#ynsirc_duplicate").attr("href", '/recruitment/candidate/detail/' + candidate_id + '/');


                    } else {
                        var error_span = '<p>' + err + '</p>';
                        $('#ynsirc_' + lKey).parent().find('.error').show().find('span').first().show().append(error_span);
                        if (count == 1)
                            $('#ynsirc_' + lKey).focus();
                        $('#ynsirc_' + lKey).addClass('ynsirc-input-data-error');
                        $('#ynsirc_' + lKey).parents('td').first().find('div').addClass('ynsirc-input-data-error');
                    }
                    count++;
                }
            }
        }
        else {
            location.reload();
        }
    });
    return false;
}

function setCAction(type) {
    _caction = parseInt(type);
}

function removeFileDelete() {
    $('input[name^=FILE_RESUME_del]').each(function () {
        $(this).prev('input').remove();
        $(this).remove();
    });
    $('input[name^=FILE_FORMATTED_RESUME_del]').each(function () {
        $(this).prev('input').remove();
        $(this).remove();
    });
    $('input[name^=FILE_COVER_LETTER_del]').each(function () {
        $(this).prev('input').remove();
        $(this).remove();
    });
    $('input[name^=FILE_OTHERS_del]').each(function () {
        $(this).prev('input').remove();
        $(this).remove();
    });
}

function moreField(event, taget) {
    var morediv = $(event).parents('td').find('div').first().clone();
    morediv.find('input').first().val('');
    resName = morediv.find('input').first().attr('name').split("[");
    resId = morediv.find('input').first().attr('id').split("__");
    morediv.find('input').first().attr('name', resName[0] + '[' + Math.floor(new Date().getTime() / 1000) + ']');
    morediv.find('input').first().attr('id', resId[0] + '__' + Math.floor(new Date().getTime() / 1000));
    morediv.find('input').first().removeClass('ynsirc-input-data-error');
    morediv.find('.error').first().css('display', 'none');
    var del_span = $('<span>', {class: "hrm-profile-user-item-del hrm-profile-user-item-del-absolute"});
    morediv.find('input').first().after(del_span);
    del_span.click(function () {
        morediv.remove();
    });
    $(event).parent().before(morediv);
}

BX.YSIRDateLinkField = function () {
    this._dataElem = null;
    this._viewElem = null;
    this._settings = {};
};

BX.YSIRDateLinkField.prototype =
    {
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

function changeSelectList(event, key, typeLable) {
    var parrentDiv = $('#content_' + key);
    var item_type = $('option:selected', event).attr('item_type');
    var typeLable = $('option:selected', event).attr('lable_type');
    if (typeof item_type !== 'undefined') {
        $('#' + key + '_CONTENT_DATE').val('');
        $('#' + key + '_CONTENT_NUMBER').val('');
        $('#' + key + '_CONTENT_STRING').val('');
        switch (parseInt(item_type)) {
            case 1:
                parrentDiv.children('.content_' + key).hide();
                if (typeLable != null && typeLable.length > 0) {
                    $('#label_' + key + '_DATE').text(typeLable+':');
                } else {
                    $('#label_' + key + '_DATE').text('');
                }

                parrentDiv.children('#content_' + key + '_DATE').show();
                break;
            case 2:
                parrentDiv.children('.content_' + key).hide();
                if (typeLable != null && typeLable.length > 0) {
                    $('#label_' + key + '_NUMBER').text(typeLable + ':');
                } else {
                    $('#label_' + key + '_NUMBER').text('');
                }
                parrentDiv.children('#content_' + key + '_NUMBER').show();

                break;
            case 3:
                parrentDiv.children('.content_' + key).hide();
                if (typeLable != null && typeLable.length > 0) {
                    $('#label_' + key + '_STRING').text(typeLable + ':');
                } else {
                    $('#label_' + key + '_STRING').text('');
                }
                parrentDiv.children('#content_' + key + '_STRING').show();

                break;
            case 4:
                parrentDiv.children('.content_' + key).hide();
                if (typeLable != null && typeLable.length > 0) {
                    $('#label_' + key + '_USER').text(typeLable + ':');
                } else {
                    $('#label_' + key + '_USER').text('');
                }
                parrentDiv.children('#content_' + key + '_USER').show();

                break;
            default:
                parrentDiv.children('.content_' + key).hide();
                break;
        }
    } else {
        parrentDiv.children('.content_' + key).hide();
    }
}

function setFileResume(item, result) {
    var hash_id = item['id']+'Item';
    $('#file-selectdialog-file_resume .file-extended').show();
    var htmlview = '<div class="file-area"><input class="file-uploaded-candidate" file-name="'+item.name+'" id="file-doc' + item.fileId + '" type="hidden" name="FILE_RESUME[]" value="' + item.fileId + '">'+
    '<div id="bx-disk-filepage-file_resume' + item.fileId + '" class="bx-disk-filepage-file_resume">'+
    '<a href="#" data-bx-viewer="iframe" data-bx-title="'+item.name+'" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=' + item.fileId + '&amp;service=gvdrive&amp; bx-attach-file-id="' + item.fileId + '" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId='+item.fileId+'&amp;service=gdrive&amp;action='+item.fileId+'">'+
     item.name + '</a> </div>' +
        '<span hash_id="'+hash_id+'" class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"\n' +
        '                                                                  onclick="removeElement(this,\''+hash_id+'\')"\n' +
        '                                                            ></span></div>' +
    '<script type="text/javascript">' +
    'BX.viewElementBind(' +
    '"bx-disk-filepage-file_resume' + item.fileId + '",' +
    '{showTitle: true},' +
    '{attr: \'data-bx-viewer\'}' +
    ');' +
    '</script>';

    $('#file_resume-list .file-placeholder-tbody').append(
        htmlview
    );
}
function setFileFormmetedResume(item, result) {
    var hash_id = item['id']+'Item';
    $('#file-selectdialog-FORMATTED_RESUME .file-extended').show();
    var htmlview = '<div class="file-area"><input class="file-uploaded-candidate" file-name="'+item.name+'" id="file-doc' + item.fileId + '" type="hidden" name="FILE_FORMATTED_RESUME[]" value="' + item.fileId + '">'+
    '<div id="bx-disk-filepage-FORMATTED_RESUME' + item.fileId + '" class="bx-disk-filepage-FORMATTED_RESUME">'+
    '<a href="#" data-bx-viewer="iframe" data-bx-title="'+item.name+'" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=' + item.fileId + '&amp;service=gvdrive&amp; bx-attach-file-id="' + item.fileId + '" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId='+item.fileId+'&amp;service=gdrive&amp;action='+item.fileId+'">'+
     item.name + '</a> </div>' +
        '<span hash_id="'+hash_id+'" class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"\n' +
        '                                                                  onclick="removeElement(this,\''+hash_id+'\')"\n' +
        '                                                            ></span></div>' +
    '<script type="text/javascript">' +
    'BX.viewElementBind(' +
    '"bx-disk-filepage-FORMATTED_RESUME' + item.fileId + '",' +
    '{showTitle: true},' +
    '{attr: \'data-bx-viewer\'}' +
    ');' +
    '</script>';


    $('#FORMATTED_RESUME-list .file-placeholder-tbody').append(
        htmlview
    );
}

function setFileCoverLetter(item, result) {
    var hash_id = item['id']+'Item';
    $('#file-selectdialog-COVER_LETTER .file-extended').show();
    var htmlview = '<div class="file-area"><input class="file-uploaded-candidate" file-name="'+item.name+'" id="file-doc' + item.fileId + '" type="hidden" name="FILE_COVER_LETTER[]" value="' + item.fileId + '">'+
    '<div id="bx-disk-filepage-COVER_LETTER' + item.fileId + '" class="bx-disk-filepage-COVER_LETTER">'+
    '<a href="#" data-bx-viewer="iframe" data-bx-title="'+item.name+'" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=' + item.fileId + '&amp;service=gvdrive&amp; bx-attach-file-id="' + item.fileId + '" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId='+item.fileId+'&amp;service=gdrive&amp;action='+item.fileId+'">'+
     item.name + '</a> </div>' +
        '<span hash_id="'+hash_id+'" class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"\n' +
        '                                                                  onclick="removeElement(this,\''+hash_id+'\')"\n' +
        '                                                            ></span></div>' +
    '<script type="text/javascript">' +
    'BX.viewElementBind(' +
    '"bx-disk-filepage-COVER_LETTER' + item.fileId + '",' +
    '{showTitle: true},' +
    '{attr: \'data-bx-viewer\'}' +
    ');' +
    '</script>';
    $('#COVER_LETTER-list .file-placeholder-tbody').append(
        htmlview
    );
}


function setFileOthers(item, result) {
    var hash_id = item['id']+'Item';
    $('#file-selectdialog-OTHERS .file-extended').show();
    var htmlview = '<div class="file-area"><input class="file-uploaded-candidate" file-name="'+item.name+'" id="file-doc' + item.fileId + '" type="hidden" name="FILE_OTHERS[]" value="' + item.fileId + '">'+
    '<div id="bx-disk-filepage-OTHERS' + item.fileId + '" class="bx-disk-filepage-OTHERS">'+
    '<a href="#" data-bx-viewer="iframe" data-bx-title="'+item.name+'" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=' + item.fileId + '&amp;service=gvdrive&amp; bx-attach-file-id="' + item.fileId + '" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId='+item.fileId+'&amp;service=gdrive&amp;action='+item.fileId+'">'+
     item.name + '</a> </div>' +
        '<span hash_id="'+hash_id+'" class="hrm-profile-user-item-del hrm-profile-user-item-del-absolute"\n' +
        '                                                                  onclick="removeElement(this,\''+hash_id+'\')"\n' +
        '                                                            ></span></div>' +
    '<script type="text/javascript">' +
    'BX.viewElementBind(' +
    '"bx-disk-filepage-OTHERS' + item.fileId + '",' +
    '{showTitle: true},' +
    '{attr: \'data-bx-viewer\'}' +
    ');' +
    '</script>';
    $('#OTHERS-list .file-placeholder-tbody').append(
        htmlview
    );
}
function deleteFile(item,id){
    $(item).parent().remove();
    $("#file-id-"+id).remove();
}
function addSpacesEvent(event) {
    var val = $(event).val();
    $(event).val(addSpaces(val));
}
function addSpaces(inputNum) {
    inputNum = inputNum.replace(/([^0-9\s]+)/g, '').trim().split(" ").join("");
    if(inputNum.length >10) {
        inputNum = inputNum.substr(0, 10);
    }
    if (inputNum.length > 0) {
        var remainder = inputNum.length % 3;
        inputNum = (inputNum.substr(0, remainder) + inputNum.substr(remainder).replace(/(\d{3})/g, ' $1')).trim();
    }
    return inputNum;
}
$(document).ready(function () {
    var arNewPlaceIssue = [];
    var arOldPlaceIssue = [];

    $("#ynsirc_work_position").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsirc_education").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsirc_major").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsirc_current_former_issue_place").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsirc_english_proficiency").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsirc_source").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsirc_candidate_status").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsirc_highest_qualification_held").select2({
        templateResult: formatState,
        templateSelection: formatRepoSelection,
    });
    $("#ynsirc_marital_status").select2({
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