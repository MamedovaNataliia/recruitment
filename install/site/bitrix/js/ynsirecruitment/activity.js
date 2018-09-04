if(typeof(BX.YNSIRActivityEditor) == 'undefined')
{
    BX.YNSIRActivityEditorType =
        {
            recent: 'RECENT',
            history: 'HISTORY',
            mixed: 'MIXED'
        };

    BX.YNSIRActivityStorageType =
        {
            undefined: 0,
            file: 1,
            webdav: 2,
            disk: 3
        };

    BX.YNSIRActivityEditor = function()
    {
        this._id = '';
        this._settings = {};
        this._items = [];
        this._onActivityChangeHandlers = [];
        this._saveHandler = BX.delegate(this._handleActivitySave, this);
        this._dlgCloseHandler = BX.delegate(this._handleActivityDialogClose, this);
        this._dlgOpenerId = null;
        this._createEventHandler = BX.delegate(this.handleAddEventClick, this);
        this._createTaskHandler = BX.delegate(this.handleAddTaskClick, this);
        this._createCallHandler = BX.delegate(this.handleAddCallClick, this);
        this._createMeetingHandler = BX.delegate(this.handleAddMeetingClick, this);
        this._createEmailCreateHandler = BX.delegate(this.handleAddEmailClick, this);
        this._eventListPageReloadHandler = null;

        this._isLocked = false;
        this._lockMessage = "";
        this._initialized = false;
    };
    BX.YNSIRActivityEditor.prototype =
        {
            initialize: function(id, settings, items)
            {
                this._id = BX.type.isNotEmptyString(id) ? id : 'crm_activity_editor';
                this._settings = settings ? settings : {};

                var enableUI = this.isUIEnabled();
                for(var i = 0; i < items.length; i++)
                {
                    var itemSettings = items[i];
                    var rowId = itemSettings['rowID'] ? itemSettings['rowID'] : '';
                    itemSettings['enableUI'] = enableUI && rowId !== '';
                    this._items.push(
                        BX.YNSIRActivity.create(
                            itemSettings,
                            rowId !== '' ? BX(rowId) : null,
                            this
                        )
                    );
                }
                this.showHeading(this._items.length > 0);
                this.showHint(this._items.length === 0);

                var container = this.getContainer();
                if(enableUI)
                {
                    var showAll = BX.findChild(container, { 'tag':'a', 'class':'crm-activity-command-show-all' }, true, false);
                    if(showAll)
                    {
                        BX.bind(showAll, 'click', BX.delegate(this.handleShowAllClick, this));
                    }
                }

                var toolbarContainer = this.getToolbarContainer();
                if(!toolbarContainer)
                {
                    toolbarContainer = container;
                }

                if(toolbarContainer && !this.getSetting('readOnly', true) && this.getSetting('enableToolbar', true))
                {
                    var addEvent = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-event' }, true, false);
                    if(addEvent)
                    {
                        BX.bind(addEvent, 'click', this._createEventHandler);
                    }

                    if(this.isTasksEnabled())
                    {
                        var addTask = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-task' }, true, false);
                        if(addTask)
                        {
                            BX.bind(addTask, 'click', this._createTaskHandler);
                        }
                    }

                    if(this.isCalendarEventsEnabled())
                    {
                        var addCall = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-call' }, true, false);
                        if(addCall)
                        {
                            BX.bind(addCall, 'click', this._createCallHandler);
                        }

                        var addMeeting = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-meeting' }, true, false);
                        if(addMeeting)
                        {
                            BX.bind(addMeeting, 'click', this._createMeetingHandler);
                        }
                    }

                    if(this.isEmailsEnabled())
                    {
                        var addEmail = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-email' }, true, false);
                        if(addEmail)
                        {
                            BX.bind(addEmail, 'click', this._createEmailCreateHandler);
                        }
                    }

                    BX.onCustomEvent(this, 'toolbarBuildUp', [ { 'container': toolbarContainer } ]);
                }

                // clear service container
                var serviceContainer = BX(this.getSetting('serviceContainerID', 'service_container'));
                if(serviceContainer)
                {
                    BX.cleanNode(serviceContainer);
                }

                // clear clock
                if(typeof(window['bxClock_' + this.getSetting('clockInputID', '')]) !== 'undefined')
                {
                    delete window['bxClock_' + this.getSetting('clockInputID', '')];
                }

                this._initialized = true;
                return this._id;
            },
            release: function()
            {
                if(!this._initialized)
                {
                    return;
                }

                var toolbarContainer = this.getToolbarContainer();
                if(!toolbarContainer)
                {
                    toolbarContainer = this.getContainer();
                }

                if(toolbarContainer && !this.getSetting('readOnly', true) && this.getSetting('enableToolbar', true))
                {

                    if(this._eventListPageReloadHandler)
                    {
                        BX.removeCustomEvent(window, "YNSIRBeforeEventPageReload", this._eventListPageReloadHandler);
                    }

                    if(this.isTasksEnabled())
                    {
                        var addTask = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-task' }, true, false);
                        if(addTask)
                        {
                            BX.unbind(addTask, 'click', this._createTaskHandler);
                        }
                    }

                    if(this.isCalendarEventsEnabled())
                    {
                        var addCall = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-call' }, true, false);
                        if(addCall)
                        {
                            BX.unbind(addCall, 'click', this._createCallHandler);
                        }

                        var addMeeting = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-meeting' }, true, false);
                        if(addMeeting)
                        {
                            BX.unbind(addMeeting, 'click', this._createMeetingHandler);
                        }
                    }

                    if(this.isEmailsEnabled())
                    {
                        var addEmail = BX.findChild(toolbarContainer, { 'class':'crm-activity-command-add-email' }, true, false);
                        if(addEmail)
                        {
                            BX.unbind(addEmail, 'click', this._createEmailCreateHandler);
                        }
                    }

                    this._initialized = false;
                    BX.onCustomEvent(this, 'release');
                }
            },
            getId: function()
            {
                return this._id;
            },
            getType: function()
            {
                return this.getSetting('type', BX.YNSIRActivityEditorType.mixed);
            },
            isDiskEnabled: function()
            {
                return this.getSetting('enableDisk', false);
            },
            isWebDavEnabled: function()
            {
                return this.getSetting('enableWebDav', false);
            },
            getDefaultStorageTypeId: function()
            {
                var result = parseInt(this.getSetting('defaultStorageTypeId', BX.YNSIRActivityStorageType.undefined));
                if(result === BX.YNSIRActivityStorageType.undefined)
                {
                    if(this.isDiskEnabled())
                    {
                        result = BX.YNSIRActivityStorageType.disk;
                    }
                    else if(this.isWebDavEnabled())
                    {
                        result = BX.YNSIRActivityStorageType.webdav;
                    }
                    else
                    {
                        result = BX.YNSIRActivityStorageType.file;
                    }
                }
                return result;
            },
            getSetting: function (name, defaultval)
            {
                return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
            },
            setSetting: function (name, val)
            {
                this._settings[name] = val;
            },
            getOwnerType: function()
            {
                return this.getSetting('ownerType', '');
            },
            getOwnerId: function()
            {
                var result = parseInt(this.getSetting('ownerID', 0));
                return !isNaN(result) ? result : 0;
            },
            isTaskTracingEnabled: function()
            {
                return this.getSetting('enableTaskTracing', false);
            },
            isTasksEnabled: function()
            {
                return this.getSetting('enableTasks', false);
            },
            isCalendarEventsEnabled: function()
            {
                return this.getSetting('enableCalendarEvents', false);
            },
            isEmailsEnabled: function()
            {
                return this.getSetting('enableEmails', false);
            },
            getContainer: function()
            {
                return BX(this.getSetting('containerID', 'action_list'));
            },
            getToolbarContainer: function()
            {
                var toolbarId = this.getSetting('toolbarID', '');
                return BX.type.isNotEmptyString(toolbarId) ? BX(toolbarId) : null;
            },
            getServiceContainer: function()
            {
                var containerID = this.getSetting('serviceContainerID', 'service_container');
                var container = BX(containerID);
                if(!container)
                {
                    container = BX.create('DIV', { props: { id: containerID } });
                    document.body.appendChild(container);
                }

                return container;
            },
            getHeading: function()
            {
                return BX.findChild(this.getContainer(), { 'tag':'tr', 'class':'crm-activity-table-head' }, true, false);
            },
            showHeading: function(show)
            {
                show = !!show;
                var heading = this.getHeading();
                if(heading)
                {
                    heading.style.display = show ? '' : 'none';
                }
            },
            getHint: function()
            {
                return BX.findChild(this.getContainer(), { 'tag':'div', 'class':'crm-view-no-actions-hint' }, true, false);
            },
            showHint: function(show)
            {
                show = !!show;
                var hint = this.getHint();
                if(hint)
                {
                    hint.style.display = show ? '' : 'none';
                }
            },
            getButton: function()
            {
                return BX(this.getSetting('buttonID'), '');
            },
            getItemIndexById: function(id)
            {
                for(var i = 0; i < this._items.length; i++)
                {
                    if(this._items[i].getId() == id)
                    {
                        return i;
                    }
                }
                return -1;
            },
            getItemById: function(id)
            {
                var ind = this.getItemIndexById(id);
                return ind >= 0 ? this._items[ind] : null;
            },
            isUIEnabled: function()
            {
                return this.getSetting('enableUI', false);
            },
            setItems: function(items, notify)
            {
                for(var i = 0; i < this._items.length; i++)
                {
                    this._items[i].cleanLayout();
                }

                this._items = [];
                var enableUI = this.isUIEnabled();
                var settings;
                if(enableUI)
                {
                    var table = BX.findChild(this.getContainer(), { 'tag':'table', 'class':'crm-activity-table' }, true, false);
                    if(!table)
                    {
                        return;
                    }

                    this.showHeading(true);
                    var tbody = table.tBodies[0];

                    for(var j = 0; j < items.length; j++)
                    {
                        settings = items[j];
                        settings['enableUI'] = true;
                        var itemRow = tbody.insertRow(-1);
                        itemRow.className = 'crm-activity-row';

                        var item = BX.YNSIRActivity.create(settings, itemRow, this);
                        this._items.push(item);
                        item.layout();
                    }

                    this.showHint(this._items.length === 0);
                    this.showAll();
                    this._syncRows();
                }
                else
                {
                    for(var k = 0; k < items.length; k++)
                    {
                        settings = items[k];
                        settings['enableUI'] = false;
                        this._items.push(BX.YNSIRActivity.create(settings, null, this));
                    }
                }
            },
            showAll: function()
            {
                if(!this.isUIEnabled())
                {
                    return;
                }

                var showAllLink = BX.findChild(this.getContainer(), { 'tag':'a', 'class':'crm-activity-command-show-all' }, true, false);
                if(!showAllLink || showAllLink.style.display == 'none')
                {
                    return;
                }

                showAllLink.style.display = 'none';

                var rows = BX.findChildren(this.getContainer(), { 'tag':'tr', 'class':'crm-activity-row' }, true);
                if(!rows)
                {
                    return;
                }

                for(var i = 0; i < rows.length; i++)
                {
                    var r = rows[i];
                    if(r.style.display == 'none')
                    {
                        r.style.display = '';
                    }
                }
            },
            display: function(display)
            {
                this.getContainer().style.display = display ? '' : 'none';

                var button = this.getButton();
                if(display)
                {
                    BX.addClass(button, 'bx-crm-view-fieldset-title-selected');
                }
                else
                {
                    BX.removeClass(button, 'bx-crm-view-fieldset-title-selected');
                }
            },
            openActivityDialog: function(mode, itemId, options, onCloseCallBack)
            {
                var item = this.getItemById(itemId);
                if(!item)
                {
                    return;
                }

                if(this._dlgOpenerId)
                {
                    window.clearTimeout(this._dlgOpenerId);
                }

                if(!options)
                {
                    options = {};
                }

                var typeID = parseInt(item.getSetting('typeID', '0'));
                var ownerType = item.getSetting('ownerType', this.getSetting('ownerType', ''));
                var ownerID = parseInt(item.getSetting('ownerID', this.getSetting('ownerID', '0')));

                var activity = null;
                if(typeID === BX.YNSIRActivityType.call
                    || typeID === BX.YNSIRActivityType.meeting
                    || typeID === BX.YNSIRActivityType.activity)
                {
                    var calEventSettings =
                        {
                            ID: item.getSetting('ID', 0),
                            ownerType: ownerType,
                            ownerID: ownerID,
                            ownerTitle: item.getSetting('ownerTitle', ''),
                            ownerUrl: item.getSetting('ownerUrl', ''),
                            typeID: typeID,
                            subject: item.getSetting('subject', ''),
                            description: item.getSetting('description', ''),
                            descriptionHtml: item.getSetting('descriptionHtml', ''),
                            descriptionBBCode: item.getSetting('descriptionBBCode', ''),
                            location: item.getSetting('location', ''),
                            start: item.getSetting('start', ''),
                            end: item.getSetting('end', ''),
                            deadline: item.getSetting('deadline', ''),
                            completed: item.getSetting('completed', false),
                            notifyType: item.getSetting('notifyType', ''),
                            notifyValue: item.getSetting('notifyValue', ''),
                            priority: item.getSetting('priority', ''),
                            responsibleID: item.getSetting('responsibleID', ''),
                            responsibleName: item.getSetting('responsibleName', ''),
                            responsibleUrl: item.getSetting('responsibleUrl', ''),
                            storageTypeID: item.getSetting('storageTypeID', ''),
                            files: item.getSetting('files', []),
                            webdavelements: item.getSetting('webdavelements', []),
                            diskfiles: item.getSetting('diskfiles', []),
                            communicationsLoaded: item.getSetting('communicationsLoaded', true),
                            communications: item.getSetting('communications', []),
                            uploadID: this.getSetting('uploadID', ''),
                            uploadControlID: this.getSetting('uploadControlID', ''),
                            uploadInputID: this.getSetting('uploadInputID', ''),
                            clockID: this.getSetting(typeID === BX.YNSIRActivityType.call ? 'callClockID' : 'meetingClockID', ''),
                            clockInputID: this.getSetting(typeID === BX.YNSIRActivityType.call ? 'callClockInputID' : 'meetingClockInputID', ''),
                            prefix: this.getSetting('prefix', ''),
                            serviceUrl: this.getSetting('serviceUrl', ''),
                            serverTime: this.getSetting('serverTime', ''),
                            imagePath: this.getSetting('imagePath', ''),
                            defaultStorageTypeId: this.getDefaultStorageTypeId(),
                            userID: this.getSetting('userID', ''),
                            userFullName: this.getSetting('userFullName', ''),
                            userSearchJsName: this.getSetting('userSearchJsName', ''),
                            disableStorageEdit: this.getSetting('disableStorageEdit', false)
                        };

                    if(typeID === BX.YNSIRActivityType.call)
                    {
                        calEventSettings['direction'] = parseInt(item.getSetting('direction', BX.YNSIRActivityDirection.outgoing));
                        calEventSettings['callToFormat'] = this.getSetting('callToFormat', BX.YNSIRCalltoFormat.slashless);
                    }

                    activity =
                        BX.YNSIRActivityCalEvent.create(calEventSettings, this,  options);
                }
                else if(typeID === BX.YNSIRActivityType.email)
                {
                    var emailSettings =
                        {
                            ID: item.getSetting('ID', 0),
                            ownerType: ownerType,
                            ownerID: ownerID,
                            ownerTitle: item.getSetting('ownerTitle', ''),
                            ownerUrl: item.getSetting('ownerUrl', ''),
                            typeID: BX.YNSIRActivityType.email,
                            subject: item.getSetting('subject', ''),
                            description: item.getSetting('description', ''),
                            descriptionHtml: item.getSetting('descriptionHtml', ''),
                            descriptionBBCode: item.getSetting('descriptionBBCode', ''),
                            start: item.getSetting('start', ''),
                            end: item.getSetting('end', ''),
                            deadline: item.getSetting('deadline', ''),
                            completed: item.getSetting('completed', false),
                            priority: item.getSetting('priority', ''),
                            responsibleID: item.getSetting('responsibleID', ''),
                            responsibleName: item.getSetting('responsibleName', ''),
                            responsibleUrl: item.getSetting('responsibleUrl', ''),
                            storageTypeID: item.getSetting('storageTypeID', ''),
                            files: item.getSetting('files', []),
                            webdavelements: item.getSetting('webdavelements', []),
                            diskfiles: item.getSetting('diskfiles', []),
                            communicationsLoaded: item.getSetting('communicationsLoaded', true),
                            communications: item.getSetting('communications', []),
                            userFullName: this.getSetting('userFullName', ''),
                            userEmail: this.getSetting('userEmail', ''),
                            userEmail2: this.getSetting('userEmail2', ''),
                            crmEmail: this.getSetting('crmEmail', ''),
                            lastUsedEmail: this.getSetting('lastUsedEmail', ''),
                            lastUsedMailTemplateID: this.getSetting('lastUsedMailTemplateID', 0),
                            uploadID: this.getSetting('emailUploadContainerID', ''),
                            uploadControlID: this.getSetting('emailUploadControlID', ''),
                            uploadInputID: this.getSetting('emailUploadInputID', ''),
                            lheContainerID: this.getSetting('emailLheContainerID', ''),
                            lheJsName: this.getSetting('emailLheJsName', ''),
                            prefix: this.getSetting('prefix', ''),
                            serviceUrl: this.getSetting('serviceUrl', ''),
                            serverTime: this.getSetting('serverTime', ''),
                            imagePath: this.getSetting('imagePath', ''),
                            defaultStorageTypeId: this.getDefaultStorageTypeId(),
                            mailTemplateData: this.getSetting('mailTemplateData', [])
                        };

                        emailSettings['direction'] = parseInt(item.getSetting('direction', BX.YNSIRActivityDirection.outgoing));
                    if(!item.getSetting('completed', false))
                    {
                        emailSettings['completed'] = true;
                        this.setActivityCompleted(item.getSetting('ID', 0), true, null, { disableNotification: true });
                    }
                    activity = BX.YNSIRActivityEmail.create(emailSettings, this, options);
                }
                else if(typeID === BX.YNSIRActivityType.task)
                {
                    var taskId = parseInt(item.getSetting('associatedEntityID', 0));
                    if(taskId <= 0)
                    {
                        return;
                    }

                    if(typeof(window['taskIFramePopup']) === 'object' && typeof(window['taskIFramePopup'].view) === 'function')
                    {
                        if (typeof(window['tasksIFrameList']) === 'undefined')
                        {
                            window['tasksIFrameList'] = [];
                        }

                        window['taskIFramePopup'].view(taskId, window['tasksIFrameList']);
                    }
                    else {
                        window.top.BX.YNSIRUIGridExtension.viewActivity('YNSI_CANDIDATE_LIST_MANAGER', itemId, { enableEditButton:true }); return false;
                    }
                }
                else if(typeID === BX.YNSIRActivityType.provider && BX.YNSIRActivityProvider)
                {
                    var providerSettings =
                        {
                            ID: item.getSetting('ID', 0),
                            ownerType: ownerType,
                            ownerID: ownerID,
                            ownerTitle: item.getSetting('ownerTitle', ''),
                            ownerUrl: item.getSetting('ownerUrl', ''),
                            subject: item.getSetting('subject', ''),
                            description: item.getSetting('description', ''),
                            descriptionHtml: item.getSetting('descriptionHtml', ''),
                            descriptionBBCode: item.getSetting('descriptionBBCode', ''),
                            start: item.getSetting('start', ''),
                            end: item.getSetting('end', ''),
                            deadline: item.getSetting('deadline', ''),
                            completed: item.getSetting('completed', false),
                            priority: item.getSetting('priority', ''),
                            responsibleID: item.getSetting('responsibleID', ''),
                            responsibleName: item.getSetting('responsibleName', ''),
                            responsibleUrl: item.getSetting('responsibleUrl', ''),
                            storageTypeID: item.getSetting('storageTypeID', ''),
                            files: item.getSetting('files', []),
                            webdavelements: item.getSetting('webdavelements', []),
                            diskfiles: item.getSetting('diskfiles', []),
                            communicationsLoaded: item.getSetting('communicationsLoaded', true),
                            communications: item.getSetting('communications', []),
                            userFullName: this.getSetting('userFullName', ''),
                            userEmail: this.getSetting('userEmail', ''),
                            userEmail2: this.getSetting('userEmail2', ''),
                            crmEmail: this.getSetting('crmEmail', ''),
                            lastUsedEmail: this.getSetting('lastUsedEmail', ''),
                            lastUsedMailTemplateID: this.getSetting('lastUsedMailTemplateID', 0),
                            uploadID: this.getSetting('emailUploadContainerID', ''),
                            uploadControlID: this.getSetting('emailUploadControlID', ''),
                            uploadInputID: this.getSetting('emailUploadInputID', ''),
                            lheContainerID: this.getSetting('emailLheContainerID', ''),
                            lheJsName: this.getSetting('emailLheJsName', ''),
                            prefix: this.getSetting('prefix', ''),
                            serviceUrl: this.getSetting('serviceUrl', ''),
                            serverTime: this.getSetting('serverTime', ''),
                            imagePath: this.getSetting('imagePath', ''),
                            defaultStorageTypeId: this.getDefaultStorageTypeId(),
                            mailTemplateData: this.getSetting('mailTemplateData', [])
                        };

                    activity = BX.YNSIRActivityProvider.create(providerSettings, this, options);
                }

                if(!activity)
                {
                    return;
                }

                activity.addOnSave(this._saveHandler);
                activity.addOnDialogClose(this._dlgCloseHandler);

                if(typeof(onCloseCallBack) === 'function')
                {
                    activity.addOnDialogClose(onCloseCallBack);
                }

                var self = this;
                this._dlgOpenerId = window.setTimeout(
                    function() { activity.openDialog(mode); self._dlgOpenerId = null; },
                    100
                );
            },
            setActivityCompleted: function(id, completed, callback, options)
            {
                var item = this.getItemById(id);
                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        id: id,
                        action: 'complete',
                        ownertype:this.getSetting('ownerType', ''),// item ? item.getSetting('ownerType', this.getSetting('ownerType', '')) : '',
                        ownerid: item ? parseInt(item.getSetting('ownerID', this.getSetting('ownerID', ''))) : 0,
                        completed: completed ? 'Y' : 'N'
                    }
                );
                var self = this;
                BX.ajax({
                    'url': serviceUrl,
                    'method': 'POST',
                    'dataType': 'json',
                    'data':
                        {
                            'ACTION' : 'COMPLETE',
                            'COMPLETED': completed ? 1 : 0,
                            'ITEM_ID': id,
                            'OWNER_TYPE': this.getSetting('ownerType', ''),//item ? item.getSetting('ownerType', this.getSetting('ownerType', '')) : '',
                            'OWNER_ID': item ? parseInt(item.getSetting('ownerID', this.getSetting('ownerID', ''))) : 0
                        },
                    onsuccess: function(data)
                    {
                        if(data['ITEM_ID'])
                        {
                            var item = self.getItemById(data['ITEM_ID']);
                            if(item)
                            {
                                item.setCompleted(!!data['COMPLETED']);
                                item.layout();

                                if(BX.type.isFunction(callback))
                                {
                                    try
                                    {
                                        callback(data);
                                    }
                                    catch(ex)
                                    {
                                    }
                                }

                                var disableNotification = BX.type.isPlainObject(options) && !!options['disableNotification'];
                                if(!disableNotification)
                                {
                                    self._notifyActivityChange('UPDATE', item.getSettings(), true);
                                }
                            }
                            else
                            {
                                if(BX.type.isFunction(callback))
                                {
                                    try
                                    {
                                        callback(data);
                                    }
                                    catch(ex)
                                    {
                                    }
                                }
                            }
                        }
                    },
                    onfailure: function(data)
                    {
                    }
                });
            },
            setActivityPriority: function(id, priority, callback)
            {
                var item = this.getItemById(id);
                if(!item)
                {
                    return false;
                }
                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        id: id,
                        action: 'set_priority',
                        ownertype: item.getSetting('ownerType', this.getSetting('ownerType', '')),
                        ownerid: item.getSetting('ownerID', this.getSetting('ownerID', '')),
                        priority: priority
                    }
                );
                var self = this;
                BX.ajax({
                    'url': serviceUrl,
                    'method': 'POST',
                    'dataType': 'json',
                    'data':
                        {
                            'ACTION' : 'SET_PRIORITY',
                            'ITEM_ID': id,
                            'PRIORITY': priority,
                            'OWNER_TYPE': item.getSetting('ownerType', this.getSetting('ownerType', '')),
                            'OWNER_ID': item.getSetting('ownerID', this.getSetting('ownerID', ''))
                        },
                    onsuccess: function(data)
                    {
                        if(data['ITEM_ID'])
                        {
                            var item = self.getItemById(data['ITEM_ID']);
                            if(item)
                            {
                                item.setPriority(data['PRIORITY']);
                                item.layout();

                                if(BX.type.isFunction(callback))
                                {
                                    try
                                    {
                                        callback(data);
                                    }
                                    catch(ex)
                                    {
                                    }
                                }

                                self._notifyActivityChange('UPDATE',  item.getSettings(), true);
                            }
                        }
                    },
                    onfailure: function(data)
                    {
                    }
                });
            },
            viewActivity: function(id, options)
            {
                id = parseInt(id);
                if(isNaN(id))
                {
                    return;
                }

                var item = this.getItemById(id);
                if(item)
                {
                    this.openActivityDialog(BX.YNSIRDialogMode.view, id, options, null);
                    return;
                }

                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        id: id,
                        action: 'get_activity',
                        ownertype: this.getSetting('ownerType', ''),
                        ownerid: this.getSetting('ownerID', '')
                    }
                );
                BX.ajax({
                    'url': serviceUrl,
                    'method': 'POST',
                    'dataType': 'json',
                    'data':
                        {
                            'ACTION' : 'GET_ACTIVITY',
                            'ID': id,
                            'OWNER_TYPE': this.getSetting('ownerType', ''),
                            'OWNER_ID': this.getSetting('ownerID', '')
                        },
                    onsuccess: BX.delegate(
                        function(data)
                        {
                            if(typeof(data['ACTIVITY']) !== 'undefined')
                            {
                                this._handleActivityChange(data['ACTIVITY']);
                                this.openActivityDialog(BX.YNSIRDialogMode.view, id, options, null);
                            }
                        },
                        this
                    ),
                    onfailure: function(data){}
                });

            },
            editActivity: function(id, options)
            {
                id = parseInt(id);
                if(isNaN(id))
                {
                    return;
                }

                var item = this.getItemById(id);
                if(item)
                {
                    this.openActivityDialog(BX.YNSIRDialogMode.edit, id, options, null);
                    return;
                }

                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        id: id,
                        action: 'get_activity',
                        ownertype: this.getSetting('ownerType', ''),
                        ownerid: this.getSetting('ownerID', '')
                    }
                );
                BX.ajax({
                    'url': serviceUrl,
                    'method': 'POST',
                    'dataType': 'json',
                    'data':
                        {
                            'ACTION' : 'GET_ACTIVITY',
                            'ID': id,
                            'OWNER_TYPE': this.getSetting('ownerType', ''),
                            'OWNER_ID': this.getSetting('ownerID', '')
                        },
                    onsuccess: BX.delegate(
                        function(data)
                        {
                            if(typeof(data['ACTIVITY']) !== 'undefined')
                            {
                                this._handleActivityChange(data['ACTIVITY']);
                                this.openActivityDialog(BX.YNSIRDialogMode.edit, id, options, null);
                            }
                        },
                        this
                    ),
                    onfailure: function(data){}
                });
            },
            deleteActivity: function(id, skipConfirmation, callback)
            {
                id = parseInt(id);
                if(isNaN(id))
                {
                    return false;
                }

                var item = this.getItemById(id);
                if(!item)
                {
                    return false;
                }

                skipConfirmation = !!skipConfirmation;
                if(!skipConfirmation && !window.confirm(BX.YNSIRActivityEditor.getMessage('deletionConfirm')))
                {
                    return false;
                }

                var self = this;
                var settings = item.getSettings();
                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        id: id ,
                        action: 'delete',
                        ownertype: item.getSetting('ownerType', this.getSetting('ownerType', '')),
                        ownerid: item.getSetting('ownerID', this.getSetting('ownerID', ''))
                    }
                );
                BX.ajax({
                    'url': serviceUrl,
                    'method': 'POST',
                    'dataType': 'json',
                    'data':
                        {
                            'ACTION' : 'DELETE',
                            'ITEM_ID': id,
                            'OWNER_TYPE': item.getSetting('ownerType', this.getSetting('ownerType', '')),
                            'OWNER_ID': item.getSetting('ownerID', this.getSetting('ownerID', ''))
                        },
                    onsuccess: function(data)
                    {
                        if(typeof(data['DELETED_ITEM_ID']) != 'undefined' && data['DELETED_ITEM_ID'] == id)
                        {
                            self._handleActivityDelete(settings);
                            if(BX.type.isFunction(callback))
                            {
                                try
                                {
                                    callback(settings);
                                }
                                catch(ex)
                                {
                                }
                            }
                            self._notifyActivityChange('DELETE',  settings, true);
                        }
                    },
                    onfailure: function(data)
                    {
                    }
                });

                return true;
            },
            setActivityCommunications: function(id, communications)
            {
                var item = this.getItemById(id);
                if(item)
                {
                    item.setSetting('communicationsLoaded', true);
                    item.setSetting('communications', communications);
                }
            },
            handleAddEventClick: function(e)
            {
                BX.PreventDefault(e);
                this.addEvent();
            },
            addEvent: function()
            {
                var url = this.getSetting("addEventUrl", "");
                if(url === "")
                {
                    return;
                }

                url = BX.util.add_url_param(url,
                    {
                        "FORM_ID": "",
                        "ENTITY_TYPE": this.getOwnerType(),
                        "ENTITY_ID": this.getOwnerId()
                    }
                );

                var dialog = new BX.CDialog({ content_url: url, width: '498', height: '275', resizable: false });
                dialog.Show();

                if(!this._eventListPageReloadHandler)
                {
                    this._eventListPageReloadHandler = BX.delegate(this._onEventListPageReload, this);
                    BX.addCustomEvent(window, "YNSIRBeforeEventPageReload", this._eventListPageReloadHandler);
                }
            },
            _onEventListPageReload: function(eventData)
            {
                BX.removeCustomEvent(window, "YNSIRBeforeEventPageReload", this._eventListPageReloadHandler);
                this._eventListPageReloadHandler = null;

                var formId = this.getSetting("formId", "");
                if(formId === "")
                {
                    return;
                }

                var formObjName = "bxForm_" + formId;
                if(typeof(window[formObjName]) !== "undefined")
                {
                    var formObj = window[formObjName];

                    var eventTabId = this.getSetting("eventTabId");
                    if(eventTabId !== formObj.GetActiveTabId())
                    {
                        formObj.SelectTab(eventTabId);
                    }
                    eventData.cancel = true;
                }
            },
            addTask: function(settings)
            {
                if(!this.isTasksEnabled())
                {
                    return;
                }

                if(typeof(settings) !== 'object')
                {
                    settings = {};
                }

                if(typeof(settings['ownerType']) === 'undefined')
                {
                    settings['ownerType'] = this.getSetting('ownerType', '');
                }

                if(typeof(settings['ownerID']) === 'undefined')
                {
                    settings['ownerID'] = this.getSetting('ownerID', '');
                }

                var taskData =
                    {
                        UF_YNSIR_TASK: settings['ownerID'],//BX.YNSIROwnerTypeAbbr.resolve(settings['ownerType'])
                        UF_YNSIR_ASSOCIATE_ID: settings['associateID'],
                        TITLE: "RECRUITMENT: ",
                        TAGS: "YNSIR"

                    };

                if(typeof(window['taskIFramePopup']) === 'object'
                    && typeof(window['taskIFramePopup'].add) === 'function')
                {
                    window['taskIFramePopup'].add(taskData);
                }
            },
            handleAddTaskClick: function(e)
            {
                BX.PreventDefault(e);
                this.addTask();
            },
            getUserEmails: function()
            {
                var result = [];
                var emailTemplate = this.getSetting('emailTemplate', null);
                if(emailTemplate && emailTemplate['from'])
                {
                    result.push(emailTemplate['from']);
                }

                var crmEmail = this.getSetting('crmEmail', '');
                var userEmail = this.getSetting('userEmail', '');
                var userEmail2 = this.getSetting('userEmail2', '');

                if (userEmail == crmEmail)
                    userEmail = '';
                if (userEmail2 == crmEmail || userEmail2 == userEmail)
                    userEmail2 = '';

                var userName = this.getSetting('userFullName', '');
                if (userEmail2 !== '')
                    result.push(userName === '' ? userEmail2 : userName + ' <' + userEmail2 + '>');
                if (crmEmail != '')
                    result.push(userName === '' ? crmEmail : userName + ' <' + crmEmail + '>');
                if (userEmail !== '')
                    result.push(userName === '' ? userEmail : userName + ' <' + userEmail + '>');

                return result;
            },
            addCall: function(settings)
            {
                if(!this.isCalendarEventsEnabled())
                {
                    return null;
                }

                if(this.isLocked())
                {
                    this.showLockMessage();
                    return null;
                }

                if(typeof(settings) !== 'object')
                {
                    settings = {};
                }

                if(typeof(settings['ownerType']) === 'undefined')
                {
                    settings['ownerType'] = this.getSetting('ownerType', '');
                }

                if(typeof(settings['ownerID']) === 'undefined')
                {
                    settings['ownerID'] = this.getSetting('ownerID', '0');
                }

                if(typeof(settings['ownerUrl']) === 'undefined')
                {
                    settings['ownerUrl'] = this.getSetting('ownerUrl', '');
                }

                if(typeof(settings['ownerTitle']) === 'undefined')
                {
                    settings['ownerTitle'] = this.getSetting('ownerTitle', '');
                }

                settings['typeID'] = BX.YNSIRActivityType.call;
                settings['uploadID'] = this.getSetting('uploadID', '');
                settings['uploadControlID'] = this.getSetting('uploadControlID', '');
                settings['uploadInputID'] = this.getSetting('uploadInputID', '');
                settings['clockID'] = this.getSetting('callClockID', '');
                settings['clockInputID'] = this.getSetting('callClockInputID', '');
                settings['prefix'] = this.getSetting('prefix', '');
                settings['serviceUrl'] = this.getSetting('serviceUrl', '');
                settings['serverTime'] = this.getSetting('serverTime', '');
                settings['imagePath'] = this.getSetting('imagePath', '');
                settings['userID'] = this.getSetting('userID', '');
                settings['userFullName'] = this.getSetting('userFullName', '');
                settings['userSearchJsName'] = this.getSetting('userSearchJsName', '');
                settings['defaultStorageTypeId'] = this.getDefaultStorageTypeId();
                settings['callToFormat'] = this.getSetting('callToFormat', BX.YNSIRCalltoFormat.slashless);

                var activity = BX.YNSIRActivityCalEvent.create(settings, this);
                activity.addOnSave(this._saveHandler);
                activity.addOnDialogClose(this._dlgCloseHandler);
                activity.openDialog(BX.YNSIRDialogMode.edit);
                return activity;
            },
            addMeeting: function(settings)
            {
                if(!this.isCalendarEventsEnabled())
                {
                    return null;
                }

                if(this.isLocked())
                {
                    this.showLockMessage();
                    return null;
                }

                if(typeof(settings) !== 'object')
                {
                    settings = {};
                }

                if(typeof(settings['ownerType']) === 'undefined')
                {
                    settings['ownerType'] = this.getSetting('ownerType', '');
                }

                if(typeof(settings['ownerID']) === 'undefined')
                {
                    settings['ownerID'] = this.getSetting('ownerID', '0');
                }

                if(typeof(settings['ownerUrl']) === 'undefined')
                {
                    settings['ownerUrl'] = this.getSetting('ownerUrl', '');
                }

                if(typeof(settings['ownerTitle']) === 'undefined')
                {
                    settings['ownerTitle'] = this.getSetting('ownerTitle', '');
                }

                settings['typeID'] = BX.YNSIRActivityType.meeting;
                settings['uploadID'] = this.getSetting('uploadID', '');
                settings['uploadControlID'] = this.getSetting('uploadControlID', '');
                settings['uploadInputID'] = this.getSetting('uploadInputID', '');
                settings['clockID'] = this.getSetting('meetingClockID', '');
                settings['clockInputID'] = this.getSetting('meetingClockInputID', '');
                settings['prefix'] = this.getSetting('prefix', '');
                settings['serviceUrl'] = this.getSetting('serviceUrl', '');
                settings['serverTime'] = this.getSetting('serverTime', '');
                settings['imagePath'] = this.getSetting('imagePath', '');
                settings['userID'] = this.getSetting('userID', '');
                settings['userFullName'] = this.getSetting('userFullName', '');
                settings['userSearchJsName'] = this.getSetting('userSearchJsName', '');
                settings['defaultStorageTypeId'] = this.getDefaultStorageTypeId();

                var activity = BX.YNSIRActivityCalEvent.create(settings, this);
                activity.addOnSave(this._saveHandler);
                activity.addOnDialogClose(this._dlgCloseHandler);
                activity.openDialog(BX.YNSIRDialogMode.edit);
                return activity;
            },
            addEmail: function(settings)
            {
                if(!this.isEmailsEnabled())
                {
                    return null;
                }

                if(this.isLocked())
                {
                    this.showLockMessage();
                    return null;
                }

                if(typeof(settings) !== 'object')
                {
                    settings = {};
                }

                if(typeof(settings['ownerType']) === 'undefined')
                {
                    settings['ownerType'] = this.getSetting('ownerType', '');
                }

                if(typeof(settings['ownerID']) === 'undefined')
                {
                    settings['ownerID'] = this.getSetting('ownerID', '');
                }

                if(typeof(settings['ownerUrl']) === 'undefined')
                {
                    settings['ownerUrl'] = this.getSetting('ownerUrl', '');
                }

                if(typeof(settings['ownerTitle']) === 'undefined')
                {
                    settings['ownerTitle'] = this.getSetting('ownerTitle', '');
                }

                settings['userFullName'] = this.getSetting('userFullName', '');
                settings['userEmail'] = this.getSetting('userEmail', '');
                settings['userEmail2'] = this.getSetting('userEmail2', '');
                settings['crmEmail']  = this.getSetting('crmEmail', '');
                settings['lastUsedEmail']  = this.getSetting('lastUsedEmail', '');
                settings['lastUsedMailTemplateID'] = this.getSetting('lastUsedMailTemplateID', 0);
                settings['uploadID'] = this.getSetting('emailUploadContainerID', '');
                settings['uploadControlID'] = this.getSetting('emailUploadControlID', '');
                settings['uploadInputID'] = this.getSetting('emailUploadInputID', '');
                settings['lheContainerID'] = this.getSetting('emailLheContainerID', '');
                settings['lheJsName'] = this.getSetting('emailLheJsName', '');
                settings['prefix'] = this.getSetting('prefix', '');
                settings['serviceUrl'] = this.getSetting('serviceUrl', '');
                settings['serverTime'] = this.getSetting('serverTime', '');
                settings['imagePath'] = this.getSetting('imagePath', '');
                settings['defaultStorageTypeId'] = this.getDefaultStorageTypeId();
                //settings['emailTemplate'] = emailTemplate;
                settings['mailTemplateData'] = this.getSetting('mailTemplateData', []);

                var activity = BX.YNSIRActivityEmail.create(settings, this);
                activity.addOnSave(this._saveHandler);
                activity.addOnDialogClose(this._dlgCloseHandler);
                activity.openDialog(BX.YNSIRDialogMode.edit);
                return activity;
            },
            handleAddCallClick: function(e)
            {
                this.addCall();
                return BX.PreventDefault(e);
            },
            handleAddMeetingClick: function(e)
            {
                this.addMeeting();
                return BX.PreventDefault(e);
            },
            addActivityChangeHandler: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onActivityChangeHandlers.length; i++)
                {
                    if(this._onActivityChangeHandlers[i] == handler)
                    {
                        return;
                    }
                }
                this._onActivityChangeHandlers.push(handler);
            },
            removeActivityChangeHandler: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onActivityChangeHandlers.length; i++)
                {
                    if(this._onActivityChangeHandlers[i] == handler)
                    {
                        this._onActivityChangeHandlers.splice(i, 1);
                        return;
                    }
                }
            },
            reloadItems: function()
            {
                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        action: 'get_activities',
                        ownertype: this.getOwnerType(),
                        ownerid: this.getOwnerId()
                    }
                );
                var self = this;
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'GET_ACTIVITIES',
                                'OWNER_TYPE': this.getOwnerType(),
                                'OWNER_ID': this.getOwnerId(),
                                'COMPLETED': this.getType() === BX.YNSIRActivityEditorType.history ? 1 : 0
                            },
                        onsuccess: function(data)
                        {
                            self.setItems(data['DATA']['ITEMS'], false);
                        },
                        onfailure: function(data)
                        {
                        }
                    }
                );
            },
            isLocked: function()
            {
                return this._isLocked;
            },
            setLocked: function(locked)
            {
                this._isLocked = !!locked;
            },
            getLockMessage: function()
            {
                return this._lockMessage;
            },
            setLockMessage: function(message)
            {
                this._lockMessage = BX.type.isNotEmptyString(message) ? message : "";
            },
            lockAndRelease: function(message)
            {
                if(BX.type.isNotEmptyString(message))
                {
                    this.setLockMessage(message);
                }
                this.setLocked(true);
                this.release();
            },
            showLockMessage: function()
            {
                if(this._lockMessage !== "")
                {
                    BX.NotificationPopup.show("activity_editor_locked", { messages: [ this._lockMessage ], timeout: 1000 });
                }
            },
            handleAddEmailClick: function(e)
            {
                this.addEmail();
                return BX.PreventDefault(e);
            },
            handleShowAllClick: function(e)
            {
                BX.PreventDefault(e);
                this.showAll();
            },
            _handleActivitySave: function(source, params)
            {
                if(!params)
                {
                    return;
                }

                var settings = params['ACTIVITY'];
                if(!settings)
                {
                    return;
                }

                this._handleActivityChange(settings);
                this._notifyActivityChange((source && parseInt(source.getId()) > 0) ? 'UPDATE' : 'CREATE',  settings, true);
            },
            _handleActivityDialogClose: function(source)
            {
                source.removeOnSave(this._saveHandler);
                source.removeOnDialogClose(this._dlgCloseHandler);

                var buttonId = source.getButtonId();

                var item = this.getItemById(source.getId());
                if(!item)
                {
                    return;
                }

                var itemSettings = item.getSettings();

                // Process instant editor mode
                if(source.getMode() === BX.YNSIRDialogMode.view)
                {
                    if(buttonId === BX.YNSIRActivityDialogButton.edit)
                    {
                        // 'markChanged' for enable deffered notification after edit dialog close
                        this.openActivityDialog(BX.YNSIRDialogMode.edit, item.getId(), { 'markChanged': source.isChanged() });
                    }
                    else if(source.isChanged())
                    {
                        this._handleActivityChange(itemSettings);
                        this._notifyActivityChange('UPDATE', itemSettings, true);
                    }
                }
                else if(buttonId === BX.YNSIRActivityDialogButton.cancel && source.isChanged()) //source.getMode() === BX.YNSIRDialogMode.edit
                {
                    // Process deffered notification
                    this._handleActivityChange(itemSettings);
                    this._notifyActivityChange('UPDATE', itemSettings, true);
                }
            },
            _handleActivityChange: function(settings)
            {
                var id = typeof(settings['ID']) != 'undefined' ? parseInt(settings['ID']) : 0;
                var curInd = id > 0 ? this.getItemIndexById(id) : null;
                var item = curInd >= 0 ? this._items[curInd] : null;

                var type = this.getType();
                var itemCompleted = typeof(settings['completed']) != 'undefined' ? settings['completed'] : false;
                if((type === BX.YNSIRActivityEditorType.history && !itemCompleted)
                    || (type === BX.YNSIRActivityEditorType.recent && itemCompleted))
                {
                    this._removeItemByIndex(id > 0 ? this.getItemIndexById(id) : -1);
                    return;
                }

                settings['enableUI'] = this.isUIEnabled();
                if(!this.isUIEnabled())
                {
                    if(item)
                    {
                        item.setSettings(settings);
                    }
                    else
                    {
                        item = BX.YNSIRActivity.create(settings, null, this);
                        this._items.push(item);
                    }
                }
                else
                {
                    //show all before add row
                    this.showAll();

                    var table = BX.findChild(this.getContainer(), { 'tag':'table', 'class':'crm-activity-table' }, true, false);
                    if(!table)
                    {
                        return;
                    }

                    this.showHeading(true);

                    //var tbody = BX.findChild(table, { tagName: 'tbody' }, true, false);
                    var tbody = table.tBodies[0];

                    var itemRow = null;
                    var index = 0;
                    if(item)
                    {
                        this._items.splice(curInd, 1);
                        item.setSettings(settings);
                        index = this._calculateItemIndex(item);

                        if(this._items.length > 0 && index < this._items.length)
                        {
                            this._items.splice(index, 0, item);
                        }
                        else
                        {
                            this._items.push(item);
                        }

                        itemRow = item.getRow();

                        tbody.removeChild(itemRow);

                        if(tbody.rows.length > 0 && index < tbody.rows.length)
                        {
                            tbody.insertBefore(itemRow, tbody.rows[index]);
                        }
                        else
                        {
                            tbody.appendChild(itemRow);
                        }
                        item.layout();
                    }
                    else
                    {
                        item = BX.YNSIRActivity.create(settings, null, this);
                        index = this._calculateItemIndex(item);

                        if(index < this._items.length)
                        {
                            this._items.splice(index, 0, item);
                        }
                        else
                        {
                            this._items.push(item);
                        }

                        itemRow = tbody.insertRow(index < tbody.rows.length ? index : -1);
                        itemRow.className = 'crm-activity-row';

                        item.setRow(itemRow);
                        item.layout();
                    }

                    this.showHint(this._items.length === 0);
                    this._syncRows();
                }
            },
            _handleActivityDelete: function(settings)
            {
                var id = typeof(settings['ID']) != 'undefined' ? parseInt(settings['ID']) : 0;
                this._removeItemByIndex(id > 0 ? this.getItemIndexById(id) : -1);
            },
            _removeItemByIndex: function(itemInd)
            {
                var item = itemInd >= 0 ? this._items[itemInd] : null;
                if(!item)
                {
                    return;
                }

                this._items.splice(itemInd, 1);

                if(this.isUIEnabled())
                {
                    item.cleanLayout();

                    this.showHeading(this._items.length > 0);
                    this.showHint(this._items.length === 0);
                    this.showAll();
                    this._syncRows();
                }
            },
            _calculateItemIndex: function(item)
            {
                var result = this._items.length;

                var deadline = item.getDeadline();
                var curDeadline, curSubj;
                if(deadline !== null)
                {
                    for(var i = 0; i < this._items.length; i++)
                    {
                        curItem = this._items[i];
                        curDeadline = curItem.getDeadline();
                        if(curDeadline === null)
                        {
                            continue;
                        }

                        var diff = deadline.getTime() - curDeadline.getTime();
                        if(Math.abs(diff) < 1000)
                        {
                            diff = 0;
                        }

                        if(diff > 0 || (diff === 0 && parseInt(item.getId()) > parseInt(curItem.getId())))
                        {
                            continue;
                        }

                        result = i;
                        break;
                    }
                }
                else
                {
                    var subj = item.getSubject();
                    for(var j = 0; j < this._items.length; j++)
                    {
                        curSubj = this._items[j].getSubject();
                        curDeadline = this._items[j].getDeadline();
                        if(curDeadline !== null || subj < curSubj)
                        {
                            result = j;
                            break;
                        }
                    }
                }
                return result;
            },
            _syncRows: function()
            {
                var table = BX.findChild(this.getContainer(), { 'tag':'table', 'class':'crm-activity-table' }, true, false);
                if(!table)
                {
                    return;
                }

                var tbody = table.tBodies[0];

                for(var i = 0; i < tbody.rows.length; i++)
                {
                    if((i + 1) % 2 === 0)
                    {
                        BX.addClass(tbody.rows[i], 'crm-activity-row-even');
                    }
                    else
                    {
                        BX.removeClass(tbody.rows[i], 'crm-activity-row-even');
                    }
                }
            },
            _findTaskItem: function(taskId)
            {
                for(var i = 0; i < this._items.length; i++)
                {
                    var item = this._items[i];
                    if(parseInt(item.getSetting('typeID', 0)) === BX.YNSIRActivityType.task && parseInt(item.getSetting('associatedEntityID', 0)) === taskId)
                    {
                        return item;
                    }
                }

                return null;
            },
            _handleTaskAdd: function(taskId)
            {
                if(this.getType() === BX.YNSIRActivityEditorType.history || !this.isTaskTracingEnabled())
                {
                    return;
                }

                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        action: 'get_task',
                        ownertype: this.getSetting('ownerType', ''),
                        ownerid: this.getSetting('ownerID', ''),
                        taskid: taskId
                    }
                );
                var self = this;
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'GET_TASK',
                                'OWNER_TYPE': this.getSetting('ownerType', ''),
                                'OWNER_ID': this.getSetting('ownerID', ''),
                                'TASK_ID': taskId
                            },
                        onsuccess: function(data)
                        {
                            self._handleActivitySave(null, data);
                        },
                        onfailure: function(data)
                        {
                        }
                    }
                );
            },
            _handleTaskChange: function(taskId)
            {
                if(!this.isTaskTracingEnabled())
                {
                    return;
                }

                var taskItem = this._findTaskItem(taskId);
                if(!taskItem)
                {
                    return;
                }

                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        id: this.getSetting('ID', '0'),
                        action: 'get_task',
                        ownertype: this.getSetting('ownerType', ''),
                        ownerid: this.getSetting('ownerID', ''),
                        taskid: taskId
                    }
                );
                var self = this;
                window.setTimeout(
                    function()
                    {
                        BX.ajax(
                            {
                                'url': serviceUrl,
                                'method': 'POST',
                                'dataType': 'json',
                                'data':
                                    {
                                        'ACTION' : 'GET_TASK',
                                        'ITEM_ID': self.getSetting('ID', '0'),
                                        'OWNER_TYPE': self.getSetting('ownerType', ''),
                                        'OWNER_ID': self.getSetting('ownerID', '0'),
                                        'TASK_ID': taskId
                                    },
                                onsuccess: function(data)
                                {
                                    self._handleActivitySave(null, data);
                                },
                                onfailure: function(data)
                                {
                                }
                            }
                        );
                    },
                    300
                );
            },
            _handleTaskDelete: function(taskId)
            {
                var taskItem = this._findTaskItem(taskId);
                if(taskItem)
                {
                    taskItem.remove(true);
                    this._notifyActivityChange('DELETE',  taskItem.getSettings(), true);
                }
            },
            _notifyActivityChange: function(action, settings, push, editor)
            {
                if(!editor)
                {
                    editor = this;
                }

                this._notify(this._onActivityChangeHandlers, [ this, action, settings, editor ]);
                if(!!push)
                {
                    BX.YNSIRActivityEditor.notifyActivityChange(this, action, settings);
                }
            },
            _notify: function(handlers, eventArgs)
            {
                var ary = [];
                for(var i = 0; i < handlers.length; i++)
                {
                    ary.push(handlers[i]);
                }

                for(var j = 0; j < ary.length; j++)
                {
                    try
                    {
                        ary[j].apply(this, eventArgs ? eventArgs : []);
                    }
                    catch(ex)
                    {
                    }
                }
            },
            handleExternalActivityChange: function(editor, action, settings)
            {
                if(editor == this)
                {
                    return;
                }

                var enableUI = this.isUIEnabled();
                var id = typeof(settings['ID']) != 'undefined' ? parseInt(settings['ID']) : 0;

                if(action === 'DELETE')
                {
                    this._handleActivityDelete(settings);
                    this._notifyActivityChange(action,  settings, false, editor);
                }
                else if(action === 'CREATE' || action === 'UPDATE')
                {
                    this._handleActivityChange(settings);
                    this._notifyActivityChange(action,  settings, false, editor);
                }
            },
            getWebDavElementInfo: function(elementId, callback)
            {
                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        action: 'get_webdav_element_info',
                        elementid: elementId
                    }
                );
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'GET_WEBDAV_ELEMENT_INFO',
                                'ELEMENT_ID': elementId
                            },
                        onsuccess: function(data)
                        {
                            var innerData = data['DATA'] ? data['DATA'] : {};
                            if(BX.type.isFunction(callback))
                            {
                                try
                                {
                                    callback(innerData['INFO'] ? innerData['INFO'] : {});
                                }
                                catch(e)
                                {
                                }
                            }
                        },
                        onfailure: function(data)
                        {
                        }
                    }
                );
            },
            prepareWebDavUploader: function(name, mode, vals)
            {
                name = BX.type.isNotEmptyString(name) ? name : 'activity_uploader';

                var uploader = typeof(BX.YNSIRWebDavUploader.items[name]) !== 'undefined'
                    ? BX.YNSIRWebDavUploader.items[name] : null;

                if(uploader)
                {
                    uploader.cleanLayout();
                }
                else
                {
                    uploader = BX.YNSIRWebDavUploader.create(
                        name,
                        {
                            'urlSelect': this.getSetting('webDavSelectUrl', ''),
                            'urlUpload': this.getSetting('webDavUploadUrl', ''),
                            'urlShow': this.getSetting('webDavShowUrl', ''),
                            'elementInfoLoader': BX.delegate(this.getWebDavElementInfo, this),
                            'msg' :
                                {
                                    'loading' : BX.YNSIRActivityEditor.getMessage('webdavFileLoading', 'Loading...'),
                                    'file_exists': BX.YNSIRActivityEditor.getMessage('webdavFileAlreadyExists', 'File already exists!'),
                                    'access_denied':"<p style='margin-top:0;'>" + BX.YNSIRActivityEditor.getMessage('webdavFileAccessDenied', 'Access denied!') + "</p>",
                                    'title': BX.YNSIRActivityEditor.getMessage('webdavTitle', 'Files'),
                                    'attachFile': BX.YNSIRActivityEditor.getMessage('webdavAttachFile', 'Attach file'),
                                    'dragFile': BX.YNSIRActivityEditor.getMessage('webdavDragFile', 'Drag a files to this area'),
                                    'selectFile': BX.YNSIRActivityEditor.getMessage('webdavSelectFile', 'or select a file in your computer'),
                                    'selectFromLib': BX.YNSIRActivityEditor.getMessage('webdavSelectFromLib', 'Select from library'),
                                    'loadFiles': BX.YNSIRActivityEditor.getMessage('webdavLoadFiles', 'Load files')
                                }
                        }
                    )
                }

                uploader.setMode(mode);
                uploader.setValues(vals);

                var container = BX.create('DIV',  { 'attrs': { 'class': 'bx-crm-dialog-activity-webdav-container' } });
                uploader.layout(container);
                return container;
            },
            prepareDiskUploader: function(name, mode, vals)
            {
                // name = BX.type.isNotEmptyString(name) ? name : 'activity_uploader';
                //
                // var uploader = typeof(BX.YNSIRDiskUploader.items[name]) !== 'undefined'
                //     ? BX.YNSIRDiskUploader.items[name] : null;
                //
                // if(uploader)
                // {
                //     uploader.show(false);
                //     uploader.removeAllItems();
                //     uploader.cleanLayout();
                // }
                // else
                // {
                //     uploader = BX.YNSIRDiskUploader.create(
                //         name,
                //         {
                //             msg :
                //                 {
                //                     'diskAttachFiles' : BX.YNSIRActivityEditor.getMessage('diskAttachFiles', ''),
                //                     'diskAttachedFiles' : BX.YNSIRActivityEditor.getMessage('diskAttachedFiles', ''),
                //                     'diskSelectFile' : BX.YNSIRActivityEditor.getMessage('diskSelectFile', ''),
                //                     'diskSelectFileLegend' : BX.YNSIRActivityEditor.getMessage('diskSelectFileLegend', ''),
                //                     'diskUploadFile' : BX.YNSIRActivityEditor.getMessage('diskUploadFile', ''),
                //                     'diskUploadFileLegend' : BX.YNSIRActivityEditor.getMessage('diskUploadFileLegend', '')
                //                 }
                //         }
                //     )
                // }
                //
                // uploader.setMode(mode);
                // uploader.setValues(vals);
                //
                // var container = BX.create('DIV',  { 'attrs': { 'class': 'bx-crm-dialog-activity-webdav-container' } });
                // uploader.layout(container);
                // return container;
            },
            prepareFileList: function(data, prefix)
            {
                var container = BX.create(
                    'DIV',
                    {
                        attrs: { className: 'bx-crm-dialog-view-activity-files' }
                    }
                );

                if(!(BX.type.isArray(data) && data.length > 0))
                {
                    return container;
                }

                if(!BX.type.isString(prefix))
                {
                    prefix = '';
                }

                for(var i = 0; i < data.length; i++)
                {
                    var item = data[i];
                    var fileId = typeof(item['id']) !== 'undefined' ? item['id'].toString() : '';
                    if(fileId === '')
                    {
                        fileId = Math.random().toString().substring(2);
                    }

                    var fileContainerId = 'File' + fileId;
                    if(prefix !== '')
                    {
                        fileContainerId = prefix + fileContainerId;
                    }
                    item['containerId'] = fileContainerId;

                    container.appendChild(
                        BX.create(
                            'DIV',
                            {
                                'attrs': { id: fileContainerId, className: 'bx-crm-dialog-view-activity-file' },
                                'children':
                                    [
                                        BX.create(
                                            'SPAN',
                                            {
                                                'attrs': { className: 'bx-crm-dialog-view-activity-file-num' },
                                                'text': (i + 1).toString()
                                            }
                                        ),
                                        BX.create(
                                            'A',
                                            {
                                                'attrs':
                                                    {
                                                        'className': 'bx-crm-dialog-view-activity-file-text',
                                                        'target': '_blank',
                                                        'href': item['url']
                                                    },
                                                'text': item['name']
                                            }
                                        )
                                    ]
                            }
                        )
                    );
                }

                return container;
            },
            prepareFileUploader: function(controlId, containerId, vals)
            {
                if(BX.CFileInput.Items[controlId])
                {
                    BX.CFileInput.Items[controlId].setFiles(vals);
                }

                var container = BX(containerId);
                if(container)
                {
                    container.style.display = '';
                }
                return container;
            },
            getWebDavUploaderValues: function(name)
            {
                var result = [];

                var uploader = BX.YNSIRWebDavUploader.items[name];
                var elements = uploader ? uploader.getValues() : [];
                for(var i = 0; i < elements.length; i++)
                {
                    result.push(elements[i]['ID']);
                }

                return result;
            },
            getDiskUploaderValues: function(name)
            {
                // var uploader = BX.YNSIRDiskUploader.items[name];
                return /*uploader ? uploader.getFileIds() :*/ [];
            },
            getFileUploaderValues: function(files)
            {
                var result = [];
                if(BX.type.isElementNode(files))
                {
                    result.push(files.value);
                }
                else if(BX.type.isArray(files) || typeof(files.length) !== 'undefined')
                {
                    for(var i = 0; i < files.length; i++)
                    {
                        result.push(files[i].value);
                    }
                }

                return result;
            },
            hideClock: function(elemID)
            {
                var clock = BX(elemID);
                if(clock)
                {
                    clock.style.display = 'none';
                    this.getServiceContainer().appendChild(clock);
                }
            },
            hideUploader: function(elemId, controlId)
            {
                var upload = BX(elemId);
                if(upload)
                {
                    upload.style.display = 'none';
                    this.getServiceContainer().appendChild(upload);
                }

                if(BX.CFileInput && BX.CFileInput.Items && BX.CFileInput.Items[controlId])
                {
                    BX.CFileInput.Items[controlId].Clear();
                }
            },
            createOwnershipSelector: function(name, changeButton)
            {
                var data = this.getSetting('ownershipSelectorData');
                if(!data)
                {
                    return null;
                }

                return CRM.Set(
                    changeButton,
                    'test',
                    '',
                    BX.type.isArray(data['items']) ? data['items'] : [],
                    false,
                    false,
                    ['deal'],
                    data['messages'] ? data['messages'] : {},
                    true
                );
            },
            getCommunicationHtml: function(type, value, callback)
            {
                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        action: 'get_communication_html'
                    }
                );
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'GET_COMMUNICATION_HTML',
                                'TYPE_NAME': type,
                                'VALUE': value
                            },
                        onsuccess: function(data)
                        {
                            var innerData = data['DATA'] ? data['DATA'] : {};
                            if(BX.type.isFunction(callback))
                            {
                                try
                                {
                                    callback(innerData['HTML'] ? innerData['HTML'] : {});
                                }
                                catch(e)
                                {
                                }
                            }
                        },
                        onfailure: function(data)
                        {
                        }
                    }
                );
            },
            getActivityCommunications: function(id, callback)
            {
                var enableCallback = BX.type.isFunction(callback);
                var item = this.getItemById(id);
                if(!item)
                {
                    if(enableCallback)
                    {
                        try
                        {
                            callback([]);
                        }
                        catch(e)
                        {
                        }
                    }
                    return;
                }

                if(item.getSetting('communicationsLoaded', true))
                {
                    if(enableCallback)
                    {
                        try
                        {
                            callback(item.getSetting('communications', []));
                        }
                        catch(e)
                        {
                        }
                    }
                    return;
                }

                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        id: id,
                        action: 'get_activity_communications'
                    }
                );
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'GET_ACTIVITY_COMMUNICATIONS',
                                'ID': id
                            },
                        onsuccess: function(responseData)
                        {
                            var data = typeof(responseData['ACTIVITY_COMMUNICATIONS']) !== 'undefined'
                            && typeof(responseData['ACTIVITY_COMMUNICATIONS']['DATA']) !== 'undefined'
                                ? responseData['ACTIVITY_COMMUNICATIONS']['DATA'] : {};

                            var communications = data['COMMUNICATIONS'] ? data['COMMUNICATIONS'] : [];

                            item.setSetting('communicationsLoaded', true);
                            item.setSetting('communications', communications);

                            if(enableCallback)
                            {
                                try
                                {
                                    callback(communications);
                                }
                                catch(e)
                                {
                                }
                            }
                        },
                        onfailure: function(data)
                        {
                        }
                    }
                );
            },
            getActivityCommunicationsPage: function(id, pageSize, pageNumber, callback)
            {
                var enableCallback = BX.type.isFunction(callback);

                var item = this.getItemById(id);
                if(!item)
                {
                    if(enableCallback)
                    {
                        try
                        {
                            callback([]);
                        }
                        catch(e)
                        {
                        }
                    }
                    return;
                }

                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        id: id,
                        action: 'get_activity_communications_page'
                    }
                );
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'GET_ACTIVITY_COMMUNICATIONS_PAGE',
                                'ID': id,
                                'PAGE_SIZE': pageSize,
                                'PAGE_NUMBER': pageNumber
                            },
                        onsuccess: function(responseData)
                        {
                            var data = typeof(responseData['ACTIVITY_COMMUNICATIONS_PAGE']) !== 'undefined'
                            && typeof(responseData['ACTIVITY_COMMUNICATIONS_PAGE']['DATA']) !== 'undefined'
                                ? responseData['ACTIVITY_COMMUNICATIONS_PAGE']['DATA'] : {};

                            var communications = data['COMMUNICATIONS'] ? data['COMMUNICATIONS'] : [];
                            pageSize = data['PAGE_SIZE'] ? data['PAGE_SIZE'] : pageSize;
                            pageNumber = data['PAGE_NUMBER'] ? data['PAGE_NUMBER'] : pageNumber;
                            var pageCount = data['PAGE_COUNT'] ? data['PAGE_COUNT'] : 1;

                            if(enableCallback)
                            {
                                try
                                {
                                    callback(communications, pageSize, pageNumber, pageCount);
                                }
                                catch(e)
                                {
                                }
                            }
                        },
                        onfailure: function(data)
                        {
                        }
                    }
                );
            },
            getActivityViewData: function(params, callback)
            {
                if(!BX.type.isFunction(callback))
                {
                    return;
                }

                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        action: 'get_activity_view_data'
                    }
                );
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'GET_ACTIVITY_VIEW_DATA',
                                'PARAMS': params
                            },
                        onsuccess: function(responseData)
                        {
                            try
                            {
                                callback(responseData);
                            }
                            catch(e)
                            {
                            }
                        },
                        onfailure: function(data)
                        {
                        }
                    }
                );
            }
        };
    BX.YNSIRActivityEditor._default = null;
    BX.YNSIRActivityEditor.getDefault = function()
    {
        return this._default;
    };
    BX.YNSIRActivityEditor.setDefault = function(editor)
    {
        this._default = editor;
    };
    BX.YNSIRActivityEditor.items = {};
    BX.YNSIRActivityEditor.create = function(id, settings, items)
    {
        var self = new BX.YNSIRActivityEditor();
        id = self.initialize(id, settings, items);
        this.items[id] = self;
        if(!this._default)
        {
            this._default = self;
        }
        return self;
    };
    BX.YNSIRActivityEditor.setActivities = function(editorId, activities, notify)
    {
        var editor = this.items[editorId];
        if(!editor)
        {
            return;
        }

        editor.setItems(activities, notify);

        var ownerType = editor.getOwnerType();
        var ownerId = editor.getOwnerId();

        for(var id in this.items)
        {
            if(id == editorId)
            {
                continue;
            }

            var curEditor = this.items[id];
            if(curEditor.getOwnerType() == ownerType && curEditor.getOwnerId() == ownerId)
            {
                curEditor.reloadItems();
            }
        }
    };
    BX.YNSIRActivityEditor._copyObject = function(obj)
    {
        if (obj == null || obj == undefined || typeof(obj) !== 'object')
        {
            return obj;
        }

        var copy = obj.constructor();
        for (var attr in obj)
        {
            if (obj.hasOwnProperty(attr))
            {
                copy[attr] = obj[attr];
            }
        }
        return copy;
    };
    BX.YNSIRActivityEditor.notifyActivityChange = function(editor, action, settings)
    {
        for(var id in this.items)
        {
            if(!this.items.hasOwnProperty(id))
            {
                continue;
            }

            var curEditor = this.items[id];
            if(curEditor.getId() === editor.getId())
            {
                continue;
            }

            curEditor.handleExternalActivityChange(editor, action, this._copyObject(settings));
        }
    };
    BX.YNSIRActivityEditor.addTask = function()
    {
        if(this._default)
        {
            this._default.addTask();
        }
    };
    BX.YNSIRActivityEditor.addCall = function()
    {
        if(this._default)
        {
            this._default.addCall();
        }
    };
    BX.YNSIRActivityEditor.addMeeting = function()
    {
        if(this._default)
        {
            this._default.addMeeting();
        }
    };
    BX.YNSIRActivityEditor.addEmail = function(settings)
    {
        if(this._default)
        {
            this._default.addEmail(settings);
        }
    };
    BX.YNSIRActivityEditor.display = function(id, display)
    {
        if(typeof(this.items[id]) != 'undefined')
        {
            this.items[id].display(display);
        }
    };
    BX.YNSIRActivityEditor.prepareDialogTitle = function(text, nodes)
    {
        var element = BX.create(
            'DIV',
            {
                attrs: { className: 'bx-crm-dialog-tittle-wrap' },
                children:
                    [
                        BX.create(
                            'SPAN',
                            {
                                text: text,
                                props: { className: 'bx-crm-dialog-title-text' }
                            }
                        )
                    ]
            }
        );

        if(BX.type.isArray(nodes) && nodes.length > 0)
        {
            for(var i = 0; i < nodes.length; i++)
            {
                element.appendChild(nodes[i]);
            }
        }

        return element;
    };
    BX.YNSIRActivityEditor.prepareDialogButtons = function(data)
    {
        var result = [];
        for(var i = 0; i < data.length; i++)
        {
            var datum = data[i];
            result.push(
                datum['type'] === 'link'
                    ? new BX.PopupWindowButtonLink(datum['settings'])
                    : new BX.PopupWindowButton(datum['settings']));
        }

        return result;
    };
    BX.YNSIRActivityEditor.prepareDialogCell = function(row, data)
    {
        var cell = row.insertCell(-1);

        if(data['children'])
        {
            this.appendChild(cell, data['children']);
        }

        BX.adjust(
            cell,
            {
                attrs: data['attrs'] ? data['attrs'] : {},
                props: data['props'] ? data['props'] : {}
            }
        );

        return cell;
    };
    BX.YNSIRActivityEditor.prepareDialogRow = function(tab, data)
    {
        var r = tab.insertRow(-1);

        if(data['skipTitle'] !== true)
        {
            if(data['headerCell'])
            {
                var headerData = data['headerCell'];

                if(!headerData['attrs'])
                {
                    headerData['attrs'] = {};
                }

                if(!BX.type.isNotEmptyString(headerData['attrs']['className']))
                {
                    headerData['attrs']['className'] = 'bx-crm-dialog-activity-table-left';
                }

                this.prepareDialogCell(r, headerData);
            }
            else
            {
                this.prepareDialogCell(
                    r,
                    {
                        attrs: { className: 'bx-crm-dialog-activity-table-left' },
                        children: [ data['title'] ? data['title'] : ' ' ]
                    }
                );
            }
        }

        if(BX.type.isArray(data['contentCells']))
        {
            for(var i = 0; i < data['contentCells'].length; i++)
            {
                var contentData = data['contentCells'][i];
                if(!contentData['attrs'])
                {
                    contentData['attrs'] = {};
                }

                if(!BX.type.isNotEmptyString(contentData['attrs']['className']))
                {
                    contentData['attrs']['className'] = 'bx-crm-dialog-activity-table-right';
                }

                this.prepareDialogCell(r, contentData);
            }
        }
        else if(data['content'])
        {
            var attrs = { className: 'bx-crm-dialog-activity-table-right' };
            if(data['skipTitle'])
            {
                attrs['colspan'] = 2;
            }
            this.prepareDialogCell(
                r,
                {
                    attrs: attrs,
                    children: BX.type.isArray(data['content']) ? data['content'] : [ data['content'] ]
                }
            );
        }
    };
    BX.YNSIRActivityEditor.appendChild = function(parent, child)
    {
        if(!BX.type.isElementNode(parent))
        {
            return;
        }

        if(BX.type.isArray(child))
        {
            for(var i = 0; i < child.length; i++)
            {
                this.appendChild(parent, child[i]);
            }
        }
        else if(BX.type.isDomNode(child))
        {
            parent.appendChild(child);
        }
        else if(BX.type.isNotEmptyString(child))
        {
            parent.appendChild(document.createTextNode(child));
        }
    };
    BX.YNSIRActivityEditor.insertAfter = function(parentNode, newElement, referenceElement)
    {
        var beforeElem = null;
        var found = false;

        for (var i = 0; i <= parentNode.childNodes.length; i++)
        {
            if (found) {
                beforeElem = parentNode.childNodes[i];
                break;
            }

            if (parentNode.childNodes[i] == referenceElement) {
                found = true;
            }
        }

        if (beforeElem != null) {
            parentNode.insertBefore(newElement, beforeElem);
        }
        else if (found) {
            parentNode.appendChild(newElement);
        }

        return newElement;
    };
    BX.YNSIRActivityEditor.findDialogElement = function(cfg, alias)
    {
        if(!cfg)
        {
            return null;
        }

        var code = typeof(cfg[alias]) != 'undefined' ? cfg[alias] : '';
        if(!BX.type.isNotEmptyString(code))
        {
            return null;
        }

        var result = BX(code);

        if(result)
        {
            return result;
        }

        var form = document.forms[cfg['form']];
        if(form)
        {
            try
            {
                result = form.elements[code];
            }
            catch(e)
            {
            }
        }
        return result;
    };
    BX.YNSIRActivityEditor.findDialogElements = function(cfg, name)
    {
        if(!cfg)
        {
            return [];
        }

        var form = document.forms[cfg['form']];
        if(!form || !form.elements[name])
        {
            return [];
        }

        return form.elements[name];
    };
    BX.YNSIRActivityEditor.getJSObject = function(settings, name, parent)
    {
        if(!parent)
        {
            parent = window;
        }

        var v = typeof(settings[name]) != 'undefined' ? settings[name] : '';
        return BX.type.isNotEmptyString(v) && typeof(parent[v]) != 'undefined' ? parent[v] : null;
    };
    BX.YNSIRActivityEditor.hideUploader = function(elemId, controlId)
    {
        var upload = BX(elemId);
        if(upload)
        {
            upload.style.display = 'none';
            document.body.appendChild(upload);
        }

        if(BX.CFileInput && BX.CFileInput.Items && BX.CFileInput.Items[controlId])
        {
            BX.CFileInput.Items[controlId].Clear();
        }
    };
    BX.YNSIRActivityEditor.hideClock = function(elemID)
    {
        var clock = BX(elemID);
        if(clock)
        {
            clock.style.display = 'none';
            document.body.appendChild(clock);
        }
    };
    BX.YNSIRActivityEditor.hideLhe = function(containerID)
    {
        var lheContainer = BX(containerID);
        if(lheContainer)
        {
            lheContainer.style.display = 'none';
            document.body.appendChild(lheContainer);
        }
    };
    BX.YNSIRActivityEditor.resolvePriorityClassName = function(priority, readOnly)
    {
        priority = parseInt(priority);
        readOnly = !!readOnly;

        var className = 'bx-crm-dialog-priority-text';
        if(priority === BX.YNSIRActivityPriority.high)
        {
            className += ' bx-crm-dialog-priority-text-high';
        }
        else if(priority === BX.YNSIRActivityPriority.medium)
        {
            className += ' bx-crm-dialog-priority-text-medium';
        }
        else if(priority === BX.YNSIRActivityPriority.low)
        {
            className += ' bx-crm-dialog-priority-text-low';
        }

        if(readOnly)
        {
            className += ' bx-crm-dialog-priority-read-only-text';
        }

        return className;
    };
    BX.YNSIRActivityEditor.getDateTimeFormat = function()
    {
        var f = BX.message('FORMAT_DATETIME');
        return BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : 'DD.MM.YYYY HH:MI:SS');
    };
    BX.YNSIRActivityEditor.getDateFormat = function()
    {
        var f = BX.message('FORMAT_DATE');
        return BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : 'DD.MM.YYYY');
    };
    BX.YNSIRActivityEditor.getTimeFormat = function()
    {
        var dt = BX.message('FORMAT_DATETIME');
        var d = BX.message('FORMAT_DATE');
        var dIndex = dt.indexOf(d);

        var t = dt;
        if(dIndex >= 0)
        {
            t = dt.replace(new RegExp("[\\s]*" + d + "[\\s]*"), '');
        }
        return BX.date.convertBitrixFormat(BX.type.isNotEmptyString(t) ? t : 'HH:MI:SS');
    };
    BX.YNSIRActivityEditor.joinDateTime = function(date, time)
    {
        var dt = BX.message('FORMAT_DATETIME');
        var d = BX.message('FORMAT_DATE');
        var dIndex = dt.indexOf(d);

        var t = dt;
        if(dIndex >= 0)
        {
            t = dt.replace(new RegExp("[\\s]*" + d + "[\\s]*"), '');
        }
        return dt.replace(new RegExp(d), date).replace(new RegExp(t), time);
    };
    BX.YNSIRActivityEditor.trimDateTimeString = function(str)
    {
        var rx = /(\d{2}):(\d{2}):(\d{2})/;
        var ary = rx.exec(str);
        if(!ary || ary.length < 4)
        {
            return str;
        }
        var result = str.substring(0, ary.index) + ary[1] + ':' + ary[2];
        var tailPos = ary.index + 8;
        if(tailPos < str.length)
        {
            result += str.substring(tailPos);
        }
        return result;
    };
    BX.YNSIRActivityEditor.loadClock = function(clockInputID)
    {
        var clock = window['bxClock_' + clockInputID];
        if(clock)
        {
            clock.pInput = BX(clockInputID);
            return;
        }

        var clockLoader = window['bxLoadClock_' + clockInputID];
        if(BX.type.isFunction(clockLoader))
        {
            clockLoader(function(obClock){ obClock.pInput = BX(clockInputID); });
        }
    };
    BX.YNSIRActivityEditor.onBeforeHide = function() {};
    BX.YNSIRActivityEditor.onAfterHide = function() {};
    BX.YNSIRActivityEditor.onBeforeShow = function()
    {
    };
    BX.YNSIRActivityEditor.onAfterShow = function() {};
    BX.YNSIRActivityEditor.onPopupTaskAdded = function(task)
    {
        var items = BX.YNSIRActivityEditor.items;
        for(var id in items)
        {
            if(items.hasOwnProperty(id))
            {
                items[id]._handleTaskAdd(task['id']);
            }
        }
    };
    BX.YNSIRActivityEditor.onPopupTaskChanged = function(task)
    {
        var items = BX.YNSIRActivityEditor.items;
        for(var id in items)
        {
            if(items.hasOwnProperty(id))
            {
                items[id]._handleTaskChange(task['id']);
            }
        }
    };

    BX.YNSIRActivityEditor.invalidateTaskPopupOverlay = function()
    {
        if(!BX.YNSIRActivityEditor._tryToRefreshTaskPopupOverlay())
        {
            return;
        }

        window.setTimeout(
            function() { BX.YNSIRActivityEditor._tryToRefreshTaskPopupOverlay(); },
            300
        );

        window.setTimeout(
            function() { BX.YNSIRActivityEditor._tryToRefreshTaskPopupOverlay(); },
            600
        );

        window.setTimeout(
            function() { BX.YNSIRActivityEditor._tryToRefreshTaskPopupOverlay(); },
            1200
        );
    };

    BX.YNSIRActivityEditor.invalidateTaskPopupOverlayCallback = null;
    BX.YNSIRActivityEditor.attachInterfaceGridReload = function()
    {
        if(!this.invalidateTaskPopupOverlayCallback)
        {
            this.invalidateTaskPopupOverlayCallback = BX.delegate(this.invalidateTaskPopupOverlay, this);
            BX.addCustomEvent(window, 'BXInterfaceGridAfterReload', this.invalidateTaskPopupOverlayCallback);
        }
    };

    BX.YNSIRActivityEditor._tryToRefreshTaskPopupOverlay = function()
    {
        if(typeof(window.top.BX.TasksIFrameInst) === 'undefined')
        {
            return false;
        }

        try
        {
            window.top.BX.TasksIFrameInst.__onWindowResize();
            window.top.BX.TasksIFrameInst.__onContentResize();
        }
        catch(e)
        {
            return false;
        }
        return true;
    };

    BX.YNSIRActivityEditor.onPopupTaskDeleted = function(taskId)
    {
        var items = BX.YNSIRActivityEditor.items;
        for(var id in items)
        {
            if(items.hasOwnProperty(id))
            {
                items[id]._handleTaskDelete(taskId);
            }
        }
    };
    BX.YNSIRActivityEditor.parseEmail = function(email)
    {
        var rx = /([^<]+)<\s*([^>]+)\s*>/;
        var ary = rx.exec(email);
        return ary ? { name: ary[1], address: ary[2] } : { name: '', address: email };
    };
    BX.YNSIRActivityEditor.validateEmail = function(email)
    {
        var rx = /^.*[<]?\s*[\w\-\+_]+(\.[\w\-\+_]+)*@[\w\-\+_]+\.[\w\-\+_]+(\.[\w\-\+_]+)*\s*[>]?$/;
        return rx.test(email);
    };
    BX.YNSIRActivityEditor.validatePhone = function(phone)
    {
        var rx = /^\s*\+?[\d-\s\(\)]+\s*$/;
        return rx.test(phone);
    };
    BX.YNSIRActivityEditor.getMessage = function(name, defaultval)
    {
        return typeof(this.messages) !== 'undefined' && this.messages[name] ? this.messages[name] : defaultval;
    };
    BX.YNSIRActivityEditor.viewActivity = function(editorId, itemId, options)
    {
        var editor = this.items[editorId];
        if(typeof(editor) !== 'undefined')
        {
            editor.viewActivity(itemId, options);
        }
    };
    BX.YNSIRActivityEditor.createCommunicationSearch = function(id, settings )
    {
        return typeof(BX.YNSIRCommunicationSearch) !== 'undefined'
            ?  BX.YNSIRCommunicationSearch.create(id, settings) : null;
    };
    BX.YNSIRActivityEditor.getDefaultCommunication = function(ownerType, ownerID, communicationType, serviceUrl)
    {
        var commSearch = this.createCommunicationSearch(
            'COMM_SEARCH_' + ownerType + '_' + ownerID + '_' + Math.random().toString().substring(2),
            {
                'entityType' : ownerType,
                'entityId': ownerID,
                'serviceUrl': serviceUrl,
                'communicationType': communicationType,
                'selectCallback': null,
                'enableSearch': false
            }
        );

        return commSearch ? commSearch.getDefaultCommunication() : null;
    };
    BX.YNSIRActivityEditor._isFlvPlayerLoaded = false;
    BX.YNSIRActivityEditor.isFlvPlayerLoaded = function()
    {
        return this._isFlvPlayerLoaded;
    };
    BX.YNSIRActivityEditor.loadFlvPlayer = function()
    {
        if(this._isFlvPlayerLoaded || !BX.type.isNotEmptyString(BX.YNSIRActivityEditor["flashPlayerApiUrl"]))
        {
            return;
        }

        BX.loadScript(BX.YNSIRActivityEditor["flashPlayerApiUrl"],
            function()
            {
                BX.YNSIRActivityEditor._isFlvPlayerLoaded = true;
                window.setTimeout(
                    function() { BX.onCustomEvent(window, "YNSIRActivityEditorFlvPlayerLoaded") },
                    0
                );
            }
        );
    };
    BX.YNSIRActivityEditor.isAudioVideoFile = function(extension)
    {
        if(!BX.type.isNotEmptyString(extension))
        {
            return false;
        }

        extension = extension.toUpperCase();
        return (extension === 'FLV' || extension === 'MP3' || extension === 'MP4' || extension === 'VP6' || extension === 'AAC');
    };

    BX.YNSIRActivityEditor._fileExtRx = /\.([a-z0-9]+)$/i;
    BX.YNSIRActivityEditor.getFileExtension = function(fileName)
    {
        if(!BX.type.isNotEmptyString(fileName))
        {
            return "";
        }

        var m = this._fileExtRx.exec(fileName);
        return (BX.type.isArray(m) && m.length > 1) ? m[1] : "";
    };

    BX.YNSIRDialogMode =
        {
            edit: 1,
            view: 2
        };
    BX.YNSIROwnerTypeAbbr =
        {
            undefined: '',
            lead: 'L',
            deal: 'D',
            contact: 'C',
            company: 'CO',
            resolve: function(name)
            {
                if(name === 'LEAD')
                {
                    return this.lead;
                }
                else if(name === 'DEAL')
                {
                    return this.deal;
                }
                else if(name === 'CONTACT')
                {
                    return this.contact;
                }
                else if(name === 'COMPANY')
                {
                    return this.company;
                }

                return this.undefined;
            }
        };
    BX.YNSIRActivityType =
        {
            undefined: 0,
            meeting: 1,
            call: 2,
            task: 3,
            email: 4,
            activity: 5,
            provider: 6,
            getName: function(id)
            {
                for(var i = 0; i < this._items.length; i++)
                {
                    if(this._items[i]['value'] == id)
                    {
                        return this._items[i]['text'];
                    }
                }
                return '[' + id + ']';
            },
            _items: [],
            getListItems: function()
            {
                return this._items;
            },
            setListItems: function(items)
            {
                this._items = items;
            }
        };
    BX.YNSIRActivityStatus =
        {
            undefined: 0,
            waiting: 1,
            completed: 2,
            getName: function(typeId, id)
            {
                if(BX.type.isArray(this._items[typeId]))
                {
                    var ary = this._items[typeId];
                    for(var i = 0; i < ary.length; i++)
                    {
                        if(ary[i]['value'] == id)
                        {
                            return ary[i]['text'];
                        }
                    }
                }
                return '[' + id + ']';
            },
            _items:{},
            getListItems: function(typeId)
            {
                return BX.type.isArray(this._items[typeId]) ? this._items[typeId] : [];
            },
            setListItems: function(items)
            {
                this._items = items;
            }
        };
    BX.YNSIRActivityNotifyType =
        {
            none: 0,
            min: 1,
            hour: 2,
            day: 3,
            descrTemplate: '',
            getDescription: function(type, value)
            {
                if(type == 0) //this.none
                {
                    return BX.YNSIRActivityEditor.getMessage('no');
                }

                return this.descrTemplate.replace(/%TYPE%/gi, this.getName(type)).replace(/%VALUE%/gi, value);
            },
            getName: function(type)
            {
                if(type == 0) //this.none
                {
                    return BX.YNSIRActivityEditor.getMessage('no');
                }

                for(var i = 0; i < this._items.length; i++)
                {
                    if(this._items[i]['value'] == type)
                    {
                        return this._items[i]['text'];
                    }
                }

                return '[' + type + ']'; // default
            },
            getNext: function(type)
            {
                if(!BX.type.isNumber(type))
                {
                    type = parseInt(type);
                }

                return type < this.day ? (type + 1) : this.min;
            },
            getAllNames: function()
            {
                var ary = [];
                for(var i = 0; i < this._items.length; i++)
                {
                    ary.push(this._items[i]['text']);
                }
                return ary;
            },
            _items: [],
            getListItems: function()
            {
                return this._items;
            },
            setListItems: function(items)
            {
                this._items = items;
            }
        };
    BX.YNSIRActivityPriority =
        {
            none: 0,
            low: 1,
            medium: 2,
            high: 3,
            _items: [],
            getName: function(id)
            {
                for(var i = 0; i < this._items.length; i++)
                {
                    if(this._items[i]['value'] == id)
                    {
                        return this._items[i]['text'];
                    }
                }
                return '[' + id + ']';
            },
            getListItems: function()
            {
                return this._items;
            },
            setListItems: function(items)
            {
                this._items = items;
            }
        };
    BX.YNSIRActivityDirection =
        {
            undefined: 0,
            incoming: 1,
            outgoing: 2,
            getName: function(typeId, id)
            {
                if(BX.type.isArray(this._items[typeId]))
                {
                    var ary = this._items[typeId];
                    for(var i = 0; i < ary.length; i++)
                    {
                        if(ary[i]['value'] == id)
                        {
                            return ary[i]['text'];
                        }
                    }
                }
                return '[' + id + ']';
            },
            _items:[],
            getListItems: function(typeId)
            {
                return BX.type.isArray(this._items[typeId]) ? this._items[typeId] : [];
            },
            setListItems: function(items)
            {
                this._items = items;
            }
        };
    BX.YNSIRActivityDialogButton =
        {
            undefined: 0,
            ok: 1,
            cancel: 2,
            edit: 3,
            save: 4
        };
    BX.YNSIRActivity = function()
    {
        this._viewMode = true;
        this._settings = {};
        this._row = this._editor = null;
    };
    BX.YNSIRActivity.prototype =
        {
            initialize: function(settings, row, editor)
            {
                this._settings = settings ? settings : {};
                this._editor = editor;
                this.setRow(row);
            },
            isUIEnabled: function()
            {
                return this.getSetting('enableUI', true);
            },
            remove: function(skipConfirmation)
            {
                if(this._editor.deleteActivity(this.getSetting('ID'), skipConfirmation))
                {
                    this.cleanLayout();
                }
            },
            handleDeleteClick:function (e)
            {
                BX.PreventDefault(e);
                this.remove(false);
                return false;
            },
            handleTypeClick: function(e)
            {
                BX.PreventDefault(e);
                this.openViewDialog();
                return false;
            },
            handleSubjectClick: function(e)
            {
                BX.PreventDefault(e);
                this.openViewDialog();
                return false;
            },
            openViewDialog: function()
            {
                this._editor.openActivityDialog(BX.YNSIRDialogMode.view, this.getId());
            },
            getId: function()
            {
                return this.getSetting('ID', 0);
            },
            getStartDate: function()
            {
                var start = this.getSetting('start', '');
                return start ? BX.parseDate(start) : null;
            },
            getEndDate: function()
            {
                var end = this.getSetting('end', '');
                return end ? BX.parseDate(end) : null;
            },
            getDeadline: function()
            {
                var deadline = this.getSetting('deadline', '');
                return deadline ? BX.parseDate(deadline) : null;
            },
            getSubject: function()
            {
                return this.getSetting('subject', '');
            },
            getRow: function()
            {
                return this._row;
            },
            setRow: function(row)
            {
                if(!this.isUIEnabled())
                {
                    return;
                }

                this._row = row;

                if(!row)
                {
                    return;
                }

                var typeLink = BX.findChild(this._row, { 'tag':'a', 'class':'crm-activity-type' }, true, false);
                if(typeLink)
                {
                    BX.bind(typeLink, 'click', BX.delegate(this.handleTypeClick, this));
                }

                var subjLink = BX.findChild(this._row, { 'tag':'a', 'class':'crm-activity-subject' }, true, false);
                if(subjLink)
                {
                    BX.bind(subjLink, 'click', BX.delegate(this.handleSubjectClick, this));
                }

                var deleteBtn = BX.findChild(this._row, { 'tag':'span', 'class':'crm-view-table-column-delete' }, true, false);
                if(deleteBtn)
                {
                    BX.bind(deleteBtn, 'click', BX.delegate(this.handleDeleteClick, this));
                    deleteBtn.setAttribute('title', BX.YNSIRActivityEditor.getMessage('deleteButtonTitle'));
                }
            },
            getSetting: function (name, defaultval)
            {
                return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
            },
            saveSettings: function()
            {
                //nothing to save
            },
            getSettings: function()
            {
                return this._settings;
            },
            setSettings: function(settings)
            {
                this._settings = settings ? settings : {};
            },
            setSetting: function(name, val)
            {
                this._settings[name] = val;
            },
            setCompleted: function(completed)
            {
                this._settings['completed'] = completed;
            },
            setPriority: function(priority)
            {
                this._settings['priority'] = priority;
            },
            isCompleted: function()
            {
                return this.getSetting('completed', false);
            },
            layout: function()
            {
                if(!this.isUIEnabled())
                {
                    return;
                }

                var row = this._row;

                if(!row)
                {
                    return;
                }

                BX.cleanNode(row, false);

                if(parseInt(this.getSetting('priority', BX.YNSIRActivityPriority.medium)) === BX.YNSIRActivityPriority.high)
                {
                    BX.addClass(row, 'crm-activity-row-important');
                }
                else
                {
                    BX.removeClass(row, 'crm-activity-row-important');
                }

                var delLink = BX.create(
                    'SPAN',
                    {
                        props: { className: 'crm-view-table-column-delete' }
                        //style: { display: this._viewMode ? 'none' : '' }
                    }
                );
                (row.insertCell(-1)).appendChild(delLink);
                BX.bind(delLink, 'click', BX.delegate(this.handleDeleteClick, this));

                (row.insertCell(-1)).appendChild(
                    BX.create(
                        'A',
                        {
                            props:
                                {
                                    href:'#',
                                    className: 'crm-activity-type'
                                },
                            text: BX.YNSIRActivityType.getName(this.getSetting('typeID', '')),
                            events: { click: BX.delegate(this.handleTypeClick, this) }
                        }
                    )
                );

                (row.insertCell(-1)).appendChild(
                    BX.create(
                        'A',
                        {
                            props:
                                {
                                    href:'#',
                                    className: 'crm-activity-subject'
                                },
                            text: this.getSetting('subject', ''),
                            events: { click: BX.delegate(this.handleSubjectClick, this) }
                        }
                    )
                );

                var deadline = this.getSetting('deadline', '');
                deadline = deadline !== '' ? BX.parseDate(deadline) : null;
                (row.insertCell(-1)).appendChild(
                    BX.create(
                        'SPAN',
                        {
                            text: deadline ? BX.YNSIRActivityEditor.trimDateTimeString(BX.date.format(BX.YNSIRActivityEditor.getDateTimeFormat(), deadline)) : '',
                            style: { color: !this.isCompleted() && deadline && deadline < (new Date()) ? '#ff0000' : '' }
                        }
                    )
                );

                (row.insertCell(-1)).appendChild(
                    BX.create(
                        'SPAN',
                        {
                            text: this.getSetting('responsibleName', '')
                        }
                    )
                );

//			(row.insertCell(-1)).appendChild(
//				BX.create(
//					'SPAN',
//					{
//						html: BX.util.htmlspecialchars(BX.YNSIRActivityPriority.getName(this.getSetting('priority', BX.YNSIRActivityPriority.medium)))
//					}
//				)
//			);
            },
            cleanLayout: function()
            {
                if(!this.isUIEnabled())
                {
                    return;
                }

                if(this._row)
                {
                    BX.cleanNode(this._row, true);
                }
            }
        };
    BX.YNSIRActivity.create = function(settings, row, editor)
    {
        var self = new BX.YNSIRActivity();
        self.initialize(settings, row, editor);
        return self;
    };
    BX.YNSIRActivityCalEvent = function()
    {
        this._settings = {};
        this._options = {};
        this._cntWrapper = null;
        this._ttlWrapper = null;
        this._dlgID = '';
        this._dlg = null;
        this._dlgMode = BX.YNSIRDialogMode.view;
        this._dlgCfg = {};
        this._onSaveHandlers = [];
        this._onDlgCloseHandlers = [];
        this._editor = null;
        this._communication = null;
        this._communicationSearch = null;
        this._isChanged = false;
        this._buttonId = BX.YNSIRActivityDialogButton.undefined;
        this._uploaderName = 'cal_event_uploader';
        this._storageTypeId = BX.YNSIRActivityStorageType.undefined;
        this._storageElementInfos = [];
        this._owner = null;
        this._userSearchPopup = null;
        this._salt = '';
        this._callCreationHandler = BX.delegate(this._handleCallCreation, this);
        this._meetingCreationHandler = BX.delegate(this._handleMeetingCreation, this);
        this._emailCreationHandler = BX.delegate(this._handleEmailCreation, this);
        this._taskCreationHandler = BX.delegate(this._handleTaskCreation, this);
        this._expandHandler = BX.delegate(this._handleExpand, this);
        this._titleMenu = null;
        this._expanded = false;
        this._communicationWaiter = null;
        this._communicationsReady = true;
        this._requestIsRunning = false;
        this._hasFlvPlayer = false;
        this._disableStorageEdit = false;
    };
    BX.YNSIRActivityCalEvent.prototype =
        {
            initialize: function(settings, editor, options)
            {
                this._settings = settings ? settings : {};
                this._editor = editor;
                this._options = options ? options : {};

                var ownerType = this.getSetting('ownerType', '');
                var ownerID =this.getSetting('ownerID', '');
                this._salt = Math.random().toString().substring(2);


                this._communicationSearch = BX.YNSIRActivityEditor.createCommunicationSearch(
                    'COMM_SEARCH_' + ownerType + '_' + ownerID + '_' + this._salt,
                    {
                        'entityType' : ownerType,
                        'entityId': ownerID,
                        'serviceUrl': this.getSetting('serviceUrl', ''),
                        'communicationType': this.getType() === BX.YNSIRActivityType.call ? BX.YNSIRCommunicationType.phone : BX.YNSIRCommunicationType.undefined,
                        'selectCallback': BX.delegate(this._handleCommunicationSelect, this),
                        'enableSearch': true,
                        'enableDataLoading': false
                    }
                );

                this._isChanged = this.getOption('markChanged', false);
                this._disableStorageEdit = (this.getOption('disableStorageEdit', false) || this.getSetting('disableStorageEdit', false));
            },
            getMode: function()
            {
                return this._dlgMode;
            },
            getMessage: function(name)
            {
                return BX.YNSIRActivityCalEvent.messages && BX.YNSIRActivityCalEvent.messages[name] ? BX.YNSIRActivityCalEvent.messages[name] : '';
            },
            getSetting: function (name, defaultval)
            {
                return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
            },
            setSetting: function (name, val)
            {
                this._settings[name] = val;
            },
            getOption: function (name, defaultval)
            {
                return typeof(this._options[name]) != 'undefined' ? this._options[name] : defaultval;
            },
            getType: function()
            {
                return this.getSetting('typeID', BX.YNSIRActivityType.activity);
            },
            getId: function()
            {
                return parseInt(this.getSetting('ID', '0'));
            },
            getOwnerType: function()
            {
                return this.getSetting('ownerType', '');
            },
            getOwnerId: function()
            {
                return this.getSetting('ownerID', '');
            },
            canChangeOwner: function()
            {
                if(this.getMode() !== BX.YNSIRDialogMode.edit || this.getId() > 0)
                {
                    return false;
                }

                var ownerType = this.getOwnerType();
                if(ownerType === 'LEAD')
                {
                    return false;
                }

                return ownerType !== 'DEAL' || this._editor.getOwnerType() !== 'DEAL';
            },
            displayOwner: function()
            {
                return this.getSetting('ownerType', '') === 'DEAL';
            },
            getDefaultStorageTypeId: function()
            {
                return parseInt(this.getSetting('defaultStorageTypeId', BX.YNSIRActivityStorageType.file));
            },
            getStatusId: function()
            {
                return this.getSetting('completed', false) ? BX.YNSIRActivityStatus.completed : BX.YNSIRActivityStatus.waiting;
            },
            getStatusName: function()
            {
                return BX.YNSIRActivityStatus.getName(this.getType(), this.getStatusId());
            },
            isChanged: function()
            {
                return this._isChanged;
            },
            getButtonId: function()
            {
                return this._buttonId;
            },
            getEditor: function()
            {
                return this._editor;
            },
            openDialog: function(mode)
            {
                var id = this.getId();

                if(!mode)
                {
                    mode = id > 0 ? BX.YNSIRDialogMode.view : BX.YNSIRDialogMode.edit;
                }

                if (BX.YNSIRActivityProvider)
                {
                    var activity = BX.YNSIRActivityProvider.create(this._settings, this._editor, this._options);

                    window.setTimeout(
                        function() { activity.openDialog(mode);},
                        10
                    );
                    return;
                }

                this._dlgMode = mode;

                var dlgId = this._dlgID = 'YNSIRActivity'
                    + (this.getType() == BX.YNSIRActivityType.meeting ? 'Meeting' : 'Call')
                    + (mode == BX.YNSIRDialogMode.edit ? (id > 0 ? 'Edit' : 'Create') : 'View')
                    + (id > 0 ? id : '');

                if(BX.YNSIRActivityCalEvent.dialogs[dlgId])
                {
                    BX.YNSIRActivityCalEvent.dialogs[dlgId].destroy();
                }

                this._dlgCfg = {};

                var self = this;
                this._dlg = new BX.PopupWindow(
                    dlgId,
                    null,
                    {
                        className: "bx-crm-dialog-wrap bx-crm-dialog-activity-call-event",
                        autoHide: false,
                        draggable: true,
                        offsetLeft: 0,
                        offsetTop: 0,
                        bindOptions: { forceBindPosition: false },
                        closeByEsc: false,
                        closeIcon: true,
                        zIndex: -12, //HACK: for tasks popup
                        titleBar:
                            {
                                content:  mode == BX.YNSIRDialogMode.edit
                                    ? this._prepareEditDlgTitle()
                                    : this._prepareViewDlgTitle()
                            },
                        events:
                            {
                                onPopupShow: function()
                                {
                                    if(self._ttlWrapper)
                                    {
                                        BX.bind(
                                            BX.findParent(self._ttlWrapper, { 'class': 'popup-window-titlebar' }),
                                            'dblclick',
                                            self._expandHandler
                                        );
                                    }
                                },
                                onPopupClose: BX.delegate(
                                    function()
                                    {
                                        if(this._communicationSearch)
                                        {
                                            this._communicationSearch.closeDialog();
                                        }

                                        this._closeOwnerSelector();

                                        if(this._userSearchPopup)
                                        {
                                            this._userSearchPopup.close();
                                        }

                                        this._releaseFlvPlayers();

                                        this._editor.hideClock(this.getSetting('clockID', ''));
                                        this._editor.hideUploader(this.getSetting('uploadID', ''), this.getSetting('uploadControlID', ''));
                                        this._dlg.destroy();
                                    },
                                    this
                                ),
                                onPopupDestroy: BX.proxy(
                                    function()
                                    {
                                        self._dlg = null;
                                        self._wrapper = null;
                                        self._ttlWrapper = null;
                                        delete(BX.YNSIRActivityCalEvent.dialogs[dlgId]);
                                    },
                                    this
                                )
                            },
                        content: mode == BX.YNSIRDialogMode.edit
                            ? this._prepareEditDlgContent(dlgId)
                            : this._prepareViewDlgContent(dlgId),
                        buttons: mode == BX.YNSIRDialogMode.edit
                            ? this._prepareEditDlgButtons()
                            : this._prepareViewDlgButtons()
                    }
                );

                BX.YNSIRActivityCalEvent.dialogs[dlgId] = this._dlg;
                var dataRequestParams = null;
                if(this._communicationSearch && !this._communicationSearch.isDataLoaded())
                {
                    if(!dataRequestParams)
                    {
                        dataRequestParams = {};
                    }
                    this._communicationSearch.prepareDataRequest(dataRequestParams);
                }

                if(id <= 0)
                {

                    if(dataRequestParams)
                    {
                        this._communicationsReady = false;
                        this._findElement('contacts').appendChild(this._prepareCommunicationWaiter());
                        this._editor.getActivityViewData(dataRequestParams, BX.delegate(this.processDataLoadingResponse, this));
                    }
                    else
                    {
                        var defaultComm = this._communicationSearch
                            ? this._communicationSearch.getDefaultCommunication() : null;
                        if(defaultComm)
                        {
                            this._addCommunication(defaultComm.getSettings());
                        }
                    }
                }
                else
                {
                    var isCommunicationsLoaded = this.getSetting('communicationsLoaded', true);
                    if(!isCommunicationsLoaded)
                    {
                        if(!dataRequestParams)
                        {
                            dataRequestParams = {};
                        }
                        dataRequestParams['ACTIVITY_COMMUNICATIONS'] = { 'ID': id };
                    }
                    else
                    {
                        var communications = this.getSetting('communications', []);
                        if(communications.length > 0)
                        {
                            this._addCommunication(communications[0]);
                        }
                    }

                    if(dataRequestParams)
                    {
                        this._communicationsReady = false;
                        this._findElement('contacts').appendChild(this._prepareCommunicationWaiter());
                        this._editor.getActivityViewData(dataRequestParams, BX.delegate(this.processDataLoadingResponse, this));
                    }
                }

                if(this.displayOwner())
                {
                    this._setupOwner(
                        {
                            'type': this.getSetting('ownerType', ''),
                            'id': parseInt(this.getSetting('ownerID', 0)),
                            'title': this.getSetting('ownerTitle', ''),
                            'url': this.getSetting('ownerUrl', '')
                        },
                        !this.canChangeOwner()
                    );
                }

                //Initialize owner selector
                if(this.canChangeOwner())
                {
                    window.setTimeout(
                        BX.delegate(
                            function()
                            {
                                var selectorId = this._editor.createOwnershipSelector(this._dlgID, BX(this.getDialogConfigValue('change_owner_button')));
                                obYNSIR[selectorId].AddOnSaveListener(BX.delegate(this._handleOwnerSelect, this));
                                this.setDialogConfigValue('owner_selector_id', selectorId);
                            }, this
                        ), 0
                    );
                }

                if(this._hasFlvPlayer)
                {
                    if(BX.YNSIRActivityEditor.isFlvPlayerLoaded())
                    {
                        this._initializeFlvPlayers();
                    }
                    else
                    {
                        BX.addCustomEvent(
                            window,
                            'YNSIRActivityEditorFlvPlayerLoaded',
                            BX.delegate(this._onFlvPlayerLoad, this)
                        );

                        BX.YNSIRActivityEditor.loadFlvPlayer();
                    }
                }

                window.setTimeout(
                    BX.delegate(
                        function()
                        {
                            var subject = this._findElement('subject');
                            if(subject)
                            {
                                subject.focus();
                            }
                        }, this
                    ), 0
                );

                this._dlg.show();
            },
            closeDialog: function()
            {
                if(this._communicationSearchController)
                {
                    this._communicationSearchController.stop();
                    this._communicationSearchController = null;
                }

                if(this._communicationSearch)
                {
                    this._communicationSearch.closeDialog();
                }

                if(this._webDavUploader)
                {
                    this._webDavUploader.cleanLayout();
                }

                this._closeOwnerSelector();

                if(this._titleMenu)
                {
                    this._titleMenu.removeCreateEmailListener(this._emailCreationHandler);
                    this._titleMenu.removeCreateTaskListener(this._taskCreationHandler);
                    this._titleMenu.removeCreateCallListener(this._callCreationHandler);
                    this._titleMenu.removeCreateMeetingListener(this._meetingCreationHandler);

                    this._titleMenu.cleanLayout();
                }

                if(!this._dlg)
                {
                    return;
                }

                this._notifyDialogClose();
                this._dlg.close();
            },
            _lockSaveButton: function()
            {
                if(!this._dlg)
                {
                    return;
                }

                var saveButton = BX.findChild(
                    this._dlg.popupContainer,
                    { 'class': 'popup-window-button-accept' },
                    true,
                    false
                );

                if(saveButton)
                {
                    BX.removeClass(saveButton, 'popup-window-button-accept');
                    BX.addClass(saveButton, 'popup-window-button-accept-disabled');
                }
            },
            _unlockSaveButton: function()
            {
                if(!this._dlg)
                {
                    return;
                }

                var saveButton = BX.findChild(
                    this._dlg.popupContainer,
                    { 'class': 'popup-window-button-accept-disabled' },
                    true,
                    false
                );

                if(saveButton)
                {
                    BX.removeClass(saveButton, 'popup-window-button-accept-disabled');
                    BX.addClass(saveButton, 'popup-window-button-accept');
                }
            },
            _prepareEditDlgTitle: function()
            {
                var text = '';
                var id = this.getId();
                if(id <= 0)
                {
                    text = this.getType() == BX.YNSIRActivityType.meeting
                        ? BX.YNSIRActivityCalEvent.messages['addMeetingDlgTitle']
                        : BX.YNSIRActivityCalEvent.messages['addCallDlgTitle']
                }
                else
                {
                    var subject =  this.getSetting('subject', '');
                    text = BX.YNSIRActivityCalEvent.messages['editDlgTitle'];
                    text =	text.replace(
                        /%SUBJECT%/i,
                        subject.length > 0 ? subject : '#' + id
                    );
                }

                return (this._ttlWrapper = BX.YNSIRActivityEditor.prepareDialogTitle(text));
            },
            _prepareEditDlgContent: function(dlgId)
            {
                var isNew = this.getId() <= 0;
                var type = this.getType();
                var cfg = this._dlgCfg;
                var codeSalt = this._salt;

                //wrapper
                var wrapper = this._cntWrapper = BX.create(
                    'DIV',
                    {
                        attrs: { className: this.getType() == BX.YNSIRActivityType.meeting ? 'bx-crm-dialog-add-meeting-popup' : 'bx-crm-dialog-add-call-popup' }
                    }
                );

                cfg['error'] = this._prepareCode('activity_cal_event_error', codeSalt);
                wrapper.appendChild(
                    BX.create(
                        'DIV',
                        {
                            attrs:
                                {
                                    className: 'bx-crm-dialog-activity-error',
                                    style: 'display:none;'
                                },
                            props: { id: cfg['error'] }
                        }
                    )
                );

                //form
                cfg['form'] = this._prepareCode('activity_cal_event');
                var form = BX.create('FORM', { props: { name: cfg['form'] } });

                wrapper.appendChild(form);

                //table
                var tab = BX.create(
                    'TABLE',
                    {
                        attrs: { className: 'bx-crm-dialog-activity-table' }
                    }
                );
                tab.cellSpacing = '0';
                tab.cellPadding = '0';
                tab.border = '0';
                form.appendChild(tab);

                //start
                var start = BX.parseDate(this.getSetting('start', ''));
                if(!start)
                {
                    start = new Date();
                }

                cfg['startDate'] = this._prepareCode('startDate', codeSalt);
                cfg['startTime'] = this.getSetting('clockInputID', '');

                // notify
                cfg['enableNotifyWrapper'] = this._prepareCode('enableNotifyWapper');
                cfg['enableNotify'] = this._prepareCode('enableNotify');
                cfg['notifyVal'] = this._prepareCode('notifyVal');
                cfg['notifyTypeSwitch'] = this._prepareCode('notifyTypeSwitch');
                cfg['notifyType'] = this._prepareCode('notifyType');

                var notifyType = this.getSetting('notifyType', BX.YNSIRActivityNotifyType.none);
                var notifyValue = this.getSetting('notifyValue', 0);
                var enableNotify = notifyType != BX.YNSIRActivityNotifyType.none;

                var startTime = BX(cfg['startTime']);
                if(startTime)
                {
                    // If AM/PM enabled use wide style
                    startTime.className = 'bx-crm-dialog-input ' + (BX.isAmPmMode() ? 'bx-crm-dialog-input-time-wide' : 'bx-crm-dialog-input-time');
                    //Remove seconds from format
                    var timeFormat = BX.YNSIRActivityEditor.getTimeFormat().replace(/:?\s*s/, '');
                    startTime.value = BX.date.format(timeFormat, start);
                }

                BX.YNSIRActivityEditor.loadClock(this.getSetting('clockInputID', ''));
                var clock = BX(this.getSetting('clockID', ''));
                if(clock)
                {
                    clock.style.display = 'inline-block';
                }

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: BX.create('SPAN', { text: this.getMessage('datetime') + ':' }),
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-activity-table-right' },
                                    children:
                                        [
                                            BX.create(
                                                'DIV',
                                                {
                                                    //style: {'whiteSpace':'nowrap'},
                                                    children:
                                                        [
                                                            BX.create(
                                                                'INPUT',
                                                                {
                                                                    attrs: { className: 'bx-crm-dialog-input bx-crm-dialog-input-date' },
                                                                    props:
                                                                        {
                                                                            type: 'text',
                                                                            id: cfg['startDate'],
                                                                            name: cfg['startDate'],
                                                                            value: BX.date.format(BX.YNSIRActivityEditor.getDateFormat(), start)
                                                                        },
                                                                    style:
                                                                        {
                                                                            width:'70px'
                                                                        },
                                                                    events:
                                                                        {
                                                                            click: BX.delegate(this._handleDateInputClick, this)
                                                                        }
                                                                }
                                                            ),
                                                            BX.create(
                                                                'A',
                                                                {
                                                                    props:
                                                                        {
                                                                            href:'javascript:void(0);',
                                                                            title: this.getMessage('setDate')
                                                                        },
                                                                    children:
                                                                        [
                                                                            BX.create(
                                                                                'IMG',
                                                                                {
                                                                                    attrs:
                                                                                        {
                                                                                            src: this.getSetting('imagePath', '') + 'calendar.gif',
                                                                                            className: 'calendar-icon',
                                                                                            alt: this.getMessage('setDate')
                                                                                        },
                                                                                    events:
                                                                                        {
                                                                                            click: BX.delegate(this._handleDateInputClick, this),
                                                                                            mouseover: BX.delegate(this._handleDateImageMouseOver, this),
                                                                                            mouseout: BX.delegate(this._handleDateImageMouseOut, this)
                                                                                        }
                                                                                }
                                                                            )
                                                                        ]
                                                                }
                                                            ),
                                                            clock
                                                        ]
                                                }
                                            ),
                                            BX.create(
                                                'DIV',
                                                {
                                                    attrs: { className: 'bx-crm-dialog-remind-wrapper' + (enableNotify ? '' : ' bx-crm-dialog-remind-wrapper-hidden') },
                                                    props: { id:cfg['enableNotifyWrapper'] },
                                                    children:
                                                        [
                                                            BX.create(
                                                                'INPUT',
                                                                {
                                                                    attrs:
                                                                        {
                                                                            className: 'bx-crm-dialog-checkbox',
                                                                            checked: enableNotify
                                                                        },
                                                                    props:
                                                                        {
                                                                            type: 'checkbox',
                                                                            id: cfg['enableNotify'],
                                                                            name: cfg['enableNotify']
                                                                        },
                                                                    events:
                                                                        {
                                                                            click: BX.delegate(this.handleNotifyToggle, this)
                                                                        }
                                                                }
                                                            ),
                                                            BX.create(
                                                                'LABEL',
                                                                {
                                                                    attrs:
                                                                        {
                                                                            'className':'bx-crm-dialog-label',
                                                                            'for': cfg['enableNotify']
                                                                        },
                                                                    text: this.getMessage('enableNotification')
                                                                }
                                                            ),
                                                            BX.create(
                                                                'INPUT',
                                                                {
                                                                    attrs: { className: 'bx-crm-dialog-input bx-crm-dialog-input-remind-time' },
                                                                    props:
                                                                        {
                                                                            type: 'text',
                                                                            id: cfg['notifyVal'],
                                                                            name: cfg['notifyVal'],
                                                                            value: enableNotify ? notifyValue : 15
                                                                        }
                                                                }
                                                            ),
                                                            BX.create(
                                                                'SPAN',
                                                                {
                                                                    attrs: { className:'bx-crm-dialog-input-remind-type' },
                                                                    props:
                                                                        {
                                                                            id: cfg['notifyTypeSwitch'],
                                                                            name: cfg['notifyTypeSwitch']
                                                                        },
                                                                    html: BX.YNSIRActivityNotifyType.getName(enableNotify ? notifyType : BX.YNSIRActivityNotifyType.min),
                                                                    events: { click: BX.delegate(this._handleNotifyTypeChange, this) }
                                                                }
                                                            ),
                                                            BX.create(
                                                                'INPUT',
                                                                {
                                                                    props:
                                                                        {
                                                                            id: cfg['notifyType'],
                                                                            name: cfg['notifyType'],
                                                                            type: 'hidden',
                                                                            value: enableNotify ? notifyType : BX.YNSIRActivityNotifyType.min
                                                                        }
                                                                }
                                                            )
                                                        ]
                                                }
                                            )
                                        ]
                                }
                            ]
                    }
                );

                // location
                if(type === BX.YNSIRActivityType.activity || type === BX.YNSIRActivityType.meeting)
                {
                    var location = this.getSetting('location', '');
                    cfg['location'] = this._prepareCode('location');
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            title: BX.create('SPAN', { html: this.getMessage('location') + ':' }),
                            content: BX.create(
                                'INPUT',
                                {
                                    attrs: { className: 'bx-crm-dialog-input' },
                                    props:
                                        {
                                            type: 'text',
                                            id: cfg['location'],
                                            name: cfg['location'],
                                            value: location
                                        }
                                }
                            )
                        }
                    );
                }

                if(type === BX.YNSIRActivityType.call)
                {
                    //direction
                    var direction = parseInt(this.getSetting('direction', BX.YNSIRActivityDirection.outgoing));
                    this.setSetting('direction', direction);

                    cfg['direction'] = this._prepareCode('direction');
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            title: BX.create('SPAN', { text: this.getMessage('direction') + ':' }),
                            content: [
                                BX.create(
                                    'SPAN',
                                    {
                                        attrs: { className: 'bx-crm-dialog-status-text' },
                                        props: { id: cfg['direction'] },
                                        text: BX.YNSIRActivityDirection.getName(BX.YNSIRActivityType.call, direction),
                                        events:
                                            {
                                                click: BX.delegate(this._handleDirectionChange, this)
                                            }
                                    }
                                )
                            ]
                        }
                    );
                }

                //contact
                cfg['contacts'] = this._prepareCode('contacts', codeSalt);
                var contactContainer = BX.create(
                    'DIV',
                    {
                        attrs: { className: 'bx-crm-dialog-comm-block' },
                        props: { id: cfg['contacts'] },
                        events: { click: BX.delegate(this._openCommunicationDialog,  this) }
                    }
                );

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: BX.create('SPAN', { html: this.getMessage('partner') + ':' }),
                        content: [ contactContainer ]
                    }
                );

                //subject
                cfg['subject'] = this._prepareCode('subject');
                var subject = this.getSetting('subject', '');
                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: BX.create('SPAN', { text: BX.YNSIRActivityCalEvent.messages['subject'] + ':' }),
                        content: BX.create(
                            'INPUT',
                            {
                                attrs:
                                    {
                                        className: 'bx-crm-dialog-input',
                                        placeholder:
                                            this.getMessage(this.getType() == BX.YNSIRActivityType.meeting
                                                ? 'meetingSubjectHint' : 'callSubjectHint')
                                    },
                                props:
                                    {
                                        type: 'text',
                                        id: cfg['subject'],
                                        name: cfg['subject'],
                                        value: subject
                                    }
                            }
                        )
                    }
                );

                //description
                var description = this.getSetting('description', '');
                var hasDescr = cfg['hasDescription']  = description !== '';
                if(!hasDescr)
                {
                    description = type == BX.YNSIRActivityType.meeting ? BX.YNSIRActivityCalEvent.messages['meetingDescrHint'] : BX.YNSIRActivityCalEvent.messages['callDescrHint'];
                }

                cfg['description'] = this._prepareCode('description');
                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-activity-table-right' },
                                    children:
                                        [
                                            BX.create(
                                                'TEXTAREA',
                                                {
                                                    attrs: { className: 'bx-crm-dialog-description-form' },
                                                    props:
                                                        {
                                                            id: cfg['description'],
                                                            name: cfg['description'],
                                                            value: description
                                                        },
                                                    events:
                                                        {
                                                            focus:BX.delegate(this._handleEditDescriptionFocus, this),
                                                            blur:BX.delegate(this._handleEditDescriptionBlur, this)
                                                        }
                                                }
                                            )
                                        ]
                                }
                            ]
                    }
                );

                //responsible
                var responsibleID = isNew ? this.getSetting('userID', 0) : this.getSetting('responsibleID', 0);
                var responsibleName = isNew ? this.getSetting('userFullName', 0) : this.getSetting('responsibleName', 0);

                cfg['responsibleSearch'] = this._prepareCode('responsibleSearch');
                cfg['responsibleData'] = this._prepareCode('responsibleData');

                var responsibleSearch = BX.create(
                    'INPUT',
                    {
                        attrs: { className: 'bx-crm-dialog-input' },
                        props:
                            {
                                type: 'text',
                                id: cfg['responsibleSearch'],
                                value: responsibleName
                            }
                    }
                );

                var responsibleData = BX.create(
                    'INPUT',
                    {
                        attrs: {},
                        props:
                            {
                                type: 'hidden',
                                id: cfg['responsibleData'],
                                value: responsibleID
                            }
                    }
                );

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: BX.create('SPAN', { html: this.getMessage('responsible') + ':' }),
                        content: [ responsibleSearch, responsibleData ]
                    }
                );

                this._userSearchPopup = BX.YNSIRUserSearchPopup.createIfNotExists(dlgId + 'Responsible',
                    {
                        'searchInput': responsibleSearch,
                        'dataInput': responsibleData,
                        'componentName': this.getSetting('userSearchJsName'),
                        'user': { 'id': responsibleID, 'name': responsibleName },
                        'serviceContainer': this._editor.getServiceContainer()
                    }
                );

                //ownership
                if(this.canChangeOwner())
                {
                    cfg['change_owner_button'] = this._prepareCode('change_owner_button', codeSalt);
                    var ownerChangeButton = BX.create(
                        'SPAN',
                        {
                            'attrs': { className: 'bx-crm-dialog-owner-change-text' },
                            'props': { id: cfg['change_owner_button'] },
                            'text': this.getMessage('change'),
                            'events': { click: BX.delegate(this._handleChangeOwnerClick, this) }
                        }
                    );

                    cfg['owner_info_wrapper'] = this._prepareCode('owner_info_wrapper', codeSalt);
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            title: BX.create('SPAN', { text: this.getMessage('owner') + ':' }),
                            content:
                                [
                                    BX.create(
                                        'DIV',
                                        {
                                            attrs: { className: 'bx-crm-dialog-owner-block' },
                                            children:
                                                [
                                                    BX.create(
                                                        'DIV',
                                                        {
                                                            attrs: { className: 'bx-crm-dialog-owner-info-wrapper' },
                                                            props: { id: cfg['owner_info_wrapper'] }
                                                        }
                                                    ),
                                                    BX.create(
                                                        'DIV',
                                                        {
                                                            attrs: { className: 'bx-crm-dialog-owner-button-wrapper' },
                                                            children:
                                                                [
                                                                    ownerChangeButton
                                                                ]
                                                        }
                                                    )
                                                ]
                                        }
                                    )
                                ]
                        }
                    );
                }
                else if(this.displayOwner())
                {
                    cfg['owner_info_wrapper'] = this._prepareCode('owner_info_wrapper', codeSalt);
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            title: BX.create('SPAN', { text: this.getMessage('owner') + ':' }),
                            content:
                                [
                                    BX.create(
                                        'DIV',
                                        {
                                            attrs: { className: 'bx-crm-dialog-owner-block' },
                                            children:
                                                [
                                                    BX.create(
                                                        'DIV',
                                                        {
                                                            attrs: { className: 'bx-crm-dialog-owner-info-wrapper' },
                                                            props: { id: cfg['owner_info_wrapper'] }
                                                        }
                                                    )
                                                ]
                                        }
                                    )
                                ]
                        }
                    );
                }

                //status
                cfg['status_text'] = this._prepareCode('status_text');

                var status = this.getSetting('completed', false) ? BX.YNSIRActivityStatus.completed : BX.YNSIRActivityStatus.waiting;
                var statusWrapper = BX.create(
                    'DIV',
                    {
                        attrs: { className: 'bx-crm-dialog-status' },
                        text: this.getMessage('status') + ':'
                    }
                );

                statusWrapper.appendChild(
                    BX.create(
                        'SPAN',
                        {
                            attrs: { className: 'bx-crm-dialog-status-text' },
                            props: { id: cfg['status_text'] },
                            text: BX.YNSIRActivityStatus.getName(type, status),
                            events:
                                {
                                    click: BX.delegate(this._handleStatusChange, this)
                                }
                        }
                    )
                );

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: '',
                        content: statusWrapper
                    }
                );

                // priority
                var priority = this.getSetting('priority', BX.YNSIRActivityPriority.medium);
                cfg['priority_text'] = this._prepareCode('priority_text');

                var priorityText = BX.create(
                    'SPAN',
                    {
                        attrs: { className: BX.YNSIRActivityEditor.resolvePriorityClassName(priority) },
                        props: { id: cfg['priority_text'] },
                        events: { click: BX.delegate(this._handlePriorityChange, this) }
                    }
                );
                priorityText.appendChild(BX.create('I'));
                priorityText.appendChild(document.createTextNode(BX.YNSIRActivityPriority.getName(priority)));

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: '',
                        content:
                            [
                                document.createTextNode( this.getMessage('priority') + ':'),
                                priorityText
                            ]
                    }
                );

                //type
                if(type === BX.YNSIRActivityType.activity)
                {
                    cfg['type_text'] = this._prepareCode('type_text');

                    var typeText = BX.create(
                        'SPAN',
                        {
                            attrs: { className: 'bx-crm-dialog-activity-type-text' },
                            props: { id: cfg['type_text'] },
                            text: this.getMessage('undefinedType'),
                            events:
                                {
                                    click: BX.delegate(this._handleTypeChange, this)
                                }
                        }
                    );

                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            title: '',
                            content:
                                [
                                    document.createTextNode(this.getMessage('type') + ':'),
                                    typeText
                                ]
                        }
                    );
                }

                if(!this._disableStorageEdit)
                {
                    var storageTypeId = parseInt(this.getSetting('storageTypeID', BX.YNSIRActivityStorageType.undefined));
                    if(isNaN(storageTypeId) || storageTypeId === BX.YNSIRActivityStorageType.undefined)
                    {
                        storageTypeId = this.getDefaultStorageTypeId();
                    }
                    this._storageTypeId = storageTypeId;
                    if(storageTypeId === BX.YNSIRActivityStorageType.webdav)
                    {
                        var webDavUploaderNode = this._editor.prepareWebDavUploader(
                            this._uploaderName,
                            this.getMode(),
                            this.getSetting('webdavelements', [])
                        );
                        BX.YNSIRActivityEditor.prepareDialogRow(tab, { content: webDavUploaderNode });
                    }
                    else if(storageTypeId === BX.YNSIRActivityStorageType.disk)
                    {
                        var diskUploaderNode = this._editor.prepareDiskUploader(
                            this._uploaderName,
                            this.getMode(),
                            this.getSetting('diskfiles', [])
                        );
                        BX.YNSIRActivityEditor.prepareDialogRow(tab, { content: diskUploaderNode });
                    }
                    else
                    {
                        BX.YNSIRActivityEditor.prepareDialogRow(
                            tab,
                            {
                                content:
                                    this._editor.prepareFileUploader(
                                        this.getSetting('uploadControlID', ''),
                                        this.getSetting('uploadID', ''),
                                        this.getSetting('files', [])
                                    )
                            }
                        );
                    }
                }
                return wrapper;
            },
            _prepareEditDlgButtons: function()
            {
                return BX.YNSIRActivityEditor.prepareDialogButtons(
                    [
                        {
                            type: 'button',
                            settings:
                                {
                                    text: BX.YNSIRActivityEditor.getMessage('saveDlgButton'),
                                    className: 'popup-window-button-accept',
                                    events:
                                        {
                                            click : BX.delegate(this._handleAcceptButtonClick, this)
                                        }
                                }
                        },
                        {
                            type: 'link',
                            settings:
                                {
                                    text: BX.YNSIRActivityEditor.getMessage('cancelShortDlgButton'),
                                    className: 'popup-window-button-link-cancel',
                                    events:
                                        {
                                            click : BX.delegate(this._handleCancelButtonClick, this)
                                        }
                                }
                        }
                    ]
                );
            },
            _prepareViewDlgTitle: function()
            {
                var typeName = BX.YNSIRActivityCalEvent.messages['activity'];
                switch(this.getType())
                {
                    case BX.YNSIRActivityType.meeting:
                        typeName = BX.YNSIRActivityCalEvent.messages['meeting'];
                        break;
                    case BX.YNSIRActivityType.call:
                        typeName = BX.YNSIRActivityCalEvent.messages['call'];
                        break;
                }

                var subject =  this.getSetting('subject', '');
                var text = BX.YNSIRActivityCalEvent.messages['viewDlgTitle'];
                text =	text.replace(/%TYPE%/gi, typeName);

                text =	text.replace(
                    /%SUBJECT%/gi,
                    subject.length > 0 ? subject : '#' + this.getSetting('ID', '0')
                );

                this._titleMenu = BX.YNSIRActivityMenu.create('',
                    {
                        'enableTasks': this._editor.isTasksEnabled(),
                        'enableCalendarEvents': this._editor.isCalendarEventsEnabled(),
                        'enableEmails': this._editor.isEmailsEnabled()
                    },
                    {
                        'createTask': this._taskCreationHandler,
                        'createCall': this._callCreationHandler,
                        'createMeeting': this._meetingCreationHandler,
                        'createEmail': this._emailCreationHandler
                    }
                );

                var wrapper = this._ttlWrapper = BX.YNSIRActivityEditor.prepareDialogTitle(text);
                this._titleMenu.layout(wrapper);
                return wrapper;
            },
            _prepareViewDlgContent: function(dlgId)
            {
                var enableInstantEdit = this.getOption('enableInstantEdit', true);

                var type = this.getType();
                var cfg = this._dlgCfg;
                var codeSalt = this._salt;

                //wrapper
                var wrapper = this._cntWrapper = BX.create(
                    'DIV',
                    {
                        attrs: { className: this.getType() == BX.YNSIRActivityType.meeting ? 'bx-crm-dialog-view-meeting-popup' : 'bx-crm-dialog-view-call-popup' }
                    }
                );

                //form
                cfg['form'] = this._prepareCode('activity_cal_event', codeSalt);
                var form = BX.create('FORM', { props: { name: cfg['form'] } });
                wrapper.appendChild(form);

                //table
                var tab = BX.create('TABLE');
                tab.cellSpacing = '0';
                tab.cellPadding = '0';
                tab.border = '0';
                tab.className = this.getType() == BX.YNSIRActivityType.meeting ? 'bx-crm-dialog-view-meeting-table' : 'bx-crm-dialog-view-call-table';
                form.appendChild(tab);

                //start
                var start = BX.parseDate(this.getSetting('start', ''));
                if(!start)
                {
                    start = new Date();
                }

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage('datetime') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children: [ BX.YNSIRActivityEditor.trimDateTimeString(BX.date.format(BX.YNSIRActivityEditor.getDateTimeFormat(), start)) ]
                                }
                            ]
                    }
                );

                // location
                var location = type == BX.YNSIRActivityType.meeting ? this.getSetting('location', '') : '';
                // Do not display empty location.
                if(BX.type.isNotEmptyString(location))
                {
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            headerCell:
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                    children: [ this.getMessage('location') + ':' ]
                                },
                            contentCells:
                                [
                                    {
                                        attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                        children: [ location ]
                                    }
                                ]
                        }
                    );
                }

                //contact
                cfg['contacts'] = this._prepareCode('contacts', codeSalt);
                var contactContainer = BX.create(
                    'DIV',
                    {
                        attrs: { className: 'bx-crm-dialog-comm-block' },
                        props: { id: cfg['contacts'] }
                    }
                );

                if(type == BX.YNSIRActivityType.call)
                {
                    //direction
                    var direction = parseInt(this.getSetting('direction', BX.YNSIRActivityDirection.outgoing));
                    cfg['direction'] = this._prepareCode('direction');
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            headerCell:
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                    children: [ this.getMessage('direction') + ':' ]
                                },
                            contentCells:
                                [
                                    {
                                        attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                        children: [ BX.YNSIRActivityDirection.getName(BX.YNSIRActivityType.call, direction) ]
                                    }
                                ]
                        }
                    );
                }

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage('partner') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children: [ contactContainer ]
                                }
                            ]
                    }
                );

                //status
                cfg['status_text'] = this._prepareCode('status_text', codeSalt);
                var status = this.getSetting('completed', false) ? BX.YNSIRActivityStatus.completed : BX.YNSIRActivityStatus.waiting;

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage('status') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children:
                                        [
                                            BX.create(
                                                'SPAN',
                                                {
                                                    attrs: { className: enableInstantEdit ? 'bx-crm-dialog-status-text' : 'bx-crm-dialog-status-text bx-crm-dialog-status-read-only-text' },
                                                    props: { id: cfg['status_text'] },
                                                    text: BX.YNSIRActivityStatus.getName(type, status),
                                                    events:
                                                        {
                                                            click: enableInstantEdit ? BX.delegate(this._handleStatusChange, this) : null
                                                        }
                                                }
                                            )
                                        ]
                                }
                            ]
                    }
                );

                // priority
                var priority = this.getSetting('priority', BX.YNSIRActivityPriority.medium);
                cfg['priority_text'] = this._prepareCode('priority_text', codeSalt);

                var priorityText = BX.create(
                    'SPAN',
                    {
                        attrs: { className: BX.YNSIRActivityEditor.resolvePriorityClassName(priority, !enableInstantEdit) },
                        props: { id: cfg['priority_text'] },
                        events: { click: enableInstantEdit ? BX.delegate(this._handlePriorityChange, this) : null }
                    }
                );
                priorityText.appendChild(BX.create('I'));
                priorityText.appendChild(document.createTextNode(BX.YNSIRActivityPriority.getName(priority)));

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage('priority') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children: [ priorityText ]
                                }
                            ]
                    }
                );

                // subject
                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage('subject') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children: [ this.getSetting('subject', '') ]
                                }
                            ]
                    }
                );

                var descrHtml = this.getSetting('descriptionHtml', '');
                if(descrHtml === '')
                {
                    descrHtml = this.getSetting('description', '');
                    if(descrHtml !== '')
                    {
                        descrHtml = descrHtml.replace(/<script[^>]*>.*?<\/script>/g, '');

                        descrHtml = descrHtml.replace(/<script[^>]*>/g, '');
                        descrHtml = BX.util.htmlspecialchars(descrHtml);
                        descrHtml = descrHtml.replace(/\r\n/g, '<br/>');
                        descrHtml = descrHtml.replace(/(\r|\n)/g, '<br/>');
                    }
                }

                if(descrHtml.length > 0)
                {
                    form.appendChild(
                        BX.create(
                            'DIV',
                            {
                                attrs: { className: 'bx-crm-dialog-view-activity-descr' },
                                children:
                                    [
                                        BX.create(
                                            'DIV',
                                            {
                                                attrs: { className: 'bx-crm-dialog-view-activity-descr-title' },
                                                text:  this.getMessage('description') + ':'
                                            }
                                        ),
                                        BX.create(
                                            'DIV',
                                            {
                                                attrs: { className: 'bx-crm-dialog-view-activity-descr-text' },
                                                html: descrHtml
                                            }
                                        )
                                    ]
                            }
                        )
                    );
                }

                //responsible
                var responsibleName = this.getSetting('responsibleName', '');
                if(BX.type.isNotEmptyString(responsibleName))
                {
                    var responsibleContent = responsibleName;
                    var responsibleUrl = this.getSetting('responsibleUrl', '');
                    if(BX.type.isNotEmptyString(responsibleUrl))
                    {
                        responsibleContent = BX.create(
                            'A',
                            {
                                attrs:
                                    {
                                        className: 'bx-crm-dialog-responsible-user-link',
                                        href: responsibleUrl,
                                        target: '_blank'
                                    },
                                text: responsibleName
                            }
                        )
                    }

                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            headerCell:
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                    children: [ this.getMessage('responsible') + ':' ]
                                },
                            contentCells:
                                [
                                    {
                                        attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                        children: [ responsibleContent ]
                                    }
                                ]
                        }
                    );
                }

                if(this.displayOwner())
                {
                    cfg['owner_info_wrapper'] = this._prepareCode('owner_info_wrapper', codeSalt);
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            headerCell:
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                    children: [ this.getMessage('owner') + ':' ]
                                },
                            contentCells:
                                [
                                    {
                                        attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                        children:
                                            [
                                                BX.create(
                                                    'DIV',
                                                    {
                                                        attrs: { className: 'bx-crm-dialog-owner-block' },
                                                        children:
                                                            [
                                                                BX.create(
                                                                    'DIV',
                                                                    {
                                                                        attrs: { className: 'bx-crm-dialog-owner-info-wrapper' },
                                                                        props: { id: cfg['owner_info_wrapper'] }
                                                                    }
                                                                )
                                                            ]
                                                    }
                                                )
                                            ]
                                    }
                                ]
                        }
                    );
                }

                // files
                var storageTypeId = parseInt(this.getSetting('storageTypeID', BX.YNSIRActivityStorageType.undefined));
                if(isNaN(storageTypeId) || storageTypeId === BX.YNSIRActivityStorageType.undefined)
                {
                    storageTypeId = this.getDefaultStorageTypeId();
                }
                this._storageTypeId = storageTypeId;

                if(storageTypeId === BX.YNSIRActivityStorageType.webdav)
                {
                    var elemAry = this.getSetting('webdavelements', []);
                    for(var i = 0; i < elemAry.length; i++)
                    {
                        var elemInfo = {
                            'id': elemAry[i]['ID'],
                            'name': elemAry[i]['NAME'],
                            'url': elemAry[i]['VIEW_URL'],
                            'ext': BX.YNSIRActivityEditor.getFileExtension(elemAry[i]['NAME']).toUpperCase()
                        };

                        if(!this._hasFlvPlayer && BX.YNSIRActivityEditor.isAudioVideoFile(elemInfo['ext']))
                        {
                            this._hasFlvPlayer = true;
                        }

                        this._storageElementInfos.push(elemInfo);
                    }
                }
                else if(storageTypeId === BX.YNSIRActivityStorageType.disk)
                {
                    var diskFileAry = this.getSetting('diskfiles', []);
                    for(var m = 0; m < diskFileAry.length; m++)
                    {
                        var diskFileInfo = {
                            'id': diskFileAry[m]['ID'],
                            'name': diskFileAry[m]['NAME'],
                            'url': diskFileAry[m]['VIEW_URL'],
                            'ext': BX.YNSIRActivityEditor.getFileExtension(diskFileAry[m]['NAME']).toUpperCase()
                        };

                        if(!this._hasFlvPlayer && BX.YNSIRActivityEditor.isAudioVideoFile(diskFileInfo['ext']))
                        {
                            this._hasFlvPlayer = true;
                        }

                        this._storageElementInfos.push(diskFileInfo);
                    }
                }
                else
                {
                    var fileAry = this.getSetting('files', []);
                    for(var j = 0; j < fileAry.length; j++)
                    {
                        var fileInfo = {
                            'id': '',
                            'name': fileAry[j]['fileName'],
                            'url': fileAry[j]['fileURL'],
                            'ext': BX.YNSIRActivityEditor.getFileExtension(fileAry[j]['fileName']).toUpperCase()
                        };

                        if(!this._hasFlvPlayer && BX.YNSIRActivityEditor.isAudioVideoFile(fileInfo['ext']))
                        {
                            this._hasFlvPlayer = true;
                        }

                        this._storageElementInfos.push(fileInfo);
                    }
                }

                if(this._storageElementInfos.length > 0)
                {
                    var fileInfos = [];
                    var recordInfos = [];
                    for(var k = 0; k < this._storageElementInfos.length; k++)
                    {
                        var info = this._storageElementInfos[k];
                        if(BX.YNSIRActivityEditor.isAudioVideoFile(info['ext']))
                        {
                            recordInfos.push(info);
                        }
                        else
                        {
                            fileInfos.push(info);
                        }
                    }

                    if(fileInfos.length > 0)
                    {
                        BX.YNSIRActivityEditor.prepareDialogRow(
                            tab,
                            {
                                headerCell:
                                    {
                                        attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                        children: [ this.getMessage('files') + ':' ]
                                    },
                                contentCells:
                                    [
                                        {
                                            attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                            children: [ this._editor.prepareFileList(fileInfos, this._dlgID) ]
                                        }
                                    ]
                            }
                        );
                    }

                    if(recordInfos.length > 0)
                    {
                        BX.YNSIRActivityEditor.prepareDialogRow(
                            tab,
                            {
                                headerCell:
                                    {
                                        attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                        children: [ this.getMessage('records') + ':' ]
                                    },
                                contentCells:
                                    [
                                        {
                                            attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                            children: [ this._editor.prepareFileList(recordInfos, this._dlgID) ]
                                        }
                                    ]
                            }
                        );
                    }
                }

                return wrapper;
            },
            _prepareViewDlgButtons: function()
            {
                var buttons = [];

                buttons.push(
                    {
                        type: 'button',
                        settings:
                            {
                                text: BX.YNSIRActivityEditor.getMessage('closeDlgButton'),
                                className: "popup-window-button-accept",
                                events:
                                    {
                                        click : BX.delegate(this._handleCancelButtonClick, this)
                                    }
                            }
                    }
                );

                if(this.getOption('enableEditButton', true))
                {
                    buttons.push(
                        {
                            type: 'link',
                            settings:
                                {
                                    text: BX.YNSIRActivityEditor.getMessage('editDlgButton'),
                                    className: "popup-window-button-link-cancel",
                                    events:
                                        {
                                            click : BX.delegate(this._handleAcceptButtonClick, this)
                                        }
                                }
                        }
                    );
                }

                return BX.YNSIRActivityEditor.prepareDialogButtons(buttons);
            },
            _onFlvPlayerLoad: function()
            {
                this._initializeFlvPlayers();
            },
            _releaseFlvPlayers: function()
            {
                if(!this._hasFlvPlayer)
                {
                    return;
                }

                if(typeof(window['jwplayer']) !== 'undefined')
                {
                    for(var i = 0; i < this._storageElementInfos.length; i++)
                    {
                        var info = this._storageElementInfos[i];
                        var containerId = BX.type.isNotEmptyString(info['containerId']) ? info['containerId'] : '';
                        if(containerId !== '' && jwplayer(containerId))
                        {
                            jwplayer(containerId).stop();
                            jwplayer.api.destroyPlayer(containerId);
                            //jwplayer(containerId).remove();
                        }
                    }
                }

                this._hasFlvPlayer = false;
            },
            _initializeFlvPlayers: function()
            {
                var flashPlayerUrl = BX.type.isNotEmptyString(BX.YNSIRActivityEditor["flashPlayerUrl"]) ? BX.YNSIRActivityEditor["flashPlayerUrl"] : '';
                if(flashPlayerUrl === '')
                {
                    return;
                }

                for(var i = 0; i < this._storageElementInfos.length; i++)
                {
                    var info = this._storageElementInfos[i];
                    if(!BX.YNSIRActivityEditor.isAudioVideoFile(info['ext']))
                    {
                        continue;
                    }

                    var containerId = BX.type.isNotEmptyString(info['containerId']) ? info['containerId'] : '';
                    var url = BX.type.isNotEmptyString(info['url']) ? info['url'] : '';
                    if(containerId === '' || url === '')
                    {
                        continue;
                    }

                    var container = BX(containerId);

                    BX.cleanNode(container);
                    var playerContainerId = containerId + 'FwPlayer';

                    container.appendChild(
                        BX.create(
                            'DIV',
                            {
                                attrs: { className: 'bx-crm-dialog-activity-fwplayer-wrapper' },
                                children:
                                    [
                                        BX.create(
                                            'DIV',
                                            {
                                                attrs: { id: playerContainerId }
                                            }
                                        )
                                    ]
                            }
                        )
                    );
                    container.appendChild(
                        BX.create(
                            'A',
                            {
                                attrs:
                                    {
                                        className: 'bx-crm-dialog-activity-fwplayer-download-link',
                                        href: url
                                    },
                                text: this.getMessage('download')
                            }
                        )
                    );

                    var name = BX.type.isNotEmptyString(info['name']) ? info['name'] : '';
                    if(this._storageTypeId === BX.YNSIRActivityStorageType.webdav)
                    {
                        //HACK: for JWPlayer we have to append file name to webdav URL
                        if(name === '')
                        {
                            name = 'dummy.flv';
                        }

                        var fileNameRx = new RegExp(name + '$', 'i');
                        if(!fileNameRx.test(url))
                        {
                            url += name;
                        }
                    }

                    var player = jwplayer(playerContainerId);
                    if(player)
                    {
                        player.setup(
                            {
                                'players':[{'type': 'html5'},{'type': 'flash', 'src': flashPlayerUrl}],
                                'file': url,
                                'provider': 'video',
                                'controlbar': 'bottom',
                                'logo.hide': 'true',
                                'allowfullscreen': 'false',
                                'icon': 'false',
                                'height': '24px',
                                'width': '300px'
                            }
                        );
                    }
                }
            },
            _handleDateInputClick: function(e)
            {
                var inputId = this._dlgCfg['startDate'];
                BX.calendar({ node: BX(inputId), field: inputId, bTime: false, serverTime: this.getSetting('serverTime', ''), bHideTimebar: true });
            },
            _handleDateImageMouseOver: function(e)
            {
                BX.addClass(e.target, 'calendar-icon-hover');
            },
            _handleDateImageMouseOut: function(e)
            {
                if(!e)
                {
                    e = window.event;
                }

                BX.removeClass(e.target, 'calendar-icon-hover');
            },
            _createSelect: function(selectSettings, optionSettings)
            {
                var select = BX.create('SELECT', selectSettings);
                for(var i = 0; i < optionSettings.length; i++)
                {
                    var setting = optionSettings[i];
                    if(!setting['value'])
                    {
                        continue;
                    }

                    if(!setting['text'])
                    {
                        setting['text'] = setting['value'];
                    }

                    var option = BX.create('OPTION', optionSettings[i]);

                    if(!BX.browser.isIE)
                    {
                        select.add(option,null);
                    }
                    else
                    {
                        try
                        {
                            // for IE earlier than version 8
                            select.add(option, select.options[null]);
                        }
                        catch (e)
                        {
                            select.add(option,null);
                        }
                    }
                }
                return select;
            },
            _handleChangeOwnerClick: function(e)
            {
                if(!this.canChangeOwner())
                {
                    return;
                }

                this._openOwnerSelector();
            },
            _openOwnerSelector: function()
            {
                var selectorId = this.getDialogConfigValue('owner_selector_id', '');
                if(selectorId !== '' && obYNSIR && obYNSIR[selectorId])
                {
                    obYNSIR[selectorId].Open();
                }
            },
            _closeOwnerSelector: function()
            {
                var selectorId = this.getDialogConfigValue('owner_selector_id', '');
                if(selectorId !== '' && obYNSIR && obYNSIR[selectorId])
                {
                    obYNSIR[selectorId].Clear();
                    delete obYNSIR[selectorId];
                }
            },
            _handleOwnerSelect: function(settings)
            {
                if(!this.canChangeOwner())
                {
                    return;
                }

                for(var type in settings)
                {
                    if(settings.hasOwnProperty(type))
                    {
                        this._setupOwner(settings[type][0], false);
                        break;
                    }
                }
            },
            _setupOwner: function(settings, readonly)
            {
                readonly = !!readonly;

                this._owner =
                    {
                        'typeName': settings.type.toUpperCase(),
                        'id': parseInt(settings.id)
                    };

                var wrapper = BX(this.getDialogConfigValue('owner_info_wrapper'));
                if(!wrapper)
                {
                    return;
                }

                BX.cleanNode(wrapper, false);

                var container = BX.create(
                    'SPAN',
                    {
                        attrs:
                            {
                                className: 'bx-crm-dialog-owner-info'
                            },
                        children:
                            [
                                BX.create(
                                    'A',
                                    {
                                        attrs:
                                            {
                                                className: 'bx-crm-dialog-owner-info-link',
                                                href: settings.url,
                                                target: '_blank'
                                            },
                                        text: settings.title
                                    }
                                )
                            ]
                    }
                );

                if(!readonly)
                {
                    container.appendChild(
                        BX.create(
                            'SPAN',
                            {
                                attrs:
                                    {
                                        className: 'finder-box-selected-item-icon'
                                    },
                                events:
                                    {
                                        click: BX.delegate(this._handleDeleteOwnerClick, this)
                                    }
                            }
                        )
                    );
                }

                wrapper.appendChild(container);
            },
            _handleDeleteOwnerClick: function(e)
            {
                if(!this.canChangeOwner())
                {
                    return;
                }

                if(!e)
                {
                    e = window.event;
                }

                var btn = e.target;
                if(btn)
                {
                    BX.remove(BX.findParent(btn, { tagName: 'SPAN', className: 'bx-crm-dialog-owner-info' }));
                }

                this._owner = null;
            },
            _prepareOptions: function(items, selectedvalue)
            {
                if(!BX.type.isArray(items))
                {
                    return [];
                }

                var result = [];
                for(var i = 0; i < items.length; i++)
                {
                    var item = items[i];
                    result.push(
                        BX.create(
                            'OPTION',
                            {
                                props:
                                    {
                                        value: item['value'],
                                        text: item['text'],
                                        selected: item['value'] == selectedvalue
                                    }
                            }
                        )
                    );
                }
                return result;
            },
            _handleCallCreation: function(sender)
            {
                var settings = {};
                var ownerType = this.getSetting('ownerType', '');
                var ownerID = parseInt(this.getSetting('ownerID', 0));
                if(ownerType !== '' && ownerID > 0)
                {
                    settings['ownerType'] = ownerType;
                    settings['ownerID'] = ownerID;
                    settings['ownerTitle'] = this.getSetting('ownerTitle', '');
                    settings['ownerUrl'] = this.getSetting('ownerUrl', '');
                }

                settings['subject'] = this.getSetting('subject', '');
                settings['description'] = this.getSetting('description', '');
                settings['priority'] = this.getSetting('priority', '');
                settings['direction'] = BX.YNSIRActivityDirection.outgoing;

                if(this.getType() === BX.YNSIRActivityType.call)
                {
                    settings['communications'] = this.getSetting('communications', []);
                }
                else if(this.getSetting('ownerType', '') === 'DEAL')
                {
                    // Need for custom logic when owner is DEAL (that doesnt have communications)
                    var commData = this.getSetting('communications', []);
                    var comm = BX.type.isArray(commData) && commData.length > 0 ? commData[0] : null;
                    if(comm)
                    {
                        var commEntityType =  comm['entityType'];
                        if(!BX.type.isNotEmptyString(commEntityType))
                        {
                            commEntityType = ownerType;
                        }

                        var commEntityId =  parseInt(comm['entityId']);
                        if(isNaN(commEntityId) || commEntityId <= 0)
                        {
                            commEntityId = ownerID;
                        }

                        var defaultComm = BX.YNSIRActivityEditor.getDefaultCommunication(
                            commEntityType,
                            commEntityId,
                            BX.YNSIRCommunicationType.phone,
                            this.getSetting('serviceUrl', '')
                        );

                        if(defaultComm)
                        {
                            settings['communications'] = [defaultComm.getSettings()];
                        }
                    }
                }

                this._editor.addCall(settings);
            },
            _handleMeetingCreation: function(sender)
            {
                var settings = {};
                var ownerType = this.getSetting('ownerType', '');
                var ownerID = parseInt(this.getSetting('ownerID', 0));
                if(ownerType !== '' && ownerID > 0)
                {
                    settings['ownerType'] = ownerType;
                    settings['ownerID'] = ownerID;
                    settings['ownerTitle'] = this.getSetting('ownerTitle', '');
                    settings['ownerUrl'] = this.getSetting('ownerUrl', '');
                }

                settings['subject'] = this.getSetting('subject', '');
                settings['description'] = this.getSetting('description', '');
                settings['priority'] = this.getSetting('priority', '');

                if(this.getType() === BX.YNSIRActivityType.meeting)
                {
                    settings['location'] = this.getSetting('location', '');
                    settings['communications'] = this.getSetting('communications', []);
                }
                else if(this.getSetting('ownerType', '') === 'DEAL')
                {
                    // Need for custom logic when owner is DEAL (that doesnt have communications)
                    var commData = this.getSetting('communications', []);
                    var comm = BX.type.isArray(commData) && commData.length > 0 ? commData[0] : null;
                    if(comm)
                    {
                        var commEntityType =  comm['entityType'];
                        if(!BX.type.isNotEmptyString(commEntityType))
                        {
                            commEntityType = ownerType;
                        }

                        var commEntityId =  parseInt(comm['entityId']);
                        if(isNaN(commEntityId) || commEntityId <= 0)
                        {
                            commEntityId = ownerID;
                        }

                        var defaultComm = BX.YNSIRActivityEditor.getDefaultCommunication(
                            commEntityType,
                            commEntityId,
                            BX.YNSIRCommunicationType.undefined,
                            this.getSetting('serviceUrl', '')
                        );

                        if(defaultComm)
                        {
                            settings['communications'] = [defaultComm.getSettings()];
                        }
                    }
                }
                this._editor.addMeeting(settings);
            },
            _handleEmailCreation: function(sender)
            {
                var settings = {};
                var ownerType = this.getSetting('ownerType', '');
                var ownerID = parseInt(this.getSetting('ownerID', 0));
                if(ownerType !== '' && ownerID > 0)
                {
                    settings['ownerType'] = ownerType;
                    settings['ownerID'] = ownerID;
                    settings['ownerTitle'] = this.getSetting('ownerTitle', '');
                    settings['ownerUrl'] = this.getSetting('ownerUrl', '');
                }

                if(this.getSetting('ownerType', '') === 'DEAL')
                {
                    // Need for custom logic when owner is DEAL (that doesnt have communications)
                    var commData = this.getSetting('communications', []);
                    var comm = BX.type.isArray(commData) && commData.length > 0 ? commData[0] : null;
                    if(comm)
                    {
                        var commEntityType =  comm['entityType'];
                        if(!BX.type.isNotEmptyString(commEntityType))
                        {
                            commEntityType = ownerType;
                        }

                        var commEntityId =  parseInt(comm['entityId']);
                        if(isNaN(commEntityId) || commEntityId <= 0)
                        {
                            commEntityId = ownerID;
                        }

                        var defaultComm = BX.YNSIRActivityEditor.getDefaultCommunication(
                            commEntityType,
                            commEntityId,
                            BX.YNSIRCommunicationType.email,
                            this.getSetting('serviceUrl', '')
                        );

                        if(defaultComm)
                        {
                            settings['communications'] = [defaultComm.getSettings()];
                        }
                    }
                }

                this._editor.addEmail(settings);
            },
            _handleTaskCreation: function(sender)
            {
                var settings = {}
                var ownerType = this.getSetting('ownerType', '');
                var ownerID = parseInt(this.getSetting('ownerID', 0));
                if(ownerType !== '' && ownerID > 0)
                {
                    settings['ownerType'] = ownerType;
                    settings['ownerID'] = ownerID;
                }

                this._editor.addTask(settings);
            },
            _syncStatus: function()
            {
                var completed = this.getSetting('completed', false);
                var statusTxtEl = this._findElement('status_text');
                if(statusTxtEl)
                {
                    statusTxtEl.innerHTML = BX.YNSIRActivityStatus.getName(this.getType(), completed ? BX.YNSIRActivityStatus.completed : BX.YNSIRActivityStatus.waiting);
                }
            },
            _handleStatusChange: function(e)
            {
                if(!this.getOption('enableInstantEdit', true))
                {
                    return;
                }

                this._isChanged = true;

                if(this._dlgMode === BX.YNSIRDialogMode.edit)
                {
                    this._settings['completed'] = !this.getSetting('completed', false);
                    this._syncStatus();
                }
                else
                {
                    var self = this;
                    this._editor.setActivityCompleted(
                        this.getId(),
                        !this.getSetting('completed', false),
                        function(result)
                        {
                            self._settings['completed'] = !!result['COMPLETED'];
                            self._syncStatus();
                        }
                    );
                }
            },
            _handleTypeChange: function(e)
            {
                if(!this.getOption('enableInstantEdit', true))
                {
                    return;
                }

                var typeTxt = this._findElement('type_text');
                if(!typeTxt)
                {
                    return;
                }

                var menuId = 'crm-activity-type';
                if(typeof(BX.PopupMenu.Data[menuId]) !== 'undefined')
                {
                    BX.PopupMenu.Data[menuId].popupWindow.destroy();
                    delete BX.PopupMenu.Data[menuId];
                }

                var self = this;
                BX.PopupMenu.show(
                    menuId,
                    typeTxt,
                    [
                        { text: BX.YNSIRActivityType.getName(BX.YNSIRActivityType.call), className:'bx-crm-action-type-link', onclick:function(e){ self._setType(BX.YNSIRActivityType.call); this.popupWindow.close(); }},
                        { text: BX.YNSIRActivityType.getName(BX.YNSIRActivityType.meeting), className:'bx-crm-action-type-link', onclick:function(e){ self._setType(BX.YNSIRActivityType.meeting); this.popupWindow.close(); }}
                    ],
                    {
                        offsetTop:0,
                        offsetLeft:-30
                    });
            },
            _syncDirection: function()
            {
                var directionTxtEl = this._findElement('direction');
                if(directionTxtEl)
                {
                    directionTxtEl.innerHTML = BX.YNSIRActivityDirection.getName(BX.YNSIRActivityType.call, parseInt(this.getSetting('direction', BX.YNSIRActivityDirection.incoming)));
                }
            },
            _handleDirectionChange: function(e)
            {
                if(!this.getOption('enableInstantEdit', true))
                {
                    return;
                }

                this._settings['direction'] = parseInt(this.getSetting('direction', BX.YNSIRActivityDirection.outgoing)) === BX.YNSIRActivityDirection.outgoing ? BX.YNSIRActivityDirection.incoming : BX.YNSIRActivityDirection.outgoing;
                this._syncDirection();

                this._settings['completed'] = this._settings['direction'] === BX.YNSIRActivityDirection.incoming;
                this._syncStatus();
            },
            _syncPriority: function()
            {
                var priority = this.getSetting('priority', BX.YNSIRActivityPriority.low);

                var priorityText = this._findElement('priority_text');
                if(!priorityText)
                {
                    return;
                }

                BX.cleanNode(priorityText, false);
                priorityText.className = BX.YNSIRActivityEditor.resolvePriorityClassName(priority);
                priorityText.appendChild(BX.create('I'));
                priorityText.appendChild(document.createTextNode(BX.YNSIRActivityPriority.getName(priority)));
            },
            _setPriority: function(priority)
            {
                this._isChanged = true;

                if(this._dlgMode === BX.YNSIRDialogMode.edit)
                {
                    this._settings['priority'] = priority;
                    this._syncPriority();
                }
                else
                {
                    var self = this;
                    this._editor.setActivityPriority(
                        this.getId(),
                        priority,
                        function(result)
                        {
                            self._settings['priority'] = result['PRIORITY'];
                            self._syncPriority();
                        }
                    );
                }
            },
            _syncType: function()
            {
                var type = this.getSetting('type', BX.YNSIRActivityType.activity);

                var typeTxt = this._findElement('type_text');
                if(!typeTxt)
                {
                    return;
                }

                BX.cleanNode(typeTxt, false);
                typeTxt.appendChild(document.createTextNode(BX.YNSIRActivityType.getName(type)));
            },
            _setType: function(type)
            {
                if(this._dlgMode === BX.YNSIRDialogMode.edit)
                {
                    this._settings['type'] = type;
                    this._syncType();
                }
            },
            _handleEditDescriptionFocus: function(e)
            {
                var descrElem = this._findElement('description');
                if(descrElem)
                {
                    BX.addClass(descrElem, 'bx-crm-dialog-description-form-active');
                    if(!this.getDialogParam('hasDescription', false))
                    {
                        descrElem.value = '';
                    }
                }
            },
            _handleEditDescriptionBlur: function(e)
            {
                var descrElem = this._findElement('description');
                if(descrElem)
                {
                    BX.removeClass(descrElem, 'bx-crm-dialog-description-form-active');

                    var hasDescription = descrElem.value !== '';
                    this.setDialogParam('hasDescription', hasDescription);
                    if(!hasDescription)
                    {
                        descrElem.value = this.getType() == BX.YNSIRActivityType.meeting ? BX.YNSIRActivityCalEvent.messages['meetingDescrHint'] : BX.YNSIRActivityCalEvent.messages['callDescrHint'];
                    }
                }
            },
            _handlePriorityChange: function(e)
            {
                if(!this.getOption('enableInstantEdit', true))
                {
                    return;
                }

                var priorityTxt = this._findElement('priority_text');
                if(!priorityTxt)
                {
                    return;
                }

                var menuId = 'crm-activity-priority';
                if(typeof(BX.PopupMenu.Data[menuId]) !== 'undefined')
                {
                    BX.PopupMenu.Data[menuId].popupWindow.destroy();
                    delete BX.PopupMenu.Data[menuId];
                }

                var self = this;
                BX.PopupMenu.show(
                    menuId,
                    priorityTxt,
                    [
                        { text: BX.YNSIRActivityPriority.getName(BX.YNSIRActivityPriority.low), className:'bx-crm-priority-low-link lead-menu-imp-active', onclick:function(e){ self._setPriority(BX.YNSIRActivityPriority.low); this.popupWindow.close(); }},
                        { text: BX.YNSIRActivityPriority.getName(BX.YNSIRActivityPriority.medium), className:'bx-crm-priority-medium-link', onclick:function(e){ self._setPriority(BX.YNSIRActivityPriority.medium); this.popupWindow.close(); }},
                        { text: BX.YNSIRActivityPriority.getName(BX.YNSIRActivityPriority.high), className:'bx-crm-priority-high-link', onclick:function(e){ self._setPriority(BX.YNSIRActivityPriority.high); this.popupWindow.close(); }}
                    ],
                    {
                        offsetTop:0,
                        offsetLeft:-30
                    });
            },
            handleNotifyToggle: function(e)
            {
                BX.toggleClass(this._findElement('enableNotifyWrapper'), 'bx-crm-dialog-remind-wrapper-hidden');
            },
            _handleNotifyTypeChange: function(e)
            {
                var notifyTypeInput = this._findElement('notifyType');
                var type =  BX.YNSIRActivityNotifyType.getNext(notifyTypeInput.value);
                notifyTypeInput.value = type;
                this._findElement('notifyTypeSwitch').innerHTML = BX.YNSIRActivityNotifyType.getName(type);
            },
            getDialogValue: function(alias, defaultval)
            {
                var el = this._findElement(alias);
                return el ? el.value : defaultval;
            },
            getDialogConfigValue: function(name, defaultval)
            {
                var cfg = this._dlgCfg;
                return cfg && typeof(cfg[name]) != 'undefined' ? cfg[name] : defaultval;
            },
            setDialogConfigValue: function(name, val)
            {
                this._dlgCfg[name] = val;
            },
            getDialogForm: function()
            {
                var cfg = this._dlgCfg;
                return cfg && typeof(cfg['form']) != 'undefined' ? document.forms[cfg['form']] : null;
            },
            _findElement: function(alias)
            {
                return BX.YNSIRActivityEditor.findDialogElement(this._dlgCfg, alias)
            },
            getDialogElements: function(name)
            {
                var cfg = this._dlgCfg;
                if(!cfg)
                {
                    return [];
                }

                var form = document.forms[cfg['form']];
                if(!form || !form.elements[name])
                {
                    return [];
                }

                return form.elements[name];
            },
            getDialogParam: function(name, defaultval)
            {
                var cfg = this._dlgCfg;
                return cfg && typeof(cfg[name]) !== 'undefined' ? cfg[name] : defaultval;
            },
            setDialogParam: function(name, val)
            {
                this._dlgCfg[name] = val;
            },
            _handleCancelButtonClick: function()
            {
                this._buttonId = BX.YNSIRActivityDialogButton.cancel;
                this._notifyDialogClose();
                this._releaseFlvPlayers();
                this._dlg.close();
            },
            _handleAcceptButtonClick: function(e)
            {
                if(!this._communicationsReady)
                {
                    return;
                }

                if(this._communicationSearch)
                {
                    this._communicationSearch.closeDialog();
                }

                if(!this._dlg)
                {
                    return;
                }

                if(this._dlgMode == BX.YNSIRDialogMode.view)
                {
                    this._buttonId = BX.YNSIRActivityDialogButton.edit;
                    this._notifyDialogClose();
                    this._dlg.close();
                    return;
                }

                if(this._requestIsRunning)
                {
                    return;
                }

                this._buttonId = BX.YNSIRActivityDialogButton.save;
                this._isChanged = true;

                var type = this.getType();
                var srcData = {};

                srcData['ID'] = this.getId();

                //start
                var startDate = this.getDialogValue('startDate', '');
                if(!BX.type.isNotEmptyString(startDate))
                {
                    startDate = BX.formatDate(null, BX.message('FORMAT_DATE'));
                }

                var startTime = this.getDialogValue('startTime', '');
                if(!BX.type.isNotEmptyString(startTime))
                {
                    startTime = BX.formatDate(null, 'HH:MI');
                }

                srcData['start'] = BX.YNSIRActivityEditor.joinDateTime(startDate, startTime);

                if(this._findElement('enableNotify').checked)
                {
                    srcData['notify'] =
                        {
                            value: this.getDialogValue('notifyVal', ''),
                            type: this.getDialogValue('notifyType', '')
                        };
                }

                srcData['type'] = this.getSetting('type', type);
                srcData['priority'] = this.getSetting('priority', BX.YNSIRActivityPriority.medium);
                srcData['location'] = this.getDialogValue('location', '');
                srcData['subject'] = this.getDialogValue('subject', '');
                srcData['description'] = this.getDialogParam('hasDescription', false) ? this.getDialogValue('description', '') : '';
                srcData['completed'] = this.getSetting('completed', false) ? 1 : 0;
                srcData['responsibleID'] = this.getDialogValue('responsibleData', '');


                var ownerType = '';
                var ownerID = 0;

                if(this.canChangeOwner())
                {
                    ownerType = this._owner ? this._owner['typeName'] : '';
                    ownerID = this._owner ? parseInt(this._owner['id']) : 0;
                }

                if(ownerType === '' || ownerID <= 0)
                {
                    var originalOwnerType = this.getSetting('ownerType', '');
                    var originalOwnerID = parseInt(this.getSetting('ownerID', 0));

                    //Transfer ownership to communication if origin owner is not DEAL.
                    if(originalOwnerType !== 'DEAL' && this._communication)
                    {
                        ownerType = this._communication.getEntityType();
                        ownerID = this._communication.getEntityId();
                    }
                    else
                    {
                        ownerType = originalOwnerType;
                        ownerID = originalOwnerID;
                    }

                    if(ownerType === '' || ownerID <= 0)
                    {
                        this._clearError();
                        this._showError(this.getMessage('ownerNotDefined'));
                        return;
                    }
                }

                srcData['ownerType'] = ownerType;
                srcData['ownerID'] = ownerID;

                if(type == BX.YNSIRActivityType.call)
                {
                    srcData['direction'] = this.getSetting('direction', BX.YNSIRActivityDirection.outgoing);
                }

                // communication
                if(this._communication)
                {
                    srcData['communication'] =
                        {
                            id: this._communication.getId(),
                            type: this._communication.getType(),
                            entityType: this._communication.getEntityType(),
                            entityId: this._communication.getEntityId(),
                            value: this._communication.getValue()
                        };
                }

                if(this._disableStorageEdit)
                {
                    srcData['disableStorageEdit'] = 'Y';
                }
                else
                {
                    srcData['storageTypeID'] = this._storageTypeId;
                    if(this._storageTypeId === BX.YNSIRActivityStorageType.webdav)
                    {
                        srcData['webdavelements'] = this._editor.getWebDavUploaderValues(this._uploaderName);
                    }
                    else if(this._storageTypeId === BX.YNSIRActivityStorageType.disk)
                    {
                        srcData['diskfiles'] = this._editor.getDiskUploaderValues(this._uploaderName);
                    }
                    else
                    {
                        srcData['files'] = this._editor.getFileUploaderValues(this.getDialogElements(this.getSetting('uploadInputID', '') + '[]'));
                        var controlId = this.getSetting('uploadControlID', '');
                        if(typeof(BX.CFileInput) !== 'undefined'
                            && typeof(BX.CFileInput.Items[controlId]) !== 'undefined')
                        {
                            srcData['uploadControlCID'] = BX.CFileInput.Items[controlId].CID;
                        }
                    }
                }

                this._requestIsRunning = true;
                this._lockSaveButton();
                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        action: 'save_activity'
                    }
                );
                var self = this;
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'SAVE_ACTIVITY',
                                'DATA': srcData
                            },
                        onsuccess: function(data)
                        {
                            if(typeof(data['ERROR']) != 'undefined')
                            {
                                self._clearError();
                                self._showError(data['ERROR']);
                            }
                            else
                            {
                                self._closeOwnerSelector();
                                self._notifySave(data);
                                self._notifyDialogClose();
                                self._dlg.close();
                            }
                            self._requestIsRunning = false;
                            self._unlockSaveButton();
                        },
                        onfailure: function(data)
                        {
                            self._clearError();
                            self._showError(data);

                            self._requestIsRunning = false;
                            self._unlockSaveButton();
                        }
                    }
                );
            },
            _openCommunicationDialog: function(e)
            {
                var container = this._findElement('contacts');
                if(!container)
                {
                    return;
                }

                var cfg = this._dlgCfg;
                if(!cfg['contactSearch'])
                {
                    cfg['contactSearch'] = this._prepareCode('contactSearch', this._salt);
                    var quickSearch = BX(cfg['contactSearch']);
                    if(!quickSearch)
                    {
                        quickSearch = BX.create(
                            'INPUT',
                            {
                                attrs: { className:'bx-crm-dialog-comm-search' },
                                props: { id: cfg['contactSearch'], type: 'text' },
                                events: { keypress: BX.delegate(this._handleQuickSearchKeyPress, this) }
                            }
                        );
                        container.appendChild(quickSearch);
                    }
                    quickSearch.focus();

                    if(!this._communicationSearchController)
                    {
                        this._communicationSearchController = BX.YNSIRCommunicationSearchController.create(this._communicationSearch, quickSearch);
                    }
                    this._communicationSearchController.start();
                }

                this._communicationSearch.openDialog(container, BX.delegate(this._handleCommunicationDialogClose, this));
            },
            _getQuickSearch: function()
            {
                return this._dlgCfg['contactSearch'] ? BX(this._dlgCfg['contactSearch']) : null;
            },
            _handleCommunicationDialogClose: function()
            {
                if(this._communicationSearchController)
                {
                    this._communicationSearchController.stop();
                    this._communicationSearchController = null;
                }

                var contactSearch = this._findElement('contactSearch');
                if(contactSearch)
                {
                    BX.remove(contactSearch);
                    delete(this._dlgCfg['contactSearch']);
                }
            },
            _addCommunication: function(settings)
            {
                if(!settings)
                {
                    return;
                }

                if(this._communication)
                {
                    this._communication.cleanupLayout();
                }

                settings['mode'] = this._dlgMode;
                settings['callToFormat'] = this.getSetting('callToFormat', BX.YNSIRCalltoFormat.slashless);

                this._communication = BX.YNSIRActivityCommunication.create(settings, this);
                this._communication.layout(this._findElement('contacts'), this._getQuickSearch());
                if(this._communicationSearch)
                {
                    this._communicationSearch.adjustDialogPosition();
                }
                this.validate();
            },
            _prepareCommunicationWaiter: function()
            {
                if(!this._communicationWaiter)
                {
                    this._communicationWaiter = BX.create(
                        'DIV',
                        {
                            'attrs': { 'className': 'bx-crm-dialog-comm-wait-wrapper' },
                            'text' : BX.YNSIRActivityEditor.getMessage('dataLoading')
                        }
                    );
                }

                return this._communicationWaiter;
            },
            processDataLoadingResponse: function(responseData)
            {
                var communicationBlock = this._findElement('contacts');
                if(this._communicationWaiter)
                {
                    communicationBlock.removeChild(this._communicationWaiter);
                }

                var communications;
                if(typeof(responseData['ACTIVITY_COMMUNICATIONS']) !== 'undefined')
                {
                    var commData =  typeof(responseData['ACTIVITY_COMMUNICATIONS']['DATA']) !== 'undefined'
                        ? responseData['ACTIVITY_COMMUNICATIONS']['DATA'] : {};

                    communications = commData['COMMUNICATIONS'] && BX.type.isArray(commData['COMMUNICATIONS'])
                        ? commData['COMMUNICATIONS'] : [];

                    this.setSetting('communications', communications);
                    this.setSetting('communicationsLoaded', true);
                    if(communications.length > 0)
                    {
                        this._addCommunication(communications[0]);
                    }

                    this._editor.setActivityCommunications(this.getId(), communications);
                }

                this._communicationsReady = true;
                if(this._communicationSearch && !this._communicationSearch.isDataLoaded())
                {
                    this._communicationSearch.processDataResponse(responseData);
                }

                if(this.getId() <= 0
                    && this._dlgMode === BX.YNSIRDialogMode.edit
                    && !this._communication)
                {
                    var defaultComm = this._communicationSearch ? this._communicationSearch.getDefaultCommunication() : null;
                    if(defaultComm)
                    {
                        this._addCommunication(defaultComm.getSettings());
                    }
                }

            },
            _loadCommunications: function()
            {
                this._communicationsReady = false;
                if(!this._communicationWaiter)
                {
                    this._communicationWaiter = BX.create(
                        'DIV',
                        {
                            'attrs': { 'className': 'bx-crm-dialog-comm-wait-wrapper' },
                            'text' : BX.YNSIRActivityEditor.getMessage('dataLoading')
                        }
                    );
                }
                var waiter = this._communicationWaiter;
                var communicationBlock = this._findElement('contacts');

                communicationBlock.appendChild(waiter);

                var self = this;
                this._editor.getActivityCommunications(
                    this.getId(),
                    function(commData)
                    {
                        if(!BX.type.isArray(commData))
                        {
                            commData = [];
                        }

                        self.setSetting('communications', commData);
                        self.setSetting('communicationsLoaded', true);

                        communicationBlock.removeChild(waiter);
                        if(commData.length > 0)
                        {
                            self._addCommunication(commData[0]);
                        }
                        self._communicationsReady = true;
                    }
                );
            },
            _handleCommunicationSelect: function(item)
            {
                this._communicationSearch.closeDialog();
                this._addCommunication(item.getSettings());
            },
            deleteCommunication: function(comm)
            {
                this._communication = null;
                this.validate();
            },
            _prepareCode: function(code, salt)
            {
                salt = BX.type.isNotEmptyString(salt) ? salt : '';
                var prefix = this.getSetting('prefix', '');
                return (prefix.length > 0 ? (prefix + '_') : '') + (salt.length > 0 ? (salt + '_') : '') + code;
            },
            addOnSave: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onSaveHandlers.length; i++)
                {
                    if(this._onSaveHandlers[i] == handler)
                    {
                        return;
                    }
                }

                this._onSaveHandlers.push(handler);

            },
            removeOnSave: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onSaveHandlers.length; i++)
                {
                    if(this._onSaveHandlers[i] == handler)
                    {
                        this._onSaveHandlers.splice(i, 1);
                        return;
                    }
                }

            },
            _notifySave: function(params)
            {
                this._notify(this._onSaveHandlers, [ this, params ]);
            },
            addOnDialogClose: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onDlgCloseHandlers.length; i++)
                {
                    if(this._onDlgCloseHandlers[i] == handler)
                    {
                        return;
                    }
                }

                this._onDlgCloseHandlers.push(handler);

            },
            removeOnDialogClose: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onDlgCloseHandlers.length; i++)
                {
                    if(this._onDlgCloseHandlers[i] == handler)
                    {
                        this._onDlgCloseHandlers.splice(i, 1);
                        return;
                    }
                }

            },
            _notifyDialogClose: function()
            {
                this._notify(this._onDlgCloseHandlers, [ this ]);
            },
            _notify: function(handlers, eventArgs)
            {
                var ary = [];
                for(var i = 0; i < handlers.length; i++)
                {
                    ary.push(handlers[i]);
                }

                for(var j = 0; j < ary.length; j++)
                {
                    try
                    {
                        ary[j].apply(this, eventArgs ? eventArgs : []);
                    }
                    catch(ex)
                    {
                    }
                }
            },
            _tryAddCustomCommunication: function(val)
            {
                if(!BX.type.isNotEmptyString(val))
                {
                    return false;
                }

                this._addCommunication(
                    {
                        entityId: '0',
                        entityTitle: '',
                        entityType: 'CONTACT',
                        type: this.getType() === BX.YNSIRActivityType.call ? 'PHONE' : '',
                        value: val
                    }
                );
                return true;
            },
            _handleQuickSearchKeyPress: function(e)
            {
                if(!e)
                {
                    e = window.event;
                }

                if(e.keyCode !== 13 && e.keyCode !== 27)
                {
                    return;
                }

                var quickSearch = this._getQuickSearch();
                if(!quickSearch)
                {
                    return;
                }

                if(e.keyCode === 27) //escape
                {
                    quickSearch.value = ''; //??
                    quickSearch.focus();
                    return;
                }

                if(this._tryAddCustomCommunication(quickSearch.value, true))
                {
                    quickSearch.value = '';
                    quickSearch.focus();
                }
            },
            validate: function()
            {
                this._clearError();

                if(this._communication && !this._communication.isValid())
                {
                    this._showError(this._communication.getError());
                }
            },
            _showError: function(messages)
            {
                var error = BX(this._dlgCfg['error']);
                if(!error)
                {
                    return;
                }

                if(!BX.type.isArray(messages))
                {
                    messages = [ messages ];
                }

                for(var i = 0; i < messages.length; i++)
                {
                    error.appendChild(
                        BX.create(
                            'P',
                            {
                                text: messages[i]
                            }
                        )
                    );
                }
                error.style.display = '';

                if(this._communicationSearch)
                {
                    this._communicationSearch.adjustDialogPosition();
                }
            },
            _clearError: function()
            {
                var error = BX(this._dlgCfg['error']);
                if(!error)
                {
                    return;
                }

                error.innerHTML = '';
                error.style.display = 'none';

                if(this._communicationSearch)
                {
                    this._communicationSearch.adjustDialogPosition();
                }
            },
            _handleExpand: function()
            {
                if(!this._dlg)
                {
                    return;
                }

                if(!this._expanded)
                {
                    BX.addClass(this._dlg.popupContainer, 'bx-crm-dialog-activity-call-event-wide');
                    BX.removeClass(this._dlg.popupContainer, 'bx-crm-dialog-activity-call-event');
                }
                else
                {
                    BX.addClass(this._dlg.popupContainer, 'bx-crm-dialog-activity-call-event');
                    BX.removeClass(this._dlg.popupContainer, 'bx-crm-dialog-activity-call-event-wide');
                }

                this._expanded = !this._expanded;

                var size = BX.GetWindowInnerSize(document);
                var scroll = BX.GetWindowScrollPos(document);
                var pos = BX.pos(this._dlg.popupContainer);

                this._dlg.popupContainer.style.left = (scroll.scrollLeft + (size.innerWidth - pos.width) / 2) + 'px';
                this._dlg.popupContainer.style.top = (scroll.scrollTop + (size.innerHeight - pos.height) / 2) + 'px';
            }
        };
    BX.YNSIRActivityCalEvent.dialogs = {};
    BX.YNSIRActivityCalEvent.create = function(settings, editor, options)
    {
        var self = new BX.YNSIRActivityCalEvent();
        self.initialize(settings, editor, options);
        return self;
    };
    BX.YNSIRActivityEmail = function()
    {
        this._settings = {};
        this._options = {};
        this._cntWrapper = null;
        this._ttlWrapper = null;
        this._dlg = null;
        this._dlgMode = BX.YNSIRDialogMode.view;
        this._dlgCfg = {};
        this._onSaveHandlers = [];
        this._onDlgCloseHandlers = [];
        this._editor = null;
        this._communications = [];
        this._communicationSearch = null;
        this._communicationSearchController = null;
        this._isChanged = false;
        this._buttonId = BX.YNSIRActivityDialogButton.undefined;
        this._uploaderName = 'email_uploader';
        this._storageTypeId = BX.YNSIRActivityStorageType.undefined;
        this._owner = null;
        this._salt = '';
        this._callCreationHandler = BX.delegate(this._handleCallCreation, this);
        this._meetingCreationHandler = BX.delegate(this._handleMeetingCreation, this);
        this._emailCreationHandler = BX.delegate(this._handleEmailCreation, this);
        this._taskCreationHandler = BX.delegate(this._handleTaskCreation, this);
        this._expandHandler = BX.delegate(this._handleExpand, this);
        this._titleMenu = null;
        this._expanded = false;
        this._templateSelector = null;
        this._templateId = 0;
        this._communicationWaiter = null;
        this._communicationsReady = true;
        this._showAllCommunicationsButton = null;
        this._requestIsRunning = false;
        this._paginator = null;
    };

    BX.YNSIRActivityEmail.prototype =
        {
            initialize: function(settings, editor, options)
            {
                this._settings = settings ? settings : {};
                this._editor = editor;
                this._options = options ? options : {};

                this._isChanged = this.getOption('markChanged', false);

                var ownerType = this.getSetting('ownerType', '');
                var ownerID =this.getSetting('ownerID', '');
                this._salt = Math.random().toString().substring(2);

                if(typeof(BX.YNSIRCommunicationSearch) !== 'undefined')
                {
                    this._communicationSearch = BX.YNSIRCommunicationSearch.create(
                        'COMM_SEARCH_' + ownerType + '_' + ownerID,
                        {
                            'entityType' : ownerType,
                            'entityId': ownerID,
                            'serviceUrl': this.getSetting('serviceUrl', ''),
                            'communicationType': BX.YNSIRCommunicationType.email,
                            'selectCallback': BX.delegate(this._handleCommunicationSelect, this),
                            'enableSearch': true,
                            'enableDataLoading': false
                        }
                    );
                }
            },
            getMode: function()
            {
                return this._dlgMode;
            },
            getSetting: function (name, defaultval)
            {
                return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
            },
            setSetting: function(name, val)
            {
                this._settings[name] = val;
            },
            getOption: function (name, defaultval)
            {
                return typeof(this._options[name]) != 'undefined' ? this._options[name] : defaultval;
            },
            getMessage: function(name)
            {
                return BX.YNSIRActivityEmail.messages && BX.YNSIRActivityEmail.messages[name] ? BX.YNSIRActivityEmail.messages[name] : '';
            },
            getDialogValue: function(alias, defaultval)
            {
                var el = this._findElement(alias);
                return el ? el.value : defaultval;
            },
            setDialogValue: function(alias, val)
            {
                var el = this._findElement(alias);
                if(el)
                {
                    el.value = val;
                }
            },
            getDialogConfigValue: function(name, defaultval)
            {
                var cfg = this._dlgCfg;
                return cfg && typeof(cfg[name]) != 'undefined' ? cfg[name] : defaultval;
            },
            setDialogConfigValue: function(name, val)
            {
                this._dlgCfg[name] = val;
            },
            getType: function()
            {
                return BX.YNSIRActivityType.email;
            },
            getId: function()
            {
                return parseInt(this.getSetting('ID', '0'));
            },
            getMessageType: function()
            {
                return this.getSetting('messageType', '');
            },
            getOwnerType: function()
            {
                return this.getSetting('ownerType', '');
            },
            getOwnerId: function()
            {
                return this.getSetting('ownerID', '');
            },
            canChangeOwner: function()
            {
                if(this.getMode() !== BX.YNSIRDialogMode.edit || this.getId() > 0)
                {
                    return false;
                }

                var ownerType = this.getOwnerType();
                if(ownerType === 'LEAD')
                {
                    return false;
                }

                return ownerType !== 'DEAL' || this._editor.getOwnerType() !== 'DEAL';
            },
            displayOwner: function()
            {
                return this.getSetting('ownerType', '') === 'DEAL';
            },
            getDefaultStorageTypeId: function()
            {
                return parseInt(this.getSetting('defaultStorageTypeId', BX.YNSIRActivityStorageType.file));
            },
            openDialog: function(mode)
            {
                var id = this.getId();

                if(!mode)
                {
                    mode = id > 0 ? BX.YNSIRDialogMode.view : BX.YNSIRDialogMode.edit;
                }

                if (mode === BX.YNSIRDialogMode.view && BX.YNSIRActivityProvider)
                {
                    var activity = BX.YNSIRActivityProvider.create(this._settings, this._editor, this._options, this);
                    window.setTimeout(function(){activity.openDialog(mode);}, 10);
                    return;
                }

                this._dlgMode = mode;

                var dlgId = 'YNSIRActivityEmail'
                    + (mode === BX.YNSIRDialogMode.edit ? 'Edit' : 'View')
                    + id;

                if(BX.YNSIRActivityEmail.dialogs[dlgId])
                {
                    return;
                }

                var self = this;
                this._dlg = new BX.PopupWindow(
                    dlgId,
                    null,
                    {
                        className: 'bx-crm-dialog-wrap bx-crm-dialog-activity-email',
                        autoHide: false,
                        draggable: true,
                        offsetLeft: 0,
                        offsetTop: 0,
                        bindOptions: { forceBindPosition: false },
                        closeByEsc: false,
                        closeIcon: true,
                        zIndex: -12, //HACK: for tasks popup
                        titleBar:
                            {
                                content:  mode == BX.YNSIRDialogMode.edit
                                    ? this._prepareEditDlgTitle()
                                    : this._prepareViewDlgTitle()
                            },
                        events:
                            {
                                onPopupShow: BX.delegate(this._onDlgShow, this),
                                onAfterPopupShow: BX.delegate(this._onAfterDlgShow, this),
                                onPopupClose: BX.delegate(
                                    function()
                                    {
                                        self._communicationSearch.closeDialog();
                                        BX.YNSIRActivityEditor.hideUploader(self.getSetting('uploadID', ''), self.getSetting('uploadControlID', ''));
                                        BX.YNSIRActivityEditor.hideLhe(self.getSetting('lheContainerID', ''));

                                        self._dlg.destroy();
                                    },
                                    this
                                ),
                                onPopupDestroy: BX.proxy(
                                    function()
                                    {
                                        self._dlg = null;
                                        self._wrapper = null;
                                        self._ttlWrapper = null;
                                        delete(BX.YNSIRActivityEmail.dialogs[dlgId]);
                                    },
                                    this
                                )
                            },
                        content: mode == BX.YNSIRDialogMode.edit
                            ? this._prepareEditDlgContent(dlgId)
                            : this._prepareViewDlgContent(dlgId),
                        buttons: mode == BX.YNSIRDialogMode.edit
                            ? this._prepareEditDlgButtons()
                            : this._prepareViewDlgButtons()
                    }
                );

                BX.YNSIRActivityEmail.dialogs[dlgId] = this._dlg;
                var dataRequestParams = null;
                if(mode === BX.YNSIRDialogMode.edit)
                {
                    var isCommunicationsLoaded = this.getSetting('communicationsLoaded', true);
                    //Display all communications in 'edit' mode
                    if(id > 0 && !isCommunicationsLoaded)
                    {
                        dataRequestParams = { 'ACTIVITY_COMMUNICATIONS': { 'ID': id } };
                    }

                    if(this._communicationSearch && !this._communicationSearch.isDataLoaded())
                    {
                        if(!dataRequestParams)
                        {
                            dataRequestParams = {};
                        }
                        this._communicationSearch.prepareDataRequest(dataRequestParams);
                    }

                    if(isCommunicationsLoaded)
                    {
                        this._communicationsReady = false;
                        this._prepareCommunications();
                        if(id <= 0 && this._communications.length === 0 && this.getMessageType() === '')
                        {
                            var defaultComm = this._communicationSearch ? this._communicationSearch.getDefaultCommunication() : null;
                            if(defaultComm)
                            {
                                this._addCommunication(defaultComm.getSettings());
                            }
                        }
                    }
                    if(dataRequestParams)
                    {
                        this._communicationsReady = isCommunicationsLoaded;
                        if(!isCommunicationsLoaded)
                        {
                            this._findElement('to').appendChild(this._prepareCommunicationWaiter());
                        }
                        this._editor.getActivityViewData(dataRequestParams, BX.delegate(this.processViewDataResponse, this));
                    }
                }
                else //mode === BX.YNSIRDialogMode.view
                {
                    var pageSize = 20;
                    var pageNumber = 1;
                    var paginatorId = this._editor.getId().toString() + '_' + this.getId().toString() + '_' + pageSize.toString();
                    if(typeof(BX.YNSIRActivityCommunicationPaginator.items[paginatorId]) !== "undefined")
                    {
                        this._paginator = BX.YNSIRActivityCommunicationPaginator.items[paginatorId];
                    }
                    else
                    {
                        this._paginator = BX.YNSIRActivityCommunicationPaginator.create(
                            paginatorId,
                            BX.YNSIRParamBag.create({ editor: this._editor, activityId: this.getId(), pageSize: pageSize })
                        );
                    }

                    this._findElement('to').appendChild(this._prepareCommunicationWaiter());
                    dataRequestParams =
                        {
                            'ACTIVITY_COMMUNICATIONS_PAGE': { 'ID': id, 'PAGE_SIZE': pageSize, 'PAGE_NUMBER': pageNumber }
                        };

                    if(this._communicationSearch && !this._communicationSearch.isDataLoaded())
                    {
                        this._communicationSearch.prepareDataRequest(dataRequestParams);
                    }
                    this._editor.getActivityViewData(dataRequestParams, BX.delegate(this.processViewDataResponse, this));
                }

                if(this.displayOwner())
                {
                    this._setupOwner(
                        {
                            'type': this.getSetting('ownerType', ''),
                            'id': this.getSetting('ownerID', '0'),
                            'title': this.getSetting('ownerTitle', ''),
                            'url': this.getSetting('ownerUrl', '')
                        },
                        !this.canChangeOwner()
                    );
                }

                //Initialize owner selector gianglh
                if(this.canChangeOwner())
                {
                    // window.setTimeout(
                    //     BX.delegate(
                    //         function()
                    //         {
                    //             var selectorId = this._editor.createOwnershipSelector(this._dlgID, BX(this.getDialogConfigValue('change_owner_button')));
                    //             obYNSIR[selectorId].AddOnSaveListener(BX.delegate(this._handleOwnerSelect, this));
                    //             this.setDialogConfigValue('owner_selector_id', selectorId);
                    //         }, this
                    //     ),
                    //     0
                    // );
                }

                this._dlg.show();

                if(mode === BX.YNSIRDialogMode.edit)
                {
                    var from = this.getSetting('lastUsedEmail', '');

                    if(from !== '')
                    {
                        this.setDialogValue('from', from);
                    }

                    if(typeof(this._dlgCfg['template']) !== 'undefined')
                    {
                        var items =
                            [
                                {
                                    'value': 0,
                                    'text': this.getMessage('noTemplate'),
                                    'enabled': true,
                                    'default': true
                                }
                            ];

                        var data = this.getSetting('mailTemplateData', []);
                        for(var j = 0; j < data.length; j++)
                        {
                            var info = data[j];
                            items.push(
                                {
                                    'value': parseInt(info['id']),
                                    'text': info['title']
                                }
                            );
                        }

                        var selector = this._templateSelector = BX.YNSIRSelector.create(
                            this._dlgCfg['template'],
                            {
                                'container': BX(this._dlgCfg['template']),
                                'title': '',
                                'selectedValue': '',
                                'items': items,
                                'layout': { 'insertBefore': { 'className': 'crm-view-actions' } }
                            }
                        );

                        selector.layout();
                        selector.addOnSelectListener(BX.delegate(this._handleTemplateChange, this));
                    }
                }
            },
            _onDlgShow: function()
            {
                if(this._ttlWrapper)
                {
                    BX.bind(BX.findParent(this._ttlWrapper, { 'class': 'popup-window-titlebar' }), 'dblclick', this._expandHandler);
                }
            },
            _onAfterDlgShow: function()
            {
                var lhe = BXHtmlEditor.Get(this.getSetting('lheJsName'));
                if (lhe)
                {
                    lhe.CheckAndReInit();
                    lhe.ResizeSceleton(0, 198);

                    if (this._dlgMode === BX.YNSIRDialogMode.edit)
                    {
                        var descr = this.getSetting('descriptionBBCode', '');
                        if(descr === '')
                        {
                            descr = this.getSetting('description', '');
                            descr = descr.replace(/<br\s*?\/?>/ig, '\n');
                        }

                        lhe.SetContent(descr, true);
                    }
                }
            },
            closeDialog: function()
            {
                if(this._communicationSearchController)
                {
                    this._communicationSearchController.stop();
                    this._communicationSearchController = null;
                }

                if(this._communicationSearch)
                {
                    this._communicationSearch.closeDialog();
                }

                if(this._titleMenu)
                {
                    this._titleMenu.removeCreateTaskListener(this._taskCreationHandler);
                    this._titleMenu.removeCreateCallListener(this._callCreationHandler);
                    this._titleMenu.removeCreateMeetingListener(this._meetingCreationHandler);

                    this._titleMenu.cleanLayout();
                }

                if(!this._dlg)
                {
                    return;
                }

                this._notifyDialogClose();
                this._dlg.close();
            },
            addOnSave: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onSaveHandlers.length; i++)
                {
                    if(this._onSaveHandlers[i] == handler)
                    {
                        return;
                    }
                }

                this._onSaveHandlers.push(handler);

            },
            removeOnSave: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onSaveHandlers.length; i++)
                {
                    if(this._onSaveHandlers[i] == handler)
                    {
                        this._onSaveHandlers.splice(i, 1);
                        return;
                    }
                }

            },
            addOnDialogClose: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onDlgCloseHandlers.length; i++)
                {
                    if(this._onDlgCloseHandlers[i] == handler)
                    {
                        return;
                    }
                }

                this._onDlgCloseHandlers.push(handler);

            },
            removeOnDialogClose: function(handler)
            {
                if(!BX.type.isFunction(handler))
                {
                    return;
                }

                for(var i = 0; i < this._onDlgCloseHandlers.length; i++)
                {
                    if(this._onDlgCloseHandlers[i] == handler)
                    {
                        this._onDlgCloseHandlers.splice(i, 1);
                        return;
                    }
                }

            },
            deleteCommunication: function(comm)
            {
                for(var i = 0; i < this._communications.length; i++)
                {
                    if(comm !== this._communications[i])
                    {
                        continue;
                    }

                    this._communications.splice(i, 1);
                    break;
                }

                this.validate();
            },
            applyTemplate: function(templateId)
            {
                var id = this._templateId = parseInt(templateId);
                if(id === 0)
                {
                    // no template
                    this._setupFromTemplate({ 'from': '', 'subject': '', 'body': '' });
                    return;
                }

                var cacheKey = 'TEMPLATE_' + id + '_' + this.getSetting('ownerType') + '_' + this.getSetting('ownerID');
                if(typeof(BX.YNSIRActivityEmail.prepredTemplates[cacheKey]) !== 'undefined')
                {
                    this._setupFromTemplate(BX.YNSIRActivityEmail.prepredTemplates[cacheKey]);
                    return;
                }

                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        action: 'prepare_mail_template',
                        ownertype: this.getSetting('ownerType', ''),
                        ownerid: this.getSetting('ownerID', ''),
                        templateid: id
                    }
                );
                var self = this;
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'PREPARE_MAIL_TEMPLATE',
                                'TEMPLATE_ID': id,
                                'OWNER_TYPE': this.getSetting('ownerType'),
                                'OWNER_ID': this.getSetting('ownerID'),
                                'JOB_ORDER_ID': this.getSetting('job_orderID',0),
                                'CONTENT_TYPE': 'BBCODE'
                            },
                        onsuccess: function(data)
                        {
                            var resultData = data['DATA'];
                            if(resultData)
                            {
                                var template =
                                    {
                                        'from': BX.type.isNotEmptyString(resultData['FROM']) ? resultData['FROM'] : '',
                                        'subject': BX.type.isNotEmptyString(resultData['SUBJECT']) ? resultData['SUBJECT'] : '',
                                        'body': BX.type.isNotEmptyString(resultData['BODY']) ? resultData['BODY'] : ''

                                    };

                                BX.YNSIRActivityEmail.prepredTemplates['TEMPLATE_' + resultData['ID'] + '_' + resultData['OWNER_TYPE'] + '_' + resultData['OWNER_ID']] = template;
                                self._setupFromTemplate(template);
                            }
                        },
                        onfailure: function(data)
                        {
                        }
                    }
                );
            },
            getDialogElements: function(name)
            {
                var cfg = this._dlgCfg;
                if(!cfg)
                {
                    return [];
                }

                var form = document.forms[cfg['form']];
                if(!form || !form.elements[name])
                {
                    return [];
                }

                return form.elements[name];
            },
            isChanged: function()
            {
                return this._isChanged;
            },
            getButtonId: function()
            {
                return this._buttonId;
            },
            _lockSaveButton: function()
            {
                if(!this._dlg)
                {
                    return;
                }

                var saveButton = BX.findChild(
                    this._dlg.popupContainer,
                    { 'class': 'popup-window-button-accept' },
                    true,
                    false
                );

                if(saveButton)
                {
                    BX.removeClass(saveButton, 'popup-window-button-accept');
                    BX.addClass(saveButton, 'popup-window-button-accept-disabled');
                }
            },
            _unlockSaveButton: function()
            {
                if(!this._dlg)
                {
                    return;
                }

                var saveButton = BX.findChild(
                    this._dlg.popupContainer,
                    { 'class': 'popup-window-button-accept-disabled' },
                    true,
                    false
                );

                if(saveButton)
                {
                    BX.removeClass(saveButton, 'popup-window-button-accept-disabled');
                    BX.addClass(saveButton, 'popup-window-button-accept');
                }
            },
            _prepareCode: function(code, salt)
            {
                salt = BX.type.isNotEmptyString(salt) ? salt : '';
                var prefix = this.getSetting('prefix', '');
                return (prefix.length > 0 ? (prefix + '_') : '') + (salt.length > 0 ? (salt + '_') : '') + code;
            },
            _prepareEditDlgTitle: function()
            {
                return (this._ttlWrapper = BX.YNSIRActivityEditor.prepareDialogTitle(this.getMessage('addEmailDlgTitle')));
            },
            _prepareViewDlgTitle: function()
            {
                var subject =  this.getSetting('subject', '');
                var text = this.getMessage('viewDlgTitle');
                text =	text.replace(
                    /%TYPE%/gi,
                    this.getMessage('email')
                );

                text =	text.replace(
                    /%SUBJECT%/gi,
                    subject.length > 0 ? subject : '#' + this.getSetting('ID', '0')
                );

                this._titleMenu = BX.YNSIRActivityMenu.create('',
                    {
                        'enableTasks': this._editor.isTasksEnabled(),
                        'enableCalendarEvents': this._editor.isCalendarEventsEnabled(),
                        'enableEmails': false
                    },
                    {
                        'createTask': this._taskCreationHandler,
                        'createCall': this._callCreationHandler,
                        'createMeeting': this._meetingCreationHandler
                    }
                );

                var wrapper = this._ttlWrapper = BX.YNSIRActivityEditor.prepareDialogTitle(text);
                this._titleMenu.layout(wrapper);
                return wrapper;
            },
            _prepareEditDlgContent: function(dlgId)
            {
                var cfg = this._dlgCfg = {};
                var codeSalt = Math.random().toString().substring(2);

                //wrapper
                cfg['wrapper'] = this._cntWrapper = this._prepareCode('activity_email_wrapper');
                var wrapper = BX.create(
                    'DIV',
                    {
                        attrs: { className: 'bx-crm-dialog-add-email-popup' },
                        props: { id: cfg['wrapper'] }
                    }
                );

                cfg['error'] = this._prepareCode('activity_email_event_error');
                wrapper.appendChild(
                    BX.create(
                        'DIV',
                        {
                            attrs:
                                {
                                    className: 'bx-crm-dialog-activity-error',
                                    style: 'display:none;'
                                },
                            props: { id: cfg['error'] }
                        }
                    )
                );

                //form
                cfg['form'] = this._prepareCode('activity_email_event');
                var form = BX.create('FORM', { props: { name: cfg['form'] } });

                wrapper.appendChild(form);

                //table
                var tab = BX.create(
                    'TABLE',
                    {
                        attrs: { className: 'bx-crm-dialog-activity-table' }
                    }
                );
                tab.cellSpacing = '0';
                tab.cellPadding = '0';
                tab.border = '0';
                form.appendChild(tab);

                // from
                cfg['from'] = this._prepareCode('from');
                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: BX.create('SPAN', { text: this.getMessage('from') + ':' }),
                        content: BX.create(
                            'INPUT',
                            {
                                attrs: { className: 'bx-crm-dialog-input' },
                                props:
                                    {
                                        type: 'text',
                                        id: cfg['from'],
                                        name: cfg['from'],
                                        value: this._prepareDefaultFrom()
                                    },
                                events:
                                    {
                                        click: BX.delegate(this._handleAddresserClick, this),
                                        change: BX.delegate(this._handleAddresseeChange, this)
                                    }
                            }
                        )
                    }
                );

                //to
                cfg['to'] = this._prepareCode('to');
                var toContainer = BX.create(
                    'DIV',
                    {
                        attrs: { className: 'bx-crm-dialog-comm-block' },
                        props: { id: cfg['to'] },
                        events: { click: BX.delegate(this._openCommunicationDialog,  this) }
                    }
                );

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: BX.create('SPAN', { text: this.getMessage('to') + ':' }),
                        content: [ toContainer ]
                    }
                );

                //subject
                cfg['subject'] = this._prepareCode('subject');
                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        title: BX.create('SPAN', { text: this.getMessage('subject') + ':' }),
                        content: BX.create(
                            'INPUT',
                            {
                                attrs: { className: 'bx-crm-dialog-input' },
                                props:
                                    {
                                        type: 'text',
                                        id: cfg['subject'],
                                        name: cfg['subject'],
                                        value: this.getSetting('subject', '')
                                    }
                            }
                        )
                    }
                );


                //template
                if(this.getMessageType() !== 'FWD')
                {
                    var templateData = this.getSetting('mailTemplateData', []);
                    if(templateData.length > 0)
                    {
                        cfg['template'] = this._prepareCode('template');
                        BX.YNSIRActivityEditor.prepareDialogRow(
                            tab,
                            {
                                title: BX.create('SPAN', { text: this.getMessage('template') + ':' }),
                                content: BX.create(
                                    'DIV',
                                    {
                                        attrs: { className: 'bx-crm-dialog-popup-select-block' },
                                        props: { id: cfg['template'] }
                                    }
                                )
                            }
                        );
                    }
                }

                //message
                var lheContainer = BX(this.getSetting('lheContainerID', ''));
                if(lheContainer)
                {
                    lheContainer.style.display = '';
                }

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        skipTitle: true,
                        contentCells:
                            [
                                {
                                    attrs: { colspan: 2 },
                                    children:
                                        [
                                            BX.create(
                                                'DIV',
                                                {
                                                    attrs: { className: 'bx-crm-dialog-text-editor-wrap' },
                                                    style: { border: 'none', background: 'white', height: 'auto', minHeight: '200px'  },
                                                    children:
                                                        [
                                                            lheContainer
                                                        ]
                                                }
                                            )
                                        ]
                                }
                            ]
                    }
                );

                //ownership
                if(this.canChangeOwner())
                {
                    // cfg['change_owner_button'] = this._prepareCode('change_owner_button', codeSalt);
                    // var ownerChangeButton = BX.create(
                    //     'SPAN',
                    //     {
                    //         'attrs': { className: 'bx-crm-dialog-owner-change-text' },
                    //         'props': { id: cfg['change_owner_button'] },
                    //         'text': this.getMessage('change'),
                    //         'events': { click: BX.delegate(this._handleChangeOwnerClick, this) }
                    //     }
                    // );
                    //
                    // cfg['owner_info_wrapper'] = this._prepareCode('owner_info_wrapper', codeSalt);
                    // BX.YNSIRActivityEditor.prepareDialogRow(
                    //     tab,
                    //     {
                    //         title: BX.create('SPAN', { text: this.getMessage('owner') + ':' }),
                    //         content:
                    //             [
                    //                 BX.create(
                    //                     'DIV',
                    //                     {
                    //                         attrs: { className: 'bx-crm-dialog-owner-block' },
                    //                         children:
                    //                             [
                    //                                 BX.create(
                    //                                     'DIV',
                    //                                     {
                    //                                         attrs: { className: 'bx-crm-dialog-owner-info-wrapper' },
                    //                                         props: { id: cfg['owner_info_wrapper'] }
                    //                                     }
                    //                                 ),
                    //                                 BX.create(
                    //                                     'DIV',
                    //                                     {
                    //                                         attrs: { className: 'bx-crm-dialog-owner-button-wrapper' },
                    //                                         children:
                    //                                             [
                    //                                                 ownerChangeButton
                    //                                             ]
                    //                                     }
                    //                                 )
                    //                             ]
                    //                     }
                    //                 )
                    //             ]
                    //     }
                    // );
                }

                //files
                var storageTypeId = parseInt(this.getSetting('storageTypeID', BX.YNSIRActivityStorageType.undefined));
                if(isNaN(storageTypeId) || storageTypeId === BX.YNSIRActivityStorageType.undefined)
                {
                    storageTypeId = this.getDefaultStorageTypeId();
                }
                this._storageTypeId = storageTypeId;
                if(storageTypeId === BX.YNSIRActivityStorageType.webdav)
                {
                    var webDavUploaderNode = this._editor.prepareWebDavUploader(
                        this._uploaderName,
                        this.getMode(),
                        this.getSetting('webdavelements', [])
                    );
                    BX.YNSIRActivityEditor.prepareDialogRow(tab, { skipTitle: true, content: webDavUploaderNode });
                }
                else if(storageTypeId === BX.YNSIRActivityStorageType.disk)
                {
                    var diskUploaderNode = this._editor.prepareDiskUploader(
                        this._uploaderName,
                        this.getMode(),
                        this.getSetting('diskfiles', [])
                    );
                    BX.YNSIRActivityEditor.prepareDialogRow(tab, { skipTitle: true, content: diskUploaderNode });
                }
                else
                {
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            skipTitle: true,
                            content:
                                this._editor.prepareFileUploader(
                                    this.getSetting('uploadControlID', ''),
                                    this.getSetting('uploadID', ''),
                                    this.getSetting('files', [])
                                )
                        }
                    );
                }
                return wrapper;
            },
            _prepareViewDlgContent: function(dlgId)
            {
                var type = this.getType();
                var cfg = this._dlgCfg = {};
                var codeSalt = this._salt;

                //wrapper
                var wrapper = this._cntWrapper = BX.create(
                    'DIV',
                    {
                        attrs: { className: 'bx-crm-dialog-view-email-popup' }
                    }
                );

                //form
                cfg['form'] = this._prepareCode('activity_email', codeSalt);
                var form = BX.create('FORM', { props: { name: cfg['form'] } });
                wrapper.appendChild(form);

                //table
                var tab = BX.create('TABLE');
                tab.cellSpacing = '0';
                tab.cellPadding = '0';
                tab.border = '0';
                tab.className = 'bx-crm-dialog-view-email-table';
                form.appendChild(tab);

                //start
                var start = BX.parseDate(this.getSetting('start', ''));
                if(!start)
                {
                    start = new Date();
                }

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage('datetime') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children: [ BX.YNSIRActivityEditor.trimDateTimeString(BX.date.format(BX.YNSIRActivityEditor.getDateTimeFormat(), start)) ]
                                }
                            ]
                    }
                );

                //direction
                var direction = parseInt(this.getSetting('direction', BX.YNSIRActivityDirection.outgoing));
                cfg['direction'] = this._prepareCode('direction', codeSalt);
                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage('direction') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children: [ BX.YNSIRActivityDirection.getName(BX.YNSIRActivityType.email, direction) ]
                                }
                            ]
                    }
                );

                //to
                cfg['to'] = this._prepareCode('to', codeSalt);
                var contactContainer = BX.create(
                    'DIV',
                    {
                        attrs: { className: 'bx-crm-dialog-comm-block' },
                        props: { id: cfg['to'] }
                    }
                );

                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage(direction === BX.YNSIRActivityDirection.outgoing ? 'addresser' : 'addressee') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children: [ contactContainer ]
                                }
                            ]
                    }
                );

                // subject
                BX.YNSIRActivityEditor.prepareDialogRow(
                    tab,
                    {
                        headerCell:
                            {
                                attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                children: [ this.getMessage('subject') + ':' ]
                            },
                        contentCells:
                            [
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                    children: [ this.getSetting('subject', '') ]
                                }
                            ]
                    }
                );


                var descrHtml = this.getSetting('descriptionHtml', '');
                if(descrHtml === '')
                {
                    descrHtml = this.getSetting('description', '');
                    if(descrHtml !== '')
                    {
                        descrHtml = descrHtml.replace(/<script[^>]*>.*?<\/script>/g, '');
                        descrHtml = descrHtml.replace(/<script[^>]*>/g, '');

                        descrHtml = descrHtml.replace(/\r\n/g, '<br/>');
                        descrHtml = descrHtml.replace(/(\r|\n)/g, '<br/>');
                    }
                }

                if(descrHtml.length > 0)
                {
                    form.appendChild(
                        BX.create(
                            'DIV',
                            {
                                attrs: { className: 'bx-crm-dialog-view-activity-descr' },
                                children:
                                    [
                                        BX.create(
                                            'DIV',
                                            {
                                                attrs: { className: 'bx-crm-dialog-view-activity-descr-title' },
                                                text: this.getMessage('description') + ':'
                                            }
                                        ),
                                        BX.create(
                                            'DIV',
                                            {
                                                attrs: { className: 'bx-crm-dialog-view-activity-descr-text' },
                                                html: descrHtml
                                            }
                                        )
                                    ]
                            }
                        )
                    );
                }

                if(this.displayOwner())
                {
                    cfg['owner_info_wrapper'] = this._prepareCode('owner_info_wrapper', codeSalt);
                    BX.YNSIRActivityEditor.prepareDialogRow(
                        tab,
                        {
                            headerCell:
                                {
                                    attrs: { className: 'bx-crm-dialog-view-cell-left' },
                                    children: [ this.getMessage('owner') + ':' ]
                                },
                            contentCells:
                                [
                                    {
                                        attrs: { className: 'bx-crm-dialog-view-cell-left-right' },
                                        children:
                                            [
                                                BX.create(
                                                    'DIV',
                                                    {
                                                        attrs: { className: 'bx-crm-dialog-owner-block' },
                                                        children:
                                                            [
                                                                BX.create(
                                                                    'DIV',
                                                                    {
                                                                        attrs: { className: 'bx-crm-dialog-owner-info-wrapper' },
                                                                        props: { id: cfg['owner_info_wrapper'] }
                                                                    }
                                                                )
                                                            ]
                                                    }
                                                )
                                            ]
                                    }
                                ]
                        }
                    );
                }

                var storageTypeId = parseInt(this.getSetting('storageTypeID', BX.YNSIRActivityStorageType.undefined));
                if(isNaN(storageTypeId) || storageTypeId === BX.YNSIRActivityStorageType.undefined)
                {
                    storageTypeId = this.getDefaultStorageTypeId();
                }
                this._storageTypeId = storageTypeId;

                var infos = [];
                if(storageTypeId === BX.YNSIRActivityStorageType.webdav)
                {
                    var elemAry = this.getSetting('webdavelements', []);
                    for(var i = 0; i < elemAry.length; i++)
                    {
                        infos.push(
                            {
                                'name': elemAry[i]['NAME'],
                                'url': elemAry[i]['VIEW_URL']
                            }
                        );
                    }
                    if(infos.length > 0)
                    {
                        form.appendChild(this._editor.prepareFileList(infos));
                    }
                }
                else if(storageTypeId === BX.YNSIRActivityStorageType.disk)
                {
                    var diskFileAry = this.getSetting('diskfiles', []);
                    for(var m = 0; m < diskFileAry.length; m++)
                    {
                        infos.push(
                            {
                                'name': diskFileAry[m]['NAME'],
                                'url': diskFileAry[m]['VIEW_URL']
                            }
                        );
                    }
                    if(infos.length > 0)
                    {
                        form.appendChild(this._editor.prepareFileList(infos));
                    }
                }
                else
                {
                    var fileAry = this.getSetting('files', []);
                    for(var j = 0; j < fileAry.length; j++)
                    {
                        infos.push(
                            {
                                'name': fileAry[j]['fileName'],
                                'url': fileAry[j]['fileURL']
                            }
                        );
                    }
                    if(infos.length > 0)
                    {
                        form.appendChild(this._editor.prepareFileList(infos));
                    }
                }

                return wrapper;
            },
            _openCommunicationDialog: function(e)
            {
                var container = this._findElement('to');
                if(!container)
                {
                    return;
                }

                var cfg = this._dlgCfg;
                cfg['contactSearch'] = this._prepareCode('contactSearch', this._salt);
                var quickSearch = BX(cfg['contactSearch']);
                if(!quickSearch)
                {
                    quickSearch = BX.create(
                        'INPUT',
                        {
                            attrs: { className:'bx-crm-dialog-comm-search' },
                            props: { id: cfg['contactSearch'], type: 'text' },
                            events: { keypress: BX.delegate(this._handleQuickSearchKeyPress, this) }
                        }
                    );
                    container.appendChild(quickSearch);
                }
                quickSearch.focus();

                if(!this._communicationSearchController)
                {
                    this._communicationSearchController = BX.YNSIRCommunicationSearchController.create(this._communicationSearch, quickSearch);
                }
                this._communicationSearchController.start();

                this._communicationSearch.openDialog(container, BX.delegate(this._handleCommunicationDialogClose, this));
            },
            _prepareDefaultFrom: function()
            {
                var userFullName = this.getSetting('userFullName', '');
                var userEmail = this.getSetting('userEmail', '');
                var userEmail2 = this.getSetting('userEmail2', '');
                var crmEmail = this.getSetting('crmEmail', '');

                if (BX.type.isNotEmptyString(crmEmail))
                    userEmail = crmEmail;
                else if (BX.type.isNotEmptyString(userEmail2))
                    userEmail = userEmail2;

                return BX.type.isNotEmptyString(userEmail)
                    ? ((BX.type.isNotEmptyString(userFullName) ? userFullName + ' ' : '')  + '<' + userEmail + '>')
                    : '';
            },
            _getQuickSearch: function()
            {
                return this._dlgCfg['contactSearch'] ? BX(this._dlgCfg['contactSearch']) : null;
            },
            _setupFromTemplate: function(template)
            {
                var from = template['from'];
                if(from === '')
                {
                    from = this.getSetting('lastUsedEmail', '');
                    if(from === '')
                    {
                        from = this._prepareDefaultFrom();
                    }
                }

                this.setDialogValue('from', from);

                var msgType = this.getMessageType();
                if(msgType !== 'RE')
                {
                    this.setDialogValue('subject', template['subject']);
                }

                var lhe = BXHtmlEditor.Get(this.getSetting('lheJsName'));
                if (lhe)
                {
                    var descr = template['body'];
                    if (msgType == 'RE')
                    {
                        var originalDescr = this.getSetting('descriptionBBCode', '');
                        if (originalDescr == '')
                        {
                            originalDescr = this.getSetting('description', '');
                            originalDescr = originalDescr.replace(/<br\s*?\/?>/ig, '\n');
                        }

                        if (originalDescr !== '')
                            descr += originalDescr;
                    }

                    lhe.CheckAndReInit(descr);
                }
            },
            _handleCommunicationDialogClose: function()
            {
                if(this._communicationSearchController)
                {
                    this._communicationSearchController.stop();
                    this._communicationSearchController = null;
                }

                var quickSearch = this._getQuickSearch();
                if(quickSearch)
                {
                    this._tryAddCustomCommunication(quickSearch.value);
                    BX.remove(quickSearch);
                }

                delete(this._dlgCfg['contactSearch']);
            },
            _handleCommunicationSelect: function(item)
            {
                this._addCommunication(item.getSettings());

                var quickSearch = this._getQuickSearch();
                if(quickSearch)
                {
                    BX.remove(quickSearch);
                }
                delete(this._dlgCfg['contactSearch']);

                this._communicationSearch.closeDialog();
            },
            _handleTemplateChange: function(filter, item)
            {
                if(item)
                {
                    this.applyTemplate(parseInt(item.getValue()));
                }
            },
            _addCommunication: function(settings)
            {
                if(!settings)
                {
                    return;
                }

                if(!BX.type.isNotEmptyString(settings['type']))
                {
                    settings['type'] = 'EMAIL';
                }

                settings['mode'] = this._dlgMode;
                var comm = BX.YNSIRActivityCommunication.create(settings, this);

                for(var i = 0; i < this._communications.length; i++)
                {
                    if(comm.equals(this._communications[i]))
                    {
                        return;
                    }
                }

                this._communications.push(comm);
                comm.layout(this._findElement('to'), this._getQuickSearch());
                if(this._communicationSearch)
                {
                    this._communicationSearch.adjustDialogPosition();
                }

                this.validate();
            },
            _prepareCommunicationWaiter: function()
            {
                if(!this._communicationWaiter)
                {
                    this._communicationWaiter = BX.create(
                        'DIV',
                        {
                            'attrs': { 'className': 'bx-crm-dialog-comm-wait-wrapper' },
                            'text' : BX.YNSIRActivityEditor.getMessage('dataLoading')
                        }
                    );
                }

                return this._communicationWaiter;
            },
            processViewDataResponse: function(responseData)
            {
                var communicationBlock = this._findElement('to');
                if(this._communicationWaiter)
                {
                    communicationBlock.removeChild(this._communicationWaiter);
                }

                var communications;
                if(typeof(responseData['ACTIVITY_COMMUNICATIONS']) !== 'undefined')
                {
                    var commData =  typeof(responseData['ACTIVITY_COMMUNICATIONS']['DATA']) !== 'undefined'
                        ? responseData['ACTIVITY_COMMUNICATIONS']['DATA'] : {};

                    communications = commData['COMMUNICATIONS'] && BX.type.isArray(commData['COMMUNICATIONS'])
                        ? commData['COMMUNICATIONS'] : [];

                    this.setSetting('communications', communications);
                    this.setSetting('communicationsLoaded', true);
                    this._prepareCommunications();
                }
                else if(typeof(responseData['ACTIVITY_COMMUNICATIONS_PAGE']) !== 'undefined')
                {
                    var commPageData =  typeof(responseData['ACTIVITY_COMMUNICATIONS_PAGE']['DATA']) !== 'undefined'
                        ? responseData['ACTIVITY_COMMUNICATIONS_PAGE']['DATA'] : {};

                    communications = commPageData['COMMUNICATIONS'] && BX.type.isArray(commPageData['COMMUNICATIONS'])
                        ? commPageData['COMMUNICATIONS'] : [];

                    var pageSize = commPageData['PAGE_SIZE'] ? commPageData['PAGE_SIZE'] : 20;
                    var pageNumber = commPageData['PAGE_NUMBER'] ? commPageData['PAGE_NUMBER'] : 1;
                    var pageCount = commPageData['PAGE_COUNT'] ? commPageData['PAGE_COUNT'] : 1;

                    for(var i = 0; i < communications.length; i++)
                    {
                        this._addCommunication(communications[i]);
                    }

                    if(this._paginator)
                    {
                        this._paginator.setupPageData(communications, pageSize, pageNumber, pageCount);
                    }
                    if(pageCount > 1)
                    {
                        this._showAllCommunicationsButton = BX.create(
                            "SPAN",
                            {
                                attrs: { className: "bx-crm-dialog-comm-block-button" },
                                events: { click: BX.delegate(this._handleShowAllCommBtnClick, this) },
                                text: BX.YNSIRActivityEditor.getMessage("showAllCommunication")
                            }
                        );
                        communicationBlock.parentNode.appendChild(this._showAllCommunicationsButton);
                    }
                    else
                    {
                        this.setSetting('communications', communications);
                        this.setSetting('communicationsLoaded', true);
                    }
                }
                this._communicationsReady = true;

                if(this._communicationSearch && !this._communicationSearch.isDataLoaded())
                {
                    this._communicationSearch.processDataResponse(responseData);
                }

                //Skip default communication for FWD and RE mode
                if(this.getId() <= 0
                    && this._dlgMode === BX.YNSIRDialogMode.edit
                    && this._communications.length === 0
                    && this.getMessageType() === '')
                {
                    var defaultComm = this._communicationSearch ? this._communicationSearch.getDefaultCommunication() : null;
                    if(defaultComm)
                    {
                        this._addCommunication(defaultComm.getSettings());
                    }
                }

            },
            _prepareCommunications: function()
            {
                if(this._communicationsReady)
                {
                    return;
                }

                var commData = this.getSetting('communications', []);
                for(var i = 0; i < commData.length; i++)
                {
                    this._addCommunication(commData[i]);
                }
                this._communicationsReady = true;
            },
            _findElement: function(alias)
            {
                return BX.YNSIRActivityEditor.findDialogElement(this._dlgCfg, alias)
            },
            _findElements: function(name)
            {
                return BX.YNSIRActivityEditor.findDialogElements(this._dlgCfg, name);
            },
            _notifySave: function(params)
            {
                for(var i = 0; i < this._onSaveHandlers.length; i++)
                {
                    try
                    {
                        this._onSaveHandlers[i](this, params);
                    }
                    catch(ex)
                    {
                    }
                }
            },
            _notifyDialogClose: function()
            {
                for(var i = 0; i < this._onDlgCloseHandlers.length; i++)
                {
                    try
                    {
                        this._onDlgCloseHandlers[i](this);
                    }
                    catch(ex)
                    {
                    }
                }
            },
            _prepareEditDlgButtons: function()
            {
                return BX.YNSIRActivityEditor.prepareDialogButtons(
                    [
                        {
                            type: 'button',
                            settings:
                                {
                                    text: BX.YNSIRActivityEditor.getMessage('sendDlgButton'),
                                    className: 'popup-window-button-accept',
                                    events:
                                        {
                                            click: BX.delegate(this._handleSaveBtnClick, this)
                                        }
                                }
                        },
                        {
                            type: 'link',
                            settings:
                                {
                                    text: BX.YNSIRActivityEditor.getMessage('cancelShortDlgButton'),
                                    className: 'popup-window-button-link-cancel',
                                    events:
                                        {
                                            click: BX.delegate(this._handleCloseBtnClick, this)
                                        }
                                }
                        }
                    ]
                );
            },
            _prepareViewDlgButtons: function()
            {
                var result = [];

                var direction = parseInt(this.getSetting('direction', BX.YNSIRActivityDirection.outgoing));
                if(direction === BX.YNSIRActivityDirection.incoming)
                {
                    result.push(
                        {
                            type: 'button',
                            settings:
                                {
                                    text: BX.YNSIRActivityEditor.getMessage('replyDlgButton'),
                                    className: 'popup-window-button-accept',
                                    events:
                                        {
                                            click: BX.delegate(this._handleReplyBtnClick, this)
                                        }
                                }
                        }
                    );
                }

                // result.push(
                //     {
                //         type: 'button',
                //         settings:
                //             {
                //                 // text: BX.YNSIRActivityEditor.getMessage('forwardDlgButton'),
                //                 // className: 'popup-window-button-accept',
                //                 // events:
                //                 //     {
                //                 //         click: BX.delegate(this._handleForwardBtnClick, this)
                //                 //     }
                //             }
                //     }
                // );

                result.push(
                    {
                        type: 'link',
                        settings:
                            {
                                text: BX.YNSIRActivityEditor.getMessage('closeDlgButton'),
                                className: 'popup-window-button-link-cancel',
                                events:
                                    {
                                        click: BX.delegate(this._handleCloseBtnClick, this)
                                    }
                            }
                    }
                );

                return BX.YNSIRActivityEditor.prepareDialogButtons(result);
            },
            _handleCallCreation: function(sender)
            {
                var settings = {};
                var ownerType = this.getSetting('ownerType', '');
                var ownerID = parseInt(this.getSetting('ownerID', 0));

                if(typeof BX.YNSIR.Activity.Planner !== 'undefined')
                {
                    (new BX.YNSIR.Activity.Planner()).showEdit({
                        TYPE_ID: BX.YNSIRActivityType.call,
                        OWNER_TYPE: ownerType,
                        OWNER_ID: ownerID,
                        FROM_ACTIVITY_ID: this.getId()
                    });
                    return;
                }

                if(ownerType !== '' && ownerID > 0)
                {
                    settings['ownerType'] = ownerType;
                    settings['ownerID'] = ownerID;
                    settings['ownerTitle'] = this.getSetting('ownerTitle', '');
                    settings['ownerUrl'] = this.getSetting('ownerUrl', '');
                }

                settings['subject'] = this.getSetting('subject', '');
                settings['direction'] = BX.YNSIRActivityDirection.outgoing;

                if(this.getSetting('ownerType', '') === 'DEAL')
                {
                    // Need for custom logic when owner is DEAL (that doesnt have communications)
                    var commData = this.getSetting('communications', []);
                    if(BX.type.isArray(commData))
                    {
                        for(var i = 0; i < commData.length; i++)
                        {
                            var comm = commData[i];

                            var commEntityType =  comm['entityType'];
                            if(!BX.type.isNotEmptyString(commEntityType))
                            {
                                commEntityType = ownerType;
                            }

                            var commEntityId =  parseInt(comm['entityId']);
                            if(isNaN(commEntityId) || commEntityId <= 0)
                            {
                                commEntityId = ownerID;
                            }

                            var defaultComm = BX.YNSIRActivityEditor.getDefaultCommunication(
                                commEntityType,
                                commEntityId,
                                BX.YNSIRCommunicationType.phone,
                                this.getSetting('serviceUrl', '')
                            );

                            if(defaultComm)
                            {
                                settings['communications'] = [defaultComm.getSettings()];
                                break;
                            }
                        }
                    }
                }

                this._editor.addCall(settings);
            },
            _handleMeetingCreation: function(sender)
            {
                var settings = {};
                var ownerType = this.getSetting('ownerType', '');
                var ownerID = parseInt(this.getSetting('ownerID', 0));

                if(typeof BX.YNSIR.Activity.Planner !== 'undefined')
                {
                    (new BX.YNSIR.Activity.Planner()).showEdit({
                        TYPE_ID: BX.YNSIRActivityType.meeting,
                        OWNER_TYPE: ownerType,
                        OWNER_ID: ownerID,
                        FROM_ACTIVITY_ID: this.getId()
                    });
                    return;
                }

                if(ownerType !== '' && ownerID > 0)
                {
                    settings['ownerType'] = ownerType;
                    settings['ownerID'] = ownerID;
                    settings['ownerTitle'] = this.getSetting('ownerTitle', '');
                    settings['ownerUrl'] = this.getSetting('ownerUrl', '');
                }

                settings['subject'] = this.getSetting('subject', '');

                if(this.getSetting('ownerType', '') === 'DEAL')
                {
                    // Need for custom logic when owner is DEAL (that doesnt have communications)
                    var commData = this.getSetting('communications', []);
                    if(BX.type.isArray(commData))
                    {
                        for(var i = 0; i < commData.length; i++)
                        {
                            var comm = commData[i];

                            var commEntityType =  comm['entityType'];
                            if(!BX.type.isNotEmptyString(commEntityType))
                            {
                                commEntityType = ownerType;
                            }

                            var commEntityId =  parseInt(comm['entityId']);
                            if(isNaN(commEntityId) || commEntityId <= 0)
                            {
                                commEntityId = ownerID;
                            }

                            var defaultComm = BX.YNSIRActivityEditor.getDefaultCommunication(
                                commEntityType,
                                commEntityId,
                                BX.YNSIRCommunicationType.undefined,
                                this.getSetting('serviceUrl', '')
                            );

                            if(defaultComm)
                            {
                                settings['communications'] = [defaultComm.getSettings()];
                                break;
                            }
                        }
                    }
                }
                this._editor.addMeeting(settings);
            },
            _handleTaskCreation: function(sender)
            {
                var settings = {};
                var ownerType = this.getSetting('ownerType', '');
                var ownerID = parseInt(this.getSetting('ownerID', 0));
                if(ownerType !== '' && ownerID > 0)
                {
                    settings['ownerType'] = ownerType;
                    settings['ownerID'] = ownerID;
                }

                this._editor.addTask(settings);
            },
            _handleCloseBtnClick: function(e)
            {
                this._buttonId = BX.YNSIRActivityDialogButton.cancel;
                this.closeDialog();
            },
            _handleReplyBtnClick: function(e)
            {
                var editor = this._editor;

                var ownerType = editor.getOwnerType();
                var ownerId = editor.getOwnerId();

                if(ownerType === '' || ownerId <= 0)
                {
                    ownerType = this.getOwnerType();
                    ownerId = this.getOwnerId();
                }

                var settings =
                    {
                        'ownerType': ownerType,
                        'ownerID': ownerId,
                        'subject': this.getSetting('subject', ''),
                        'description': this.getSetting('description', ''),
                        'descriptionBBCode': this.getSetting('descriptionBBCode', ''),
                        'communications': [],
                        'communicationsLoaded': this.getSetting('communicationsLoaded', true),
                        'messageType': 'RE',
                        'originalMessageID': this.getId()
                    };

                var prepareDescription = function()
                {
                    var header = '\n\n-------- Original message --------\n';

                    if (settings['communications'].length > 0)
                    {
                        var email = settings['communications'][0]['value'] ? settings['communications'][0]['value'] : '';
                        var name  = settings['communications'][0]['entityTitle'] ? settings['communications'][0]['entityTitle'] : '';

                        if (email)
                        {
                            header += name
                                ? 'From: "' + name + '" <[URL=mailto:' + email + ']' + email + '[/URL]>\n'
                                : 'From: [URL=mailto:' + email + ']' + email + '[/URL]\n';
                        }
                    }

                    if (settings['subject'])
                        header += 'Subject: ' + settings['subject'] + '\n';
                    settings['subject'] = 'Re: ' + settings['subject'];

                    if (settings['description'])
                        settings['description'] = header + '\n' + settings['description'];
                    if (settings['descriptionBBCode'])
                        settings['descriptionBBCode'] = header + '\n' + settings['descriptionBBCode'];
                };

                var loaded = this.getSetting('communicationsLoaded', true);
                if(loaded)
                {
                    settings['communications'] = this.getSetting('communications', []);
                    settings['communicationsLoaded'] = true;

                    prepareDescription();

                    this.closeDialog();
                    editor.addEmail(settings);
                }
                else
                {
                    var self = this;
                    editor.getActivityCommunications(
                        this.getId(),
                        function(commData)
                        {
                            if(!BX.type.isArray(commData))
                            {
                                commData = [];
                            }

                            settings['communications'] = commData;
                            settings['communicationsLoaded'] = true;

                            prepareDescription();

                            self.closeDialog();
                            editor.addEmail(settings);
                        }
                    );
                }
            },
            _handleForwardBtnClick: function(e)
            {
                // Ignore communications and owner info in FORWARD mode
                this.closeDialog();
                var settings =
                    {
                        'ownerType': '',
                        'ownerID': 0,
                        'subject': 'Fwd: ' + this.getSetting('subject', ''),
                        'description': this.getSetting('description', ''),
                        'descriptionBBCode': this.getSetting('descriptionBBCode', ''),
                        'communications': [],
                        'communicationsLoaded': true,
                        'messageType': 'FWD',
                        'originalMessageID': this.getId()
                    };

                var header = '\n-------- Forwarded message --------\n';
                if (settings['description'])
                    settings['description'] = header + '\n' + settings['description'];
                if (settings['descriptionBBCode'])
                    settings['descriptionBBCode'] = header + '\n' + settings['descriptionBBCode'];

                var storageTypeId = settings['storageTypeID'] = parseInt(this.getSetting('storageTypeID', BX.YNSIRActivityStorageType.undefined));
                if(storageTypeId === BX.YNSIRActivityStorageType.file)
                {
                    settings['files'] = this.getSetting('files', []);
                }
                else if(storageTypeId === BX.YNSIRActivityStorageType.webdav)
                {
                    settings['webdavelements'] = this.getSetting('webdavelements', []);
                }
                else if(storageTypeId === BX.YNSIRActivityStorageType.disk)
                {
                    settings['diskfiles'] = this.getSetting('diskfiles', []);
                }

                this._editor.addEmail(settings);
            },
            _handleSaveBtnClick: function(e)
            {
                if(this._requestIsRunning || !this._communicationsReady || !this._dlg)
                {
                    return;
                }

                var srcData = {};

                srcData['ID'] = this.getId();
                srcData['from'] = this.getDialogValue('from', '');

                if(srcData['from'] !== '')
                {
                    this._editor.setSetting('lastUsedEmail', srcData['from']);
                }

                srcData['communications'] = [];
                var comm;
                for(var i = 0; i < this._communications.length; i++)
                {
                    comm = this._communications[i];
                    srcData['communications'].push(
                        {
                            id: comm.getId(),
                            type: comm.getType(),
                            entityType: comm.getEntityType(),
                            entityId: comm.getEntityId(),
                            value: comm.getValue()
                        }
                    );
                }

                srcData['subject'] = this.getDialogValue('subject', '');

                srcData['message'] = '';
                var lhe = BXHtmlEditor.Get(this.getSetting('lheJsName'));
                if (lhe)
                    srcData['message'] = lhe.GetContent();

                srcData['storageTypeID'] = this._storageTypeId;
                if(this._storageTypeId === BX.YNSIRActivityStorageType.webdav)
                {
                    srcData['webdavelements'] = this._editor.getWebDavUploaderValues(this._uploaderName);
                }
                else if(this._storageTypeId === BX.YNSIRActivityStorageType.disk)
                {
                    srcData['diskfiles'] = this._editor.getDiskUploaderValues(this._uploaderName);
                }
                else
                {
                    srcData['files'] = this._editor.getFileUploaderValues(this.getDialogElements(this.getSetting('uploadInputID', '') + '[]'));
                    var controlId = this.getSetting('uploadControlID', '');
                    if(typeof(BX.CFileInput) !== 'undefined'
                        && typeof(BX.CFileInput.Items[controlId]) !== 'undefined')
                    {
                        srcData['uploadControlCID'] = BX.CFileInput.Items[controlId].CID;
                    }
                }

                var ownerType = '';
                var ownerID = 0;

                var originalOwnerType = this.getSetting('ownerType', '');
                var originalOwnerID = parseInt(this.getSetting('ownerID', 0));

                if(this.canChangeOwner())
                {
                    ownerType = this._owner ? this._owner['typeName'] : '';
                    ownerID = this._owner ? parseInt(this._owner['id']) : 0;
                }

                if((ownerType === '' || ownerID <= 0)
                    && originalOwnerType !== 'DEAL'
                    && this._communications
                    && this._communications.length > 0)
                {
                    for(var j = 0; j < this._communications.length; j++)
                    {
                        comm = this._communications[j];

                        ownerType = comm.getEntityType();
                        ownerID = parseInt(comm.getEntityId());

                        if(ownerType !== '' && ownerID > 0)
                        {
                            break;
                        }
                    }
                }

                if(ownerType === '' || ownerID <= 0)
                {
                    if(originalOwnerType !== '' && originalOwnerID > 0)
                    {
                        ownerType = originalOwnerType;
                        ownerID = originalOwnerID;
                    }
                    else
                    {
                        this._clearError();
                        this._showError(this.getMessage('ownerNotDefined'));
                        return;
                    }
                }

                srcData['ownerType'] = ownerType;
                srcData['ownerID'] = ownerID;

                if(this.getMessageType() === 'FWD')
                {
                    srcData['FORWARDED_ID'] = parseInt(this.getSetting('originalMessageID', 0));
                }
                else if (this.getMessageType() == 'RE')
                {
                    srcData['REPLIED_ID'] = parseInt(this.getSetting('originalMessageID', 0));
                }

                srcData['templateID'] = this._templateId;

                this._buttonId = BX.YNSIRActivityDialogButton.save;
                this._isChanged = true;

                this._requestIsRunning = true;
                this._lockSaveButton();
                var serviceUrl = BX.util.add_url_param(this.getSetting('serviceUrl', ''),
                    {
                        action: 'save_email'
                    }
                );
                //add by nhatth2
                srcData['job_orderID'] = this.getSetting('job_orderID', 0);

                var self = this;
                BX.ajax(
                    {
                        'url': serviceUrl,
                        'method': 'POST',
                        'dataType': 'json',
                        'data':
                            {
                                'ACTION' : 'SAVE_EMAIL',
                                'DATA': srcData
                            },
                        onsuccess: function(data)
                        {
                            if(typeof(data['ERROR']) != 'undefined')
                            {
                                self._clearError();
                                self._showError(data['ERROR']);
                            }
                            else
                            {
                                self._notifySave(data);
                                self.closeDialog();
                            }
                            self._requestIsRunning = false;
                            self._unlockSaveButton();
                        },
                        onfailure: function(data)
                        {
                            self._clearError();
                            self._showError(data);

                            self._requestIsRunning = false;
                            self._unlockSaveButton();
                        }
                    }
                );
            },
            _handleShowAllCommBtnClick: function(e)
            {
                var id = this.getId();
                var mode = id > 0 ? BX.YNSIRDialogMode.view : BX.YNSIRDialogMode.edit;
                var dlgId = "YNSIRCommunicationList"
                    + (mode === BX.YNSIRDialogMode.edit ? "Edit" : "View")
                    + id;

                var dlg = BX.YNSIRActivityCommunicationListDialog.create(
                    dlgId,
                    BX.YNSIRParamBag.create(
                        {
                            "activityId": id,
                            "editor": this._editor,
                            "pageSize": 20,
                            "anchor": this._showAllCommunicationsButton
                        }
                    )
                );
                dlg.open();
            },
            _tryAddCustomCommunication: function(val)
            {
                if(!BX.type.isNotEmptyString(val))
                {
                    return false;
                }

                var emailInfo = BX.YNSIRActivityEditor.parseEmail(val);
                this._addCommunication(
                    {
                        entityId: '0',
                        entityTitle: emailInfo['name'],
                        entityType: 'CONTACT',
                        type: 'EMAIL',
                        value: emailInfo['address']
                    }
                );
                return true;
            },
            _handleQuickSearchKeyPress: function(e)
            {
                if(!e)
                {
                    e = window.event;
                }

                if(e.keyCode !== 13 && e.keyCode !== 27)
                {
                    return;
                }

                var quickSearch = this._getQuickSearch();
                if(!quickSearch)
                {
                    return;
                }

                if(e.keyCode === 27) //escape
                {
                    quickSearch.value = ''; //??
                    quickSearch.focus();
                    return;
                }

                if(this._tryAddCustomCommunication(quickSearch.value))
                {
                    quickSearch.value = '';
                    quickSearch.focus();
                }
            },
            _handleAddresseeChange: function(e)
            {
                this.validate();
            },
            _handleChangeOwnerClick: function(e)
            {
                if(!this.canChangeOwner())
                {
                    return;
                }

                this._openOwnerSelector();
            },
            _openOwnerSelector: function()
            {
                var selectorId = this.getDialogConfigValue('owner_selector_id', '');
                if(selectorId !== '' && obYNSIR && obYNSIR[selectorId])
                {
                    obYNSIR[selectorId].Open();
                }
            },
            _closeOwnerSelector: function()
            {
                var selectorId = this.getDialogConfigValue('owner_selector_id', '');
                if(selectorId !== '' && obYNSIR && obYNSIR[selectorId])
                {
                    obYNSIR[selectorId].Clear();
                    delete obYNSIR[selectorId];
                }
            },
            _handleOwnerSelect: function(settings)
            {
                if(!this.canChangeOwner())
                {
                    return;
                }

                for(var type in settings)
                {
                    if(settings.hasOwnProperty(type))
                    {
                        this._setupOwner(settings[type][0], false);
                        break;
                    }
                }
            },
            _handleAddresserClick: function(e)
            {
                var from = BX(this.getDialogConfigValue('from'));
                if(!from)
                {
                    return;
                }

                var menuId = 'crm-activity-email-addresser';
                if(typeof(BX.PopupMenu.Data[menuId]) !== 'undefined')
                {
                    BX.PopupMenu.Data[menuId].popupWindow.destroy();
                    delete BX.PopupMenu.Data[menuId];
                }

                var menuItems = [];
                var userEmails = this._editor.getUserEmails();
                if(userEmails.length > 0)
                {
                    for(var i = 0;  i < userEmails.length; i++)
                    {
                        var email = userEmails[i];
                        menuItems.push(
                            { text: BX.util.htmlspecialchars(email), className:'', onclick:BX.delegate(this._handleAddresserSelect, this) }
                        );
                    }
                }

                if(menuItems.length === 0)
                {
                    return;
                }

                BX.PopupMenu.show(menuId, from, menuItems, { offsetTop:0, offsetLeft:0 });
            },
            _handleAddresserSelect: function(e)
            {
                if(!e)
                {
                    e = window.event;
                }

                var target = e.target;

                if(!target)
                {
                    return;
                }

                if(target.className !== 'menu-popup-item')
                {
                    target = BX.findParent(target, { className: 'menu-popup-item' });
                }

                var text = BX.findChild(target, { className: 'menu-popup-item-text' }, true, false);
                if(!text)
                {
                    return;
                }

                var from = BX(this.getDialogConfigValue('from'));
                if(from)
                {
                    from.value = BX.util.htmlspecialcharsback(text.innerHTML);
                }

                var menuId = 'crm-activity-email-addresser';
                if(typeof(BX.PopupMenu.Data[menuId]) !== 'undefined')
                {
                    BX.PopupMenu.Data[menuId].popupWindow.destroy();
                    delete BX.PopupMenu.Data[menuId];
                }
            },
            _setupOwner: function(settings, readonly)
            {
                readonly = !!readonly;

                this._owner =
                    {
                        'typeName': settings.type.toUpperCase(),
                        'id': parseInt(settings.id)
                    };

                var wrapper = BX(this.getDialogConfigValue('owner_info_wrapper'));
                if(!wrapper)
                {
                    return;
                }

                BX.cleanNode(wrapper, false);

                var container = BX.create(
                    'SPAN',
                    {
                        attrs:
                            {
                                className: 'bx-crm-dialog-owner-info'
                            },
                        children:
                            [
                                BX.create(
                                    'A',
                                    {
                                        attrs:
                                            {
                                                className: 'bx-crm-dialog-owner-info-link',
                                                href: settings.url,
                                                target: '_blank'
                                            },
                                        text: settings.title
                                    }
                                )
                            ]
                    }
                );

                if(!readonly)
                {
                    container.appendChild(
                        BX.create(
                            'SPAN',
                            {
                                attrs:
                                    {
                                        className: 'finder-box-selected-item-icon'
                                    },
                                events:
                                    {
                                        click: BX.delegate(this._handleDeleteOwnerClick, this)
                                    }
                            }
                        )
                    );
                }

                wrapper.appendChild(container);
            },
            _handleDeleteOwnerClick: function(e)
            {
                if(!this.canChangeOwner())
                {
                    return;
                }

                if(!e)
                {
                    e = window.event;
                }

                var btn = e.target;
                if(btn)
                {
                    BX.remove(BX.findParent(btn, { tagName: 'SPAN', className: 'bx-crm-dialog-owner-info' }));
                }

                this._owner = null;
            },
            validate: function()
            {
                this._clearError();

                var from = this._findElement('from');
                if(from)
                {
                    if(from.value === '')
                    {
                        this._showError(BX.YNSIRActivityEditor.getMessage('addresseeIsEmpty'));
                    }
                    else if(!BX.YNSIRActivityEditor.validateEmail(from.value))
                    {
                        this._showError(BX.YNSIRActivityEditor.getMessage('invalidEmailError').replace('#VALUE#', from.value));
                    }
                }

                if(this._communications.length === 0)
                {
                    this._showError(BX.YNSIRActivityEditor.getMessage('addresserIsEmpty'));
                }
                else
                {
                    for(var j = 0; j < this._communications.length; j++)
                    {
                        var curComm = this._communications[j];
                        if(!curComm.isValid())
                        {
                            this._showError(curComm.getError());
                        }
                    }
                }
            },
            _showError: function(messages)
            {
                var error = this._findElement('error');
                if(!error)
                {
                    return;
                }

                if(!BX.type.isArray(messages))
                {
                    messages = [ messages ];
                }

                for(var i = 0; i < messages.length; i++)
                {
                    error.appendChild(
                        BX.create(
                            'P',
                            {
                                text: messages[i]
                            }
                        )
                    );
                }
                error.style.display = '';

                if(this._communicationSearch)
                {
                    this._communicationSearch.adjustDialogPosition();
                }
            },
            _clearError: function()
            {
                var error = this._findElement('error');
                if(!error)
                {
                    return;
                }

                error.innerHTML = '';
                error.style.display = 'none';

                if(this._communicationSearch)
                {
                    this._communicationSearch.adjustDialogPosition();
                }
            },
            _handleExpand: function()
            {
                if(!this._dlg)
                {
                    return;
                }

                if(!this._expanded)
                {
                    BX.addClass(this._dlg.popupContainer, 'bx-crm-dialog-activity-email-wide');
                    BX.removeClass(this._dlg.popupContainer, 'bx-crm-dialog-activity-email');
                }
                else
                {
                    BX.addClass(this._dlg.popupContainer, 'bx-crm-dialog-activity-email');
                    BX.removeClass(this._dlg.popupContainer, 'bx-crm-dialog-activity-email-wide');
                }

                this._expanded = !this._expanded;

                var size = BX.GetWindowInnerSize(document);
                var scroll = BX.GetWindowScrollPos(document);
                var pos = BX.pos(this._dlg.popupContainer);

                this._dlg.popupContainer.style.left = (scroll.scrollLeft + (size.innerWidth - pos.width) / 2) + 'px';
                this._dlg.popupContainer.style.top = (scroll.scrollTop + (size.innerHeight - pos.height) / 2) + 'px';
            }
        };
    BX.YNSIRActivityEmail.prepredTemplates = {};
    BX.YNSIRActivityEmail.dialogs = {};
    BX.YNSIRActivityEmail.create = function(settings, editor, options)
    {
        var self = new BX.YNSIRActivityEmail();
        self.initialize(settings, editor, options);
        return self;
    };
    BX.YNSIRActivityCommunication = function()
    {
        this._settings = {};
        this._activity = null;
        this._wrapper = null;
        this._isValid = true;
        this._sipCallButton = null;
        this._sipCallButtonClickHandler = BX.delegate(this._onSipCallButtonClick, this);
    };
    BX.YNSIRActivityCommunication.prototype =
        {
            initialize: function(settings, activity)
            {
                if(!activity)
                {
                    throw 'Activity is not defined.';
                }

                this._activity = activity;
                this._settings = settings ? settings : {};

                var val = this.getValue();
                if(this.getType() === 'PHONE')
                {
                    this._isValid = val !== '' && BX.YNSIRActivityEditor.validatePhone(val);
                }
                else if(this.getType() === 'EMAIL')
                {
                    this._isValid = val !== '' && BX.YNSIRActivityEditor.validateEmail(val);
                }
                else if(this.getType() === '' && val === '')
                {
                    this._settings['value'] = this.getSetting('entityTitle', '');
                }
            },
            getSetting: function (name, defaultval)
            {
                return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
            },
            getId: function()
            {
                return parseInt(this.getSetting('id', 0));
            },
            getType: function()
            {
                return this.getSetting('type', '');
            },
            getEntityType: function()
            {
                return this.getSetting('entityType', '');
            },
            getEntityId: function()
            {
                return this.getSetting('entityId', '');
            },
            getValue: function()
            {
                return this.getSetting('value', '');
            },
            getEntityTitle: function()
            {
                return this.getSetting('entityTitle', '');
            },
            getMode: function()
            {
                return this.getSetting('mode', BX.YNSIRDialogMode.edit);
            },
            isSipEnabled: function()
            {
                return this.getSetting('enableSip', false);
            },
            equals: function(comm)
            {
                return this.getType() === comm.getType()
                    && this.getValue() === comm.getValue()
                    && this.getEntityId() === comm.getEntityId()
                    && this.getEntityType() === comm.getEntityType()
            },
            isValid: function()
            {
                return this._isValid;
            },
            getError: function()
            {
                if(this.isValid())
                {
                    return '';
                }

                if(this.getType() === 'PHONE')
                {
                    return BX.YNSIRActivityEditor.getMessage('invalidPhoneError').replace('#VALUE#', this.getValue());
                }
                else if(this.getType() === 'EMAIL')
                {
                    return BX.YNSIRActivityEditor.getMessage('invalidEmailError').replace('#VALUE#', this.getValue());
                }

                return '';
            },
            _createEntityLink: function(text, href)
            {
                return BX.create(
                    'A',
                    {
                        attrs:
                            {
                                className: 'bx-crm-dialog-communication-entity-link',
                                href: href,
                                target: '_blank'
                            },
                        text: text
                    }
                );
            },
            _loadHtml: function(container)
            {
                this._activity.getEditor().getCommunicationHtml(
                    this.getSetting('type'),
                    this.getSetting('value', ''),
                    BX.delegate(
                        function(html) { container.innerHTML = html; },
                        this
                    )
                );
            },
            layout: function(container, insertBefore)
            {
                var mode = this.getMode();

                var wrapper = this._wrapper = BX.create(
                    'SPAN',
                    {
                        attrs: { className: this.isValid() ? 'bx-crm-dialog-contact' : 'bx-crm-dialog-contact bx-crm-dialog-contact-invalid' }
                    }
                );

                var type = this.getSetting('type');
                var ttl = this.getSetting('entityTitle', '');
                var url = this.getSetting('entityUrl', '');
                var val = this.getSetting('value', '');

                if(type === 'PHONE')
                {
                    var callToFormat = parseInt(this.getSetting('callToFormat', BX.YNSIRCalltoFormat.slashless));

                    if(mode === BX.YNSIRDialogMode.view && BX.type.isNotEmptyString(url))
                    {
                        wrapper.appendChild(this._createEntityLink(ttl, url));
                        wrapper.appendChild(document.createTextNode(' '));
                    }
                    else
                    {
                        wrapper.appendChild(
                            BX.create(
                                'SPAN',
                                {
                                    attrs:
                                        {
                                            className: 'bx-crm-dialog-contact-name'
                                        },
                                    text: ttl + ' '
                                }
                            )
                        );
                    }

                    var link = BX.create(
                        'A',
                        {
                            attrs:
                                {
                                    className: callToFormat === BX.YNSIRCalltoFormat.custom ? 'crm-fld-disabled' : 'crm-fld-text',
                                    href: (callToFormat === BX.YNSIRCalltoFormat.standard ? 'callto://' : 'callto:') + val,
                                    title: val
                                },
                            text: val
                        }
                    );

                    if(callToFormat === BX.YNSIRCalltoFormat.bitrix)
                    {
                        BX.bind(link, 'click', this._sipCallButtonClickHandler);
                    }

                    var linkWrapper = BX.create(
                        'SPAN',
                        {
                            attrs: { className: 'bx-crm-dialog-contact-phone' },
                            children: [ link ]
                        }
                    );

                    wrapper.appendChild(linkWrapper);

                    if(this.isSipEnabled() && this.getMode() === BX.YNSIRDialogMode.view)
                    {
                        this._sipCallButton = BX.create(
                            'SPAN',
                            { attrs: { className: 'crm-client-contacts-block-text-tel-icon' } }
                        );
                        BX.bind(this._sipCallButton, 'click', this._sipCallButtonClickHandler);
                        wrapper.appendChild(this._sipCallButton);
                    }

                    if(callToFormat === BX.YNSIRCalltoFormat.custom)
                    {
                        this._loadHtml(linkWrapper);
                    }
                }
                else if(type === 'EMAIL')
                {
                    if(ttl !== '')
                    {
                        if(mode === BX.YNSIRDialogMode.view && BX.type.isNotEmptyString(url))
                        {
                            wrapper.appendChild(this._createEntityLink(ttl, url));
                            wrapper.appendChild(document.createTextNode(' '));
                            wrapper.appendChild(
                                BX.create(
                                    'SPAN',
                                    {
                                        text: ' <' + val + '>'
                                    }
                                )
                            );
                        }
                        else
                        {
                            wrapper.appendChild(
                                BX.create(
                                    'SPAN',
                                    {
                                        text: ttl + ' <' + val + '>'
                                    }
                                )
                            );
                        }
                    }
                    else
                    {
                        wrapper.appendChild(
                            BX.create(
                                'SPAN',
                                {
                                    text: val
                                }
                            )
                        );
                    }
                }
                else if(type === '')
                {
                    if(mode === BX.YNSIRDialogMode.view && BX.type.isNotEmptyString(url))
                    {
                        wrapper.appendChild(this._createEntityLink(val, url));
                    }
                    else
                    {
                        wrapper.appendChild(
                            BX.create(
                                'SPAN',
                                {
                                    text: val
                                }
                            )
                        );
                    }
                }
                else
                {
                    wrapper.appendChild(
                        BX.create(
                            'SPAN',
                            {
                                text: ttl !== '' ? (ttl + ' ' + val) : val
                            }
                        )
                    );
                }

                if(mode === BX.YNSIRDialogMode.edit)
                {
                    wrapper.appendChild(
                        BX.create(
                            'SPAN',
                            {
                                attrs: { className: 'finder-box-selected-item-icon' },
                                events: { click: BX.delegate(this._handleDeletion, this) }
                            }
                        )
                    );
                }

                if(BX.type.isElementNode(insertBefore))
                {
                    container.insertBefore(wrapper, insertBefore);
                }
                else
                {
                    container.appendChild(wrapper);
                }
            },
            cleanupLayout: function()
            {
                if(this._sipCallButton)
                {
                    BX.unbind(this._sipCallButton, 'click', this._sipCallButtonClickHandler);
                    this._sipCallButton = null;
                }

                if(this._wrapper)
                {
                    BX.remove(this._wrapper);
                    this._wrapper = null;
                }
            },
            _handleDeletion: function(e)
            {
                if(this.getMode() !== BX.YNSIRDialogMode.edit)
                {
                    return;
                }

                this._activity.deleteCommunication(this);
                this.cleanupLayout();
                BX.eventCancelBubble(e);
            },
            _onSipCallButtonClick: function(e)
            {
                if(typeof(BX.YNSIRSipManager) !== 'undefined')
                {
                    BX.YNSIRSipManager.startCall(
                        {
                            number: this.getSetting('value', ''),
                            enableInfoLoading: true
                        },
                        {
                            ENTITY_TYPE:  BX.YNSIRSipManager.resolveSipEntityTypeName(this.getSetting('entityType', '')),
                            ENTITY_ID: this.getSetting('entityId', ''),
                            SRC_ACTIVITY_ID: this._activity.getId().toString()
                        },
                        true,
                        this._sipCallButton
                    );
                }

                return BX.PreventDefault(e);
            }
        };
    BX.YNSIRActivityCommunication.create = function(settings, activity)
    {
        var self = new BX.YNSIRActivityCommunication();
        self.initialize(settings, activity);
        return self;
    };

    BX.YNSIRActivityMenu = function()
    {
        this._id = '';
        this._settings = {};
        this._createEmailListeners = [];
        this._createTaskListeners = [];
        this._createCallListeners = [];
        this._createMeetingListeners = [];
        this._documentClickHandler = BX.delegate(this._onDocumentClick, this);
        this._isPopupMenuShown = false;
        this._wrapper = null;
        this._popupMenu = null;
    };

    BX.YNSIRActivityMenu.prototype =
        {
            initialize: function(id, settings, listeners)
            {
                this._id = BX.type.isNotEmptyString(id) ? id : Math.random().toString().substring(2);
                this._settings = settings ? settings : {};
                if(listeners)
                {
                    if(listeners['createTask'])
                    {
                        this.addCreateTaskListener(listeners['createTask']);
                    }
                    if(listeners['createCall'])
                    {
                        this.addCreateCallListener(listeners['createCall']);
                    }
                    if(listeners['createMeeting'])
                    {
                        this.addCreateMeetingListener(listeners['createMeeting']);
                    }
                    if(listeners['createEmail'])
                    {
                        this.addCreateEmailListener(listeners['createEmail']);
                    }
                }
            },
            getSetting: function (name, defaultval)
            {
                return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
            },
            getMessage: function(name, defaultval)
            {
                var msgs = BX.YNSIRActivityMenu.messages;
                return typeof(msgs) !== 'undefined' && msgs[name] ? msgs[name] : defaultval;
            },
            isTasksEnabled: function()
            {
                return this.getSetting('enableTasks', false);
            },
            isCalendarEventsEnabled: function()
            {
                return this.getSetting('enableCalendarEvents', false);
            },
            isEmailsEnabled: function()
            {
                return this.getSetting('enableEmails', false);
            },
            layout: function(container, config)
            {
                var enableTasks = this.isTasksEnabled();
                var enableCalEvents = this.isCalendarEventsEnabled();
                var enableEmails = this.isEmailsEnabled();

                if(!enableEmails && !enableTasks && !enableCalEvents)
                {
                    return;
                }

                if (!config)
                    config = {};

                var wrapper = this._wrapper = BX.create(
                    'UL',
                    {
                        attrs:
                            {
                                className: config.wrapperClassName || 'bx-crm-dialog-view-menu-wrapper'
                            }
                    }
                );
                container.appendChild(wrapper);

                if(enableEmails)
                {
                    wrapper.appendChild(
                        BX.create(
                            'LI',
                            {
                                attrs:
                                    {
                                        className: 'bx-crm-dialog-view-menu-mess'
                                    },
                                events:
                                    {
                                        click: BX.delegate(this._onCreateEmailClick, this)
                                    }
                            }
                        )
                    );
                }

                if(enableTasks || enableCalEvents)
                {
                    var popupMenu = this._popupMenu = BX.create(
                        'UL',
                        {
                            attrs: { id: 'crm_activity_menu' + '_' + this._id },
                            children:
                                [
                                    BX.create(
                                        'LI',
                                        {
                                            attrs:
                                                {
                                                    className: 'bx-crm-dialog-view-menu-arrow'
                                                }
                                        }
                                    )
                                ]
                        }
                    );

                    if(enableTasks)
                    {
                        popupMenu.appendChild(
                            BX.create(
                                'LI',
                                {
                                    children:
                                        [
                                            BX.create(
                                                'A',
                                                {
                                                    attrs:
                                                        {
                                                            className: 'bx-crm-dialog-view-menu-task',
                                                            href: '#'
                                                        },
                                                    events:
                                                        {
                                                            click: BX.delegate(this._onCreateTaskClick, this)
                                                        },
                                                    text: this.getMessage('task', 'Task')
                                                }
                                            )
                                        ]
                                }
                            )
                        );
                    }

                    if(enableCalEvents)
                    {
                        popupMenu.appendChild(
                            BX.create(
                                'LI',
                                {
                                    children:
                                        [
                                            BX.create(
                                                'A',
                                                {
                                                    attrs:
                                                        {
                                                            className: 'bx-crm-dialog-view-menu-call',
                                                            href: '#'
                                                        },
                                                    events:
                                                        {
                                                            click: BX.delegate(this._onCreateCallClick, this)
                                                        },
                                                    text: this.getMessage('call', 'Call')
                                                }
                                            )
                                        ]
                                }
                            )
                        );

                        popupMenu.appendChild(
                            BX.create(
                                'LI',
                                {
                                    children:
                                        [
                                            BX.create(
                                                'A',
                                                {
                                                    attrs:
                                                        {
                                                            className: 'bx-crm-dialog-view-menu-meeting',
                                                            href: '#'
                                                        },
                                                    events:
                                                        {
                                                            click: BX.delegate(this._onCreateMeetingClick, this)
                                                        },
                                                    text: this.getMessage('meeting', 'Interview')
                                                }
                                            )
                                        ]
                                }
                            )
                        );
                    }

                    wrapper.appendChild(
                        BX.create(
                            'LI',
                            {
                                attrs:
                                    {
                                        className: 'bx-crm-dialog-view-menu-more'
                                    },
                                children:
                                    [
                                        BX.create(
                                            'SPAN',
                                            {
                                                events:
                                                    {
                                                        click: BX.delegate(this._onMenuClick, this)
                                                    }
                                            }
                                        ),
                                        popupMenu
                                    ]
                            }
                        )
                    );
                }
            },
            cleanLayout: function()
            {
                if(this._wrapper)
                {
                    BX.cleanNode(this._wrapper, true);
                }
            },
            _onMenuClick: function(e)
            {
                BX.PreventDefault(e);
                this.showPopupMenu(!this._isPopupMenuShown);
            },
            _onDocumentClick: function(e)
            {
                if(this._isPopupMenuShown)
                {
                    this.showPopupMenu(false);
                }
            },
            showPopupMenu: function(show)
            {
                show = !!show;
                this._isPopupMenuShown = show;
                var menu = this._popupMenu;
                if(menu)
                {
                    if(show)
                    {
                        BX.addClass(menu, 'display');
                        BX.bind(document.body, 'click', this._documentClickHandler);
                    }
                    else
                    {
                        BX.removeClass(menu, 'display');
                        BX.unbind(document.body, 'click', this._documentClickHandler);
                    }
                }
            },
            addCreateEmailListener: function(listener)
            {
                this._addListener(listener, this._createEmailListeners);
            },
            removeCreateEmailListener: function(listener)
            {
                this._removeListener(listener, this._createEmailListeners);
            },
            addCreateTaskListener: function(listener)
            {
                this._addListener(listener, this._createTaskListeners);
            },
            removeCreateTaskListener: function(listener)
            {
                this._removeListener(listener, this._createTaskListeners);
            },
            addCreateCallListener: function(listener)
            {
                this._addListener(listener, this._createCallListeners);
            },
            removeCreateCallListener: function(listener)
            {
                this._removeListener(listener, this._createCallListeners);
            },
            addCreateMeetingListener: function(listener)
            {
                this._addListener(listener, this._createMeetingListeners);
            },
            removeCreateMeetingListener: function(listener)
            {
                this._removeListener(listener, this._createMeetingListeners);
            },
            _onCreateEmailClick: function(e)
            {
                //BX.PreventDefault(e);
                this._notify(this._createEmailListeners, [ this ]);
            },
            _onCreateTaskClick: function(e)
            {
                BX.PreventDefault(e);
                this.showPopupMenu(false);
                this._notify(this._createTaskListeners, [ this ]);
            },
            _onCreateCallClick: function(e)
            {
                BX.PreventDefault(e);
                this.showPopupMenu(false);
                this._notify(this._createCallListeners, [ this ]);
            },
            _onCreateMeetingClick: function(e)
            {
                BX.PreventDefault(e);
                this.showPopupMenu(false);
                this._notify(this._createMeetingListeners, [ this ]);
            },
            _addListener: function(listener, listeners)
            {
                if(!BX.type.isFunction(listener))
                {
                    return;
                }

                for(var i = 0; i < listeners.length; i++)
                {
                    if(listeners[i] == listener)
                    {
                        return;
                    }
                }
                listeners.push(listener);
            },
            _removeListener: function(listener, listeners)
            {
                if(!BX.type.isFunction(listener))
                {
                    return;
                }

                for(var i = 0; i < listeners.length; i++)
                {
                    if(listeners[i] == listener)
                    {
                        listeners.splice(i, 1);
                        return;
                    }
                }

            },
            _notify: function(handlers, eventArgs)
            {
                var ary = [];
                for(var i = 0; i < handlers.length; i++)
                {
                    ary.push(handlers[i]);
                }

                for(var j = 0; j < ary.length; j++)
                {
                    try
                    {
                        ary[j].apply(this, eventArgs ? eventArgs : []);
                    }
                    catch(ex)
                    {
                    }
                }
            }
        };

    BX.YNSIRActivityMenu.create = function(id, settings, listeners)
    {
        var self = new BX.YNSIRActivityMenu();
        self.initialize(id, settings, listeners);
        return self;
    };

    if(typeof(BX.YNSIRCalltoFormat) === "undefined")
    {
        BX.YNSIRCalltoFormat =
            {
                undefined: 0,
                standard: 1,
                slashless: 2,
                custom: 3,
                bitrix: 4
            };
    }

    BX.YNSIRActivityCommunicationPaginator = function()
    {
        this._editor = null;
        this._pageSize = 20;
        this._pageNumber = 1;
        this._pageCount = 1;
        this._pages = {};
        this._callback = null;
        this._activityChangeHandler = BX.delegate(this._onActivityChange, this);
    };
    BX.YNSIRActivityCommunicationPaginator.prototype =
        {
            initialize: function(id, settings)
            {
                this._id = id;
                this._settings = settings ? settings : (settings = BX.YNSIRParamBag.create(null));

                this._activityId =  settings.getIntParam("activityId");
                if(this._activityId <= 0)
                {
                    throw  "BX.YNSIRActivityCommunicationPaginator.initialize: activityId not found!";
                }

                this._editor =  settings.getParam("editor");
                if(!this._editor)
                {
                    throw  "BX.YNSIRActivityCommunicationPaginator.initialize: editor not found!";
                }

                this._editor.addActivityChangeHandler(this._activityChangeHandler);

                this._pageSize = settings.getIntParam("pageSize", 20);
                this._pageNumber = settings.getIntParam("pageNumber", 1);
                this._pageCount = this._pageNumber;
            },
            getId: function()
            {
                return this._id;
            },
            getActivityId: function()
            {
                return this._activityId;
            },
            getPageSize: function()
            {
                return this._pageSize;
            },
            getPageNumber: function()
            {
                return this._pageNumber;
            },
            getPageCount: function()
            {
                return this._pageCount;
            },
            getPage: function(pageNumber, callback)
            {
                pageNumber = parseInt(pageNumber);
                if(pageNumber <= 0)
                {
                    return;
                }

                if(typeof(this._pages[pageNumber]) !== "undefined")
                {
                    this._pageNumber = pageNumber;
                    if(BX.type.isFunction(callback))
                    {
                        callback(this, this._pages[pageNumber]);
                    }
                    return;
                }

                BX.showWait();

                if(BX.type.isFunction(callback))
                {
                    this._callback = callback;
                }

                this._editor.getActivityCommunicationsPage(
                    this._activityId,
                    this._pageSize,
                    pageNumber,
                    BX.delegate(this._onPageLoad, this)
                );
            },
            setupPageData: function(commData, pageSize, pageNumber, pageCount)
            {
                this._pageNumber = parseInt(pageNumber);
                this._pageCount = parseInt(pageCount);
                this._pages[this._pageNumber] = commData;

                if(this._callback)
                {
                    this._callback(this, commData);
                }
            },
            _onPageLoad: function(commData, pageSize, pageNumber, pageCount)
            {
                BX.closeWait();
                this.setupPageData(commData, pageSize, pageNumber, pageCount);
            },
            _onActivityChange: function(editor, action, settings)
            {
                if(parseInt(settings["ID"]) === this._activityId)
                {
                    this._editor.removeActivityChangeHandler(this._activityChangeHandler);
                    delete BX.YNSIRActivityCommunicationPaginator.items[this.getId()];
                }
            }
        };
    BX.YNSIRActivityCommunicationPaginator.items = {};
    BX.YNSIRActivityCommunicationPaginator.create = function(id, settings)
    {
        var self = new BX.YNSIRActivityCommunicationPaginator();
        self.initialize(id, settings);
        this.items[self.getId()] = self;
        return self;
    };

    BX.YNSIRActivityCommunicationListDialog = function()
    {
        this._id = "";
        this._settings = null;
        this._editor = null;
        this._activity = null;
        this._popup = null;
        this._wrapper = null;
        this._paginator = null;
        this._commBlock = null;
        this._prevPageButton = null;
        this._communications = [];
    };
    BX.YNSIRActivityCommunicationListDialog.prototype =
        {
            initialize: function(id, settings)
            {
                this._id = id;
                this._settings = settings ? settings : (settings = BX.YNSIRParamBag.create(null));

                this._editor =  settings.getParam("editor");
                if(!this._editor)
                {
                    throw  "BX.YNSIRActivityCommunicationListDialog.initialize: editor not found!";
                }

                var activityId = settings.getIntParam("activityId");
                this._activity = this._editor.getItemById(activityId);
                if(!this._activity)
                {
                    throw  "BX.YNSIRActivityCommunicationListDialog.initialize: activity not found!";
                }

                var pageSize = settings.getIntParam("pageSize", 20);
                var paginatorId = this._editor.getId().toString() + '_' + activityId.toString() + '_' + pageSize.toString();
                if(typeof(BX.YNSIRActivityCommunicationPaginator.items[paginatorId]) !== "undefined")
                {
                    this._paginator = BX.YNSIRActivityCommunicationPaginator.items[paginatorId];
                }
                else
                {
                    this._paginator = BX.YNSIRActivityCommunicationPaginator.create(
                        paginatorId,
                        BX.YNSIRParamBag.create(
                            {
                                editor: this._editor,
                                activityId: activityId,
                                pageSize: pageSize
                            }
                        )
                    );
                }
            },
            getId: function()
            {
                return this._id;
            },
            open: function()
            {
                if(this._popup)
                {
                    this._popup.show();
                    return;
                }

                var settings = this._settings;
                this._popup = BX.PopupWindowManager.create(
                    this._id,
                    settings.getParam("anchor"),
                    {
                        "className":"bx-crm-dialog-wrap crm-activity-comm-list bx-crm-dialog-activity-comm-list",
                        "closeByEsc": true,
                        "autoHide": true,
                        "offsetLeft": 0,
                        "closeIcon": false,
                        "content": this._prepareContent(),
                        "events": { "onPopupClose": BX.delegate(this._onPopupClose, this) },
                        "buttons":
                            [
                                new BX.PopupWindowButtonLink(
                                    {
                                        "text": BX.message["JS_CORE_WINDOW_CLOSE"],
                                        "className": "popup-window-button-link-cancel",
                                        "events":
                                            {
                                                "click": BX.delegate(this._onCancelButtonClick, this)
                                            }
                                    }
                                )
                            ]
                    }
                );

                this._prepagePage(1);
                this._popup.show();
            },
            close: function()
            {
                if(this._popup)
                {
                    this._popup.close();
                }
            },
            _onPopupClose: function()
            {
                if(this._popup)
                {
                    this._popup.destroy();
                    this._popup = null;
                }
            },
            _prepareContent: function()
            {
                var wrapper = this._wrapper = BX.create(
                    "DIV",
                    { "attrs": { "class": "bx-crm-dialog-view-comm-list-popup" } }
                );

                var commBlock = this._commBlock = BX.create(
                    "DIV",
                    { "attrs": { "class": "bx-crm-dialog-comm-block" } }
                );

                wrapper.appendChild(commBlock);

                this._prevPageButton = BX.create(
                    "SPAN",
                    {
                        attrs: { className: "bx-crm-dialog-comm-block-button" },
                        style: { display:"none" },
                        events: { click: BX.delegate(this._onPrevPageBtnClick, this) },
                        text: BX.YNSIRActivityEditor.getMessage("prevPage")
                    }
                );
                wrapper.appendChild(this._prevPageButton);

                this._nextPageButton = BX.create(
                    "SPAN",
                    {
                        attrs: { className: "bx-crm-dialog-comm-block-button" },
                        style: { display:"none" },
                        events: { click: BX.delegate(this._onNextPageBtnClick, this) },
                        text: BX.YNSIRActivityEditor.getMessage("nextPage")
                    }
                );
                wrapper.appendChild(this._nextPageButton);

                return wrapper;
            },
            _onCancelButtonClick: function(e)
            {
                this.close();
            },
            _onPrevPageBtnClick: function(e)
            {
                var pageNumber = this._paginator.getPageNumber();
                if(pageNumber > 1)
                {
                    this._prepagePage(pageNumber - 1);
                }
            },
            _onNextPageBtnClick: function(e)
            {
                var pageNumber = this._paginator.getPageNumber();
                var pageCount = this._paginator.getPageCount();
                if(pageNumber < pageCount)
                {
                    this._prepagePage(pageNumber + 1);
                }
            },
            _prepagePage: function(pageNumber)
            {
                pageNumber = parseInt(pageNumber);
                if(pageNumber <= 0)
                {
                    pageNumber = this._paginator.getPageNumber();
                }
                this._paginator.getPage(pageNumber, BX.delegate(this._onPageLoad, this));
            },
            _onPageLoad: function(paginator, commData)
            {
                for(var i = 0; i < this._communications.length; i++)
                {
                    this._communications[i].cleanupLayout();
                }
                this._communications = [];

                if(!BX.type.isArray(commData))
                {
                    commData = [];
                }

                for(var j = 0; j < commData.length; j++)
                {
                    this._addCommunication(commData[j]);
                }

                var pageNumber = this._paginator.getPageNumber();
                var pageCount = this._paginator.getPageCount();
                this._prevPageButton.style.display = pageNumber > 1 ? "" : "none";
                this._nextPageButton.style.display = pageNumber < pageCount ? "" : "none";
            },
            _addCommunication: function(data)
            {
                if(!data)
                {
                    return;
                }

                data['mode'] = BX.YNSIRDialogMode.view;
                var comm = BX.YNSIRActivityCommunication.create(data, this._activity);

                for(var i = 0; i < this._communications.length; i++)
                {
                    if(comm.equals(this._communications[i]))
                    {
                        return;
                    }
                }

                this._communications.push(comm);
                comm.layout(this._commBlock);
            }
        };
    BX.YNSIRActivityCommunicationListDialog.create = function(id, settings)
    {
        var self = new BX.YNSIRActivityCommunicationListDialog();
        self.initialize(id, settings);
        return self;
    };
}