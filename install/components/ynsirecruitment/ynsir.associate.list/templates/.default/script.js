Associategrid = function(
    settings,
    containerId
)
{
    this.settings = null;
    this.containerId = '';
    this.container = null;
    this.Selectcontainer = null;

    this._round_container = null;
    this._round_Selectcontainer = null;
    this._messages = null;

    this.init(
        settings,
        containerId
    );
};
Associategrid.prototype = {
    init: function (settings, containerId) {
        this.settings = settings;
        this.containerId = containerId;
        this.container = BX(containerId+'-associate-dialog-content');
        this.Selectcontainer = BX(containerId+'-status-select');

        this._round_container = BX(containerId+'-associate-change-round-status-dialog-content');
        this._round_Selectcontainer = BX(containerId+'-status-round-select');

        this._messages = this.getSetting("messages", {});
    },
    getSetting: function (name, defaultval) {
        return typeof(this.settings[name]) != 'undefined' ? this.settings[name] : defaultval;
    },
    _handleDestroy: function(popup)
    {
        BX.Main.gridManager.reload(this.containerId);
    },
    getMessage: function (name) {
        return this._messages.hasOwnProperty(name) ? this._messages[name] : name;
    },
    getGridId: function()
    {
        return this.containerId;
    },
    getGrid: function()
    {
        var gridId = this.containerId;
        if(gridId === '')
        {
            return null;
        }

        var gridInfo = BX.Main.gridManager.getById(gridId);
        return (BX.type.isPlainObject(gridInfo) && gridInfo["instance"] !== "undefined" ? gridInfo["instance"] : null);
    },
    getGridJsObject: function()
    {
        var gridId = this.getSetting('gridId', '');
        return BX.type.isNotEmptyString(gridId) ? window['bxGrid_' + gridId] : null;
    },
    changeStatusSelected: function(associate_id,status_id,TYPE)
    {
        var grid = this.getGrid();
        if(TYPE == 'STATUS') {
            var data = {'ID': associate_id, 'STATUS_ID': status_id};
        } else {
            var data = {'ID': associate_id, 'STATUS_ROUND_ID': status_id};
        }
        data[grid.getActionKey()] = 'changeStatus';
        grid.reloadTable('POST', data);
    },
    ChangeStatusDialog: function (id_associate,id_selected,arOption ,type, then, cancel) {
        var app = this;
        var dialog, popupContainer, applyButton, cancelButton;
        var container,containerid, selectOjb;

        if(type == 'STATUS') {
            container = app.container;
            selectOjb = app.Selectcontainer;
        } else {
            container = app._round_container;
            selectOjb = app._round_Selectcontainer;
        }

        dialog = new BX.PopupWindow(
            this.containerId + '-associate-dialog',
            null,
            {
                content: container,
                titleBar: '',
                autoHide: false,
                zIndex: 9999,
                overlay: 0.4,
                offsetTop: -100,
                closeIcon: true,
                closeByEsc: true,
                events: {
                    onClose: function () {
                        BX.unbind(window, 'keydown', hotKey);
                    }
                },
                buttons: [
                    new BX.PopupWindowButton({
                        text: this.getMessage('ChangeStatusDialogButtonTitle'),
                        id: this.containerId + '-associate-dialog-apply-button',
                        events: {
                            click: function () {
                                BX.type.isFunction(then) ? then() : null;
                                app.changeStatusSelected(id_associate,BX(selectOjb).value,type);
                                this.popupWindow.close();
                                // this.popupWindow.destroy();
                                // BX.onCustomEvent(window, 'Grid::confirmDialogApply', [this]);
                                BX.unbind(window, 'keydown', hotKey);
                            }
                        }
                    }),
                    new BX.PopupWindowButtonLink({
                        text: this.getMessage('ChangeStatusDialogButtonCancel'),
                        id: this.containerId + '-associate-dialog-cancel-button',
                        events: {
                            click: function () {
                                BX.type.isFunction(cancel) ? cancel() : null;
                                this.popupWindow.close();
                                // this.popupWindow.destroy();
                                // BX.onCustomEvent(window, 'Grid::confirmDialogCancel', [this]);
                                BX.unbind(window, 'keydown', hotKey);
                            }
                        }
                    })
                ]
            }
        );

        if (!dialog.isShown()) {
            BX.selectUtils.deleteAllOptions(selectOjb)
            var n = arOption.length;
            for(var i=0; i<n; i++)
                BX.selectUtils.addNewOption(selectOjb, arOption[i].KEY, arOption[i].VALUE, true, true);
            BX.selectUtils.selectOption(selectOjb,id_selected);
            dialog.show();
            popupContainer = dialog.popupContainer;
            BX.removeClass(popupContainer, "main-grid-show-popup-animation");
            BX.addClass(popupContainer, "main-grid-show-popup-animation");
            applyButton = BX(this.containerId + '-associate-dialog-apply-button');
            cancelButton = BX(this.containerId + '-associate-dialog-cancel-button');

            BX.bind(window, 'keydown', hotKey);
        }


        function hotKey(event) {
            if (event.code === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                BX.fireEvent(applyButton, 'click');
            }

            if (event.code === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                BX.fireEvent(cancelButton, 'click');
            }
        }
    },
    insertToNode: function(url, node,callback) {
        node = BX(node);
        if (!!node) {
            var eventArgs = {cancel: false};
            BX.onCustomEvent('onAjaxInsertToNode', [{url: url, node: node, eventArgs: eventArgs}]);
            if (eventArgs.cancel === true) {
                return;
            }

            var show = null;
            show = BX.showWait(node);

            return BX.ajax.get(url, function (data) {
                node.innerHTML = data;
                callback();
                BX.closeWait(node, show);
            });
        }
    },
    showPopUpOnboardingProcess: function (id_associate) {
        var app = this;

        dialog = new BX.PopupWindow(
            this.containerId + '-onboarding-dialog',
            null,
            {
                content: "<span class='main-grid-more-icon'></span>",
                titleBar: '',
                autoHide: false,
                zIndex: 9999,
                height:50,
                width:400,
                overlay: 0.4,
                offsetTop: -100,
                closeIcon: false,
                closeByEsc: false,
                events:
                    {
                        onClose: function () {
                            BX.unbind(window, 'keydown', hotKey);
                        },
                        onPopUpClose:BX.delegate(app._handleDestroy, this)
                    },
                buttons: [
                    new BX.PopupWindowButton({
                        text: this.getMessage('startBpButtonTitle'),
                        id: this.containerId + '-onboarding-dialog-apply-button',
                        events: {
                            click: function () {
                                popup = this.popupWindow;
                                var form = $('#popup-window-content-'+app.containerId + '-onboarding-dialog form').first();
                                var data = form.serialize();
                                data = data + '&DoStartParamWorkflow=start';
                                BX.ajax.post(form.attr('action'), data, function (result) {
                                    result = $.parseJSON(result);
                                    if(result.SUCCESS == 'Y') {
                                        BX('popup-window-content-'+app.containerId + '-onboarding-dialog').innerHTML = result.MESSAGE;
                                        BX.onCustomEvent(window, 'Grid::confirmDialogApply', [this]);
                                        setTimeout(function(){
                                                popup.close();
                                                popup.destroy();},
                                            2000);

                                    } else {
                                        BX('popup-window-content-'+app.containerId + '-onboarding-dialog').innerHTML = result.MESSAGE;
                                    }
                                });

                                BX.unbind(window, 'keydown', hotKey);
                            }
                        }
                    }),
                    new BX.PopupWindowButtonLink({
                        text: this.getMessage('ChangeStatusDialogButtonCancel'),
                        id: this.containerId + '-onboarding-dialog-cancel-button',
                        events: {
                            click: function () {
                                popup = this.popupWindow;
                                var form = $('#popup-window-content-'+app.containerId + '-onboarding-dialog form').first();
                                var data = form.serialize();
                                data = data + '&CancelStartParamWorkflow=cancel';
                                BX.ajax.post(form.attr('action'), data, function (result) {
                                        popup.close();
                                        popup.destroy();
                                    }
                                );

                                this.popupWindow.close();
                                this.popupWindow.destroy();
                                BX.onCustomEvent(window, 'Grid::confirmDialogCancel', [this]);
                                BX.unbind(window, 'keydown', hotKey);
                            }
                        }
                    })
                ]
            }
        );

        if (!dialog.isShown()) {
            var url = this.getSetting('url_bp_onboarding')+'&RE_ENTITY_BIZ_ID='+id_associate ;
            BX.addClass(BX('popup-window-content-'+app.containerId + '-onboarding-dialog'),'associate-grid-add-btn pagetitle-container load');
            app.insertToNode(url,BX('popup-window-content-'+app.containerId + '-onboarding-dialog'),
                function(){
                    BX.removeClass(BX('popup-window-content-'+app.containerId + '-onboarding-dialog'),'associate-grid-add-btn pagetitle-container load');
                });
            dialog.show();
            popupContainer = dialog.popupContainer;
            BX.removeClass(popupContainer, "main-grid-show-popup-animation");
            BX.addClass(popupContainer, "main-grid-show-popup-animation");
            applyButton = BX(this.containerId + '-onboarding-dialog-apply-button');
            cancelButton = BX(this.containerId + '-onboarding-dialog-cancel-button');

            BX.bind(window, 'keydown', hotKey);
        }


        function hotKey(event) {
            if (event.code === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                BX.fireEvent(applyButton, 'click');
            }

            if (event.code === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                BX.fireEvent(cancelButton, 'click');
            }
        }
    },
    showPopUpScanCV: function (id_associate) {
        var app = this;

        dialog = new BX.PopupWindow(
            this.containerId + '-scancv-dialog',
            null,
            {
                content: "<span class='main-grid-more-icon'></span>",
                titleBar: '',
                autoHide: false,
                zIndex: 9999,
                overlay: 0.4,
                offsetTop: -100,
                closeIcon: false,
                closeByEsc: false,
                events:
                    {
                        onClose: function () {
                            BX.unbind(window, 'keydown', hotKey);
                        },
                        onPopUpClose:BX.delegate(app._handleDestroy, this)
                    },
                buttons: [
                    new BX.PopupWindowButton({
                        text: this.getMessage('startBpButtonTitle'),
                        id: this.containerId + '-scancv-dialog-apply-button',
                        events: {
                            click: function () {
                                popup = this.popupWindow;
                                var form = $('#popup-window-content-'+app.containerId + '-scancv-dialog form').first();
                                var data = form.serialize();
                                data = data + '&DoStartParamWorkflow=start';
                                BX.ajax.post(form.attr('action'), data, function (result) {
                                    result = $.parseJSON(result);
                                    if(result.SUCCESS == 'Y') {
                                        BX('popup-window-content-'+app.containerId + '-scancv-dialog').innerHTML = result.MESSAGE;
                                        BX.onCustomEvent(window, 'Grid::confirmDialogApply', [this]);
                                        setTimeout(function(){
                                                popup.close();
                                                popup.destroy();},
                                            2000);

                                    } else {
                                        BX('popup-window-content-'+app.containerId + '-scancv-dialog').innerHTML = result.MESSAGE;
                                    }
                                });

                                BX.unbind(window, 'keydown', hotKey);
                            }
                        }
                    }),
                    new BX.PopupWindowButtonLink({
                        text: this.getMessage('ChangeStatusDialogButtonCancel'),
                        id: this.containerId + '-scancv-dialog-cancel-button',
                        events: {
                            click: function () {
                                popup = this.popupWindow;
                                var form = $('#popup-window-content-'+app.containerId + '-scancv-dialog form').first();
                                var data = form.serialize();
                                data = data + '&CancelStartParamWorkflow=cancel';
                                BX.ajax.post(form.attr('action'), data, function (result) {
                                        popup.close();
                                        popup.destroy();
                                    }
                                );

                                this.popupWindow.close();
                                this.popupWindow.destroy();
                                BX.onCustomEvent(window, 'Grid::confirmDialogCancel', [this]);
                                BX.unbind(window, 'keydown', hotKey);
                            }
                        }
                    })
                ]
            }
        );

        if (!dialog.isShown()) {
            var url = this.getSetting('url_bp_scan')+'&RE_ENTITY_BIZ_ID='+id_associate ;
            BX.addClass(BX('popup-window-content-'+app.containerId + '-scancv-dialog'),'associate-grid-add-btn pagetitle-container load');
            app.insertToNode(url,BX('popup-window-content-'+app.containerId + '-scancv-dialog'),
                function(){
                    BX.removeClass(BX('popup-window-content-'+app.containerId + '-scancv-dialog'),'associate-grid-add-btn pagetitle-container load');
                });
            dialog.show();
            popupContainer = dialog.popupContainer;
            BX.removeClass(popupContainer, "main-grid-show-popup-animation");
            BX.addClass(popupContainer, "main-grid-show-popup-animation");
            applyButton = BX(this.containerId + '-scancv-dialog-apply-button');
            cancelButton = BX(this.containerId + '-scancv-dialog-cancel-button');

            BX.bind(window, 'keydown', hotKey);
        }


        function hotKey(event) {
            if (event.code === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                BX.fireEvent(applyButton, 'click');
            }

            if (event.code === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                BX.fireEvent(cancelButton, 'click');
            }
        }
    }
};

if (typeof(YNSIRAssociateSearchDialogWindow) === "undefined")
{
    YNSIRAssociateSearchDialogWindow = function()
    {
        this._settings = {};
        this.popup = null;
        this.random = "";
        this.contentContainer = null;
        this.zIndex = -100;
        // this.jsEventsManager = null;
        this.pos = null;
        this.height = 0;
        this.width = 0;
        this.resizeCorner = null;
        this.contentdata = null;
        this._messages = null;
    };

    YNSIRAssociateSearchDialogWindow.prototype = {
        initialize: function (settings)
        {
            this.random = Math.random().toString().substring(2);

            this._settings = settings ? settings : {};

            var size = YNSIRAssociateSearchDialogWindow.size;

            this._settings.width = size.width || this._settings.width || 1100;
            this._settings.height = size.height || this._settings.height || 530;
            this._settings.minWidth = this._settings.minWidth || 500;
            this._settings.minHeight = this._settings.minHeight || 800;
            this._settings.draggable = !!this._settings.draggable || true;
            this._settings.resizable = !!this._settings.resizable || true;
            if (typeof(this._settings.closeWindowHandler) !== "function")
                this._settings.closeWindowHandler = null;
            if (typeof(this._settings.showWindowHandler) !== "function")
                this._settings.showWindowHandler = null;

            this._messages = this._settings.messages;
            this.contentdata = this._settings.contentdata;

            // this.jsEventsManager = BX.Crm[this._settings.jsEventsManagerId] || null;

            this.contentContainer = BX.create(
                "DIV",
                {
                    attrs: {
                        className: "crm-catalog",
                        style: "display: block; background-color: #f3f6f7; height: " + this._settings.height +
                        "px; overflow: hidden; width: " + this._settings.width + "px;"
                    }
                }
            );
        },
        _handleCloseDialog: function(popup)
        {
            if(popup)
                popup.destroy();
            this.popup = null;
            // BX.Main.gridManager.destroy;
            // if (this.jsEventsManager)
            // {
            //     this.jsEventsManager.unregisterEventHandlers("CrmProduct_SelectSection");
            // }
            if (typeof(this._settings.closeWindowHandler) === "function")
                this._settings.closeWindowHandler();
            BX.Main.gridManager.reload(this._settings.grid_id);



        },
        _handleDestroy: function(popup) {
            //DElete grid
            var index = BX.Main.gridManager.getDataIndex("YNSI_CANDIDATE_LIST_INTERNAL");

            if (index !== null) {
                delete BX.Main.gridManager.data[index];
            }
            var index_job_order = BX.Main.gridManager.getDataIndex("YNSI_JOB_ORDER_LIST_INTERNAL");

            if (index_job_order !== null) {
                delete BX.Main.gridManager.data[index_job_order];
            }
            //detroy popup search
            if (BX.PopupWindowManager.isPopupExists('YNSI_CANDIDATE_LIST_INTERNAL_search_container')) {
                BX.PopupWindowManager.create('YNSI_CANDIDATE_LIST_INTERNAL_search_container').destroy();
            }
            if (BX.PopupWindowManager.isPopupExists('YNSI_JOB_ORDER_LIST_INTERNAL_search_container')) {
                BX.PopupWindowManager.create('YNSI_JOB_ORDER_LIST_INTERNAL_search_container').destroy();
            }
            //detroy popup search
            if (BX.PopupWindowManager.isPopupExists('YNSI_CANDIDATE_LIST_INTERNAL-grid-settings-window')) {
                BX.PopupWindowManager.create('YNSI_CANDIDATE_LIST_INTERNAL-grid-settings-window').destroy();
            }
            if (BX.PopupWindowManager.isPopupExists('YNSI_JOB_ORDER_LIST_INTERNAL-grid-settings-window')) {
                BX.PopupWindowManager.create('YNSI_JOB_ORDER_LIST_INTERNAL-grid-settings-window').destroy();
            }

        },
        _handleAfterShowDialog: function(popup)
        {
            popup.popupContainer.style.position = "fixed";
            popup.popupContainer.style.top =
                (parseInt(popup.popupContainer.style.top) - BX.GetWindowScrollPos().scrollTop) + 'px';
            if (typeof(this._settings.showWindowHandler) === "function")
                this._settings.showWindowHandler();
        },
        setContent: function (htmlData)
        {
            if (BX.type.isString(htmlData) && BX.type.isDomNode(this.contentContainer))
                this.contentContainer.innerHTML = htmlData;
        },
        showWait:function() {
            BX.addClass(BX('ynsir-associate-btn'),'load');
        },
        closeWait:function() {
            BX.removeClass(BX('ynsir-associate-btn'),'load');
        },
        show: function ()
        {
            this.showWait();
            BX.ajax({
                method: "POST",
                dataType: 'html',
                url: this._settings.content_url,
                data: {
                    'PARAMS':this.contentdata
                },
                skipAuthCheck: true,
                onsuccess: BX.delegate(function(data) {
                    this.setContent(data || "&nbsp;");
                    this.showWindow();
                }, this),
                onfailure: BX.delegate(function() {
                    this.closeWait();
                    if (typeof(this._settings.showWindowHandler) === "function")
                        this._settings.showWindowHandler();
                }, this)
            });
        },
        getMessage: function (name) {
            return this._messages.hasOwnProperty(name) ? this._messages[name] : name;
        },
        showWindow: function ()
        {
            this.popup = new BX.PopupWindow(
                "YNSIRAssociateSearchDialogWindow" + this.random,
                null,
                {
                    overlay: {opacity: 82},
                    autoHide: false,
                    draggable: this._settings.draggable,
                    offsetLeft: 0,
                    offsetTop: 0,

                    bindOptions: { forceBindPosition: false },
                    bindOnResize: false,
                    zIndex: this.zIndex,
                    closeByEsc: false,
                    closeIcon: { top: '10px', right: '15px' },
                    "titleBar":
                        {
                            "content": BX.create("SPAN", { "attrs":
                                { "className": "popup-window-titlebar-text" },
                                "text": this.getMessage('YNSIR_TITLE_SEARCH_POPUP')
                            })
                        },
                    events:
                        {
                            onPopupDestroy: BX.delegate(this._handleDestroy, this),
                            onPopupClose: BX.delegate(this._handleCloseDialog, this),
                            // onAfterPopupShow: BX.delegate(this._handleAfterShowDialog, this)
                        },
                    "content": this.contentContainer
                }
            );
            if (this.popup.popupContainer)
            {
                this.resizeCorner = BX.create(
                    'SPAN',
                    {
                        attrs: {className: "bx-crm-dialog-resize"},
                        events: {mousedown : BX.delegate(this.resizeWindowStart, this)}
                    }
                );
                this.popup.popupContainer.appendChild(this.resizeCorner);
                if (!this._settings.resizable)
                    this.resizeCorner.style.display = "none";
            }
            this.closeWait();
            this.popup.show();
        },
        setResizable: function(resizable)
        {
            resizable = !!resizable;
            if (this._settings.resizable !== resizable)
            {
                this._settings.resizable = resizable;
                if (this.resizeCorner)
                {
                    if (resizable)
                        this.resizeCorner.style.display = "inline-block";
                    else
                        this.resizeCorner.style.display = "none";
                }
            }
        },
        resizeWindowStart: function(e)
        {
            if (!this._settings.resizable)
                return;

            e =  e || window.event;
            BX.PreventDefault(e);

            this.pos = BX.pos(this.contentContainer);

            BX.bind(document, "mousemove", BX.proxy(this.resizeWindowMove, this));
            BX.bind(document, "mouseup", BX.proxy(this.resizeWindowStop, this));

            if (document.body.setCapture)
                document.body.setCapture();

            try { document.onmousedown = false; } catch(e) {}
            try { document.body.onselectstart = false; } catch(e) {}
            try { document.body.ondragstart = false; } catch(e) {}
            try { document.body.style.MozUserSelect = "none"; } catch(e) {}
            try { document.body.style.cursor = "nwse-resize"; } catch(e) {}
        },
        resizeWindowMove: function(e)
        {
            var windowScroll = BX.GetWindowScrollPos();
            var x = e.clientX + windowScroll.scrollLeft;
            var y = e.clientY + windowScroll.scrollTop;

            YNSIRAssociateSearchDialogWindow.size.height = this.height = Math.max(y-this.pos.top, this._settings.minHeight);
            YNSIRAssociateSearchDialogWindow.size.width = this.width = Math.max(x-this.pos.left, this._settings.minWidth);

            this.contentContainer.style.height = this.height+'px';
            this.contentContainer.style.width = this.width+'px';
        },
        resizeWindowStop: function(e)
        {
            if(document.body.releaseCapture)
                document.body.releaseCapture();

            BX.unbind(document, "mousemove", BX.proxy(this.resizeWindowMove, this));
            BX.unbind(document, "mouseup", BX.proxy(this.resizeWindowStop, this));

            try { document.onmousedown = null; } catch(e) {}
            try { document.body.onselectstart = null; } catch(e) {}
            try { document.body.ondragstart = null; } catch(e) {}
            try { document.body.style.MozUserSelect = ""; } catch(e) {}
            try { document.body.style.cursor = "auto"; } catch(e) {}
        }
    };

    YNSIRAssociateSearchDialogWindow.create = function(settings)
    {
        var self = new YNSIRAssociateSearchDialogWindow();
        self.initialize(settings);
        return self;
    };
    YNSIRAssociateSearchDialogWindow.loadCSS = function(settings)
    {
        BX.ajax({
            method: "GET",
            dataType: 'html',
            url: settings.content_url,
            data: {},
            skipAuthCheck: true
        });
    };


    YNSIRAssociateSearchDialogWindow.size = {width: 0, height: 0};
}
