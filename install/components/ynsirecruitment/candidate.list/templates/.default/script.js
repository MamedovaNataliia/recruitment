BX.Bitrix24.PageSlider.bindAnchors({
    rules: [
        {
            condition: [
                '/recruitment/candidate/detail/(\\d+)/'
            ]
        }
    ]
});

function quikview(id) {

    $("#ynsir_show_candidate_a").attr('href', "/recruitment/candidate/detail/" + id + "/?QUIK_VIEW=Y");
    document.getElementById("ynsir_show_candidate_a").click();
}

ListActions = (function () {
    var ListActions = function (parameters) {
        this.listAction = parameters.listAction;
        this.init();
    };

    ListActions.prototype.init = function () {
        this.actionButton = BX('lists-title-action');
        this.actionPopupItems = [];
        this.actionPopupObject = null;
        this.actionPopupId = 'lists-title-action';
        BX.bind(this.actionButton, 'click', BX.delegate(this.showListAction, this));
    };

    ListActions.prototype.showListAction = function () {
        if (!this.actionPopupItems.length) {
            for (var k = 0; k < this.listAction.length; k++) {
                this.actionPopupItems.push({
                    text: this.listAction[k].text,
                    onclick: this.listAction[k].action,
                    className: (this.listAction[k].className ? this.listAction[k].className : ""),
                });
            }
        }
        if (!BX.PopupMenu.getMenuById(this.actionPopupId)) {
            var buttonRect = this.actionButton.getBoundingClientRect();
            this.actionPopupObject = BX.PopupMenu.create(
                this.actionPopupId,
                this.actionButton,
                this.actionPopupItems,
                {
                    closeByEsc: true,
                    angle: true,
                    offsetLeft: buttonRect.width / 2,
                    events: {
                        onPopupShow: BX.proxy(function () {
                            BX.addClass(this.actionButton, 'webform-button-active');
                        }, this),
                        onPopupClose: BX.proxy(function () {
                            BX.removeClass(this.actionButton, 'webform-button-active');
                        }, this)
                    }
                }
            );
        }
        if (this.actionPopupObject) this.actionPopupObject.popupWindow.show();
    };
    return ListActions;
})();

function convertcv(item, result) {
    $('#file-selectdialog-RESUME_FILE .file-extended').show();

    $('#files-resume-list .file-placeholder-tbody').append(
        '<tr id="wd-doc' + item.fileId + '"><td class="files-name">' +
        '                        <span class="files-text"><span class="wd-files-icon feed-file-icon-'+item.ext+'"></span>' +
        '                            <span class="f-wrap" data-role="name">' + item.name + '</span>' +
        '                        </span>' +
        '                        </td>' +
        '                        <td class="files-size">' + item.size + '</td><td class="files-storage">' +
        '<span class="cv-profile-user-item-del" onclick="$(this).parent().parent().remove()"></span>' +
        '                            <div class="files-storage-block">' +
        '                                <span class="files-placement">&nbsp;</span>' +
        '                            </div>' +
        '                        </td>' +
        '<input id="file-doc' + item.fileId + '" type="hidden" name="RESUME_ID[]" value="' + item.fileId + '">' +
        '                     </tr>'
    );
}

function convertexcel(item, result) {
    $('.containt-file-excel').remove();
    $('#file-selectdialog-EXCEL_FILE .file-extended').show();
    $('#files-excel-list .file-placeholder-tbody').append(
        '<tr class="containt-file-excel" id="wd-doc' + item.fileId + '"><td class="files-name"><span class="wd-files-icon feed-file-icon-excel"></span>' +
        '                        <span class="files-text">' +
        '                            <span class="f-wrap" data-role="name">' + item.name + '</span>' +
        '                        </span>' +
        '                        </td>' +
        '                        <td class="files-size">' + item.size + '</td><td class="files-storage">' +
        '<span class="excel-profile-user-item-del" onclick="$(this).parent().parent().remove()"></span>' +
        '                            <div class="files-storage-block">' +
        '                                <span class="files-placement">&nbsp;</span>' +
        '                                <span class="del-but"></span>' +
        '                            </div>' +
        '                        </td>' +
        '<input id="file-doc' + item.fileId + '" type="hidden" name="RESUME_EXCEL_ID" value="' + item.fileId + '">'+        
        '                    </tr>' 
    );
}