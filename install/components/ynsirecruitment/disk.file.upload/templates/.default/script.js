BX.ready(function () {
    if (BX.DiskUpload !== undefined) {
        BX.DiskUpload.prototype.onUploadDone = function (item, result) {
            if (item.__progressBar)
                item.__progressBar.style.width = '100%';
            this.counter.uploading = BX.util.deleteFromArray(this.counter.uploading, BX.util.array_search(item.id, this.counter.uploading));
            this.counter.uploaded.push(item.id);
            item.fileId = result["file"]["fileId"];
            var node = this.agent.getItem(item.id).node, file = this._camelToSNAKE(result["file"]);
            if (BX(node)) {
                var html = this.templates.done.text;
                for (var ii in file) {
                    if (file.hasOwnProperty(ii)) {
                        html = html.replace(new RegExp("#" + ii.toLowerCase() + "#", "gi"), file[ii]).replace(new RegExp("#" + ii.toUpperCase() + "#", "gi"), file[ii]);
                    }
                }
                var TR;
                if (BX.browser.IsIE8()) {
                    TR = node;
                    while (TR.cells.length > 0) {
                        TR.deleteCell(0);
                    }
                    var cellIndex = 0;
                    html.replace(/<\/td>/gi, "\002").replace(/<td([^>]+)>([^\002]+)\002/gi, function (str, attrs, innerH) {
                        var TD = TR.insertCell(cellIndex);
                        TD.innerHTML = innerH;
                        attrs.replace(/class=["']([a-z\-\s]+)['"]/, function (str, className) {
                            TD.className = className;
                        });
                        cellIndex++;
                        return '';
                    });
                }
                else {
                    TR = BX.create('TR', {attrs: this.templates.done.attrs, html: html});
                    TR.setAttribute("id", node.getAttribute("id"));
                    node.parentNode.replaceChild(TR, node);
                }
            }
            else {
                this.onUploadError(item, result);
            }
            this.__bindEventsToNode(node, item);
            this.__checkButton();

            // update
            var id_attached = item.caller.fileInput.id;
            id_attached = id_attached.replace("inputContainerFolderList", "");
            if (YNSIRDFUJSData[id_attached] !== undefined) {
                window[YNSIRDFUJSData[id_attached]._onUploadDone](item, result);
            }
            // end update
        },

            BX.DiskUpload.prototype.onUploadWindowClose = function () {
                if (this.popup && this.counter.uploading.length <= 0) {
                    this.popup.close();
                }
            },

            BX.DiskUpload.prototype.onFileIsInited = function (id, file) {
                // update
                var id_attached = file.caller.fileInput.id;
                id_attached = id_attached.replace("inputContainerFolderList", "");
                if (YNSIRDFUJSData[id_attached] !== undefined) {
                    // lichtv
                    // lichtv - multiple="multiple"
                    if(YNSIRDFUJSData[id_attached]._multiple_file == 0){
                        $('#FolderList'+id_attached+'PlaceHolder').find('tr').remove();
                        if(YNSIRDFUJSData[id_attached]._count_file == 1){
                            YNSIRDFUJSData[id_attached]._count_file = 0;
                            return;
                        }
                        else{
                            YNSIRDFUJSData[id_attached]._count_file = 1;
                        }
                    }

                    var _allow_upload_ext = YNSIRDFUJSData[id_attached]._allow_upload_ext;
                    if (_allow_upload_ext.indexOf(file.ext) < 0) {
                        return;
                    }
                    // gianglh
                    file.name = '[' + _user_id + ']' + file.name;
                    var isExist = false;
                    $('.file-uploaded-candidate').each(function () {
                        var nameFile = $(this).attr('file-name');
                        if (nameFile == file.name) {
                            isExist = true;
                        }
                    });
                    if (isExist)
                        return;
                }
                // end update

                this.counter.all.push(id);

                BX.addCustomEvent(file, 'onFileIsAppended', this._onFileIsAppended);
                BX.addCustomEvent(file, 'onUploadStart', this._onUploadStart);
                BX.addCustomEvent(file, 'onUploadProgress', this._onUploadProgress);
                BX.addCustomEvent(file, 'onUploadDone', this._onUploadDone);
                BX.addCustomEvent(file, 'onUploadError', this._onUploadError);

                BX.addCustomEvent(file, 'onFileIsAppended', resetDataForReplace(id_attached, 1));

                this.__checkButton();
                if (this.bp && this.bpParameters && this.popup) {
                    this.onStartBizproc();
                }
            }
    }
    ;
});

// remove upload multiple file
$(document).ready(function(){
    for(var keyIdElement in YNSIRDFUJSData){
        if (YNSIRDFUJSData.hasOwnProperty(keyIdElement)) {
            if(YNSIRDFUJSData[keyIdElement]._multiple_file == 0){
                $('#inputContainerFolderList' + keyIdElement).removeAttr('multiple');
                $('#inputContainerFolderList' + keyIdElement).parents('label').attr('onclick', 'resetDataForReplace("'+keyIdElement+'")');
            }
            $('#inputContainerFolderList' + keyIdElement).attr('accept', '.' + (YNSIRDFUJSData[keyIdElement]._allow_upload_ext).join(",."));
        }
    }
});

function resetDataForReplace(id_element, bSetTitle){
    if(YNSIRDFUJSData[id_element]._multiple_file == 0){
        YNSIRDFUJSData[id_element]._count_file = 0;
        if(bSetTitle == 1){
            $('#FolderList'+id_element+'PlaceHolder').parents('.bx-disk-popup-container')
                .find('.bx-disk-popup-buttons label').first().text(YNSIRDFUJSData[id_element]._text_replace);
        }
    }
}