BX.namespace('BX.YNSIR.Activity');
if(typeof(BX.YNSIRActivityProvider) == 'undefined')
{
    BX.YNSIRActivityProvider = function ()
	{
		this._settings = {};
		this._options = {};
		this._ttlWrapper = null;
		this._dlg = null;
		this._dlgMode = BX.YNSIRDialogMode.view;
		this._dlgCfg = {};
		this._onSaveHandlers = [];
		this._onDlgCloseHandlers = [];
		this._editor = null;
		this._isChanged = false;
		this._buttonId = BX.YNSIRActivityDialogButton.undefined;
		this._owner = null;
		this._salt = '';
		this._callCreationHandler = BX.delegate(this._handleCallCreation, this);
		this._meetingCreationHandler = BX.delegate(this._handleMeetingCreation, this);
		this._emailCreationHandler = BX.delegate(this._handleEmailCreation, this);
		this._taskCreationHandler = BX.delegate(this._handleTaskCreation, this);
		this._titleMenu = null;
		this._contentNode = null;
	};

	BX.YNSIRActivityProvider.prototype =
	{
		initialize: function (settings, editor, options)
		{
			this._settings = settings ? settings : {};
			this._editor = editor;
			this._options = options ? options : {};

			this._isChanged = this.getOption('markChanged', false);

			var ownerType = this.getSetting('ownerType', '');
			var ownerID = this.getSetting('ownerID', '');
			this._salt = Math.random().toString().substring(2);
		},
		getMode: function ()
		{
			return this._dlgMode;
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
		getMessage: function (name)
		{
			return BX.YNSIRActivityProvider.messages && BX.YNSIRActivityProvider.messages[name] ? BX.YNSIRActivityProvider.messages[name] : '';
		},
		getType: function ()
		{
			return this.getSetting('typeID', BX.YNSIRActivityType.provider);
		},
		getId: function ()
		{
			return parseInt(this.getSetting('ID', '0'));
		},
		getMessageType: function ()
		{
			return this.getSetting('messageType', '');
		},
		getOwnerType: function ()
		{
			return this.getSetting('ownerType', '');
		},
		getOwnerId: function ()
		{
			return this.getSetting('ownerID', '');
		},
		openDialog: function (mode)
		{
			var id = this.getId();

			if (id <= 0 || mode !== BX.YNSIRDialogMode.view)
				throw 'not supported.';

			this._dlgMode = mode;

			var dlgId = 'YNSIRActivityProvider'
				+ (mode === BX.YNSIRDialogMode.edit ? 'Edit' : 'View')
				+ id;

			if (BX.YNSIRActivityProvider.dialogs[dlgId])
			{
				return;
			}

			var params = {
				sessid: BX.bitrix_sessid(),
				ajax_action: 'ACTIVITY_VIEW',
				activity_id: id
			};

			var self = this;

			BX.ajax({
				method: 'POST',
				dataType: 'html',
				url: '/bitrix/components/ynsirecruitment/activity.planner/ajax.php?site_id=' + BX.message('SITE_ID'),
				data: params,
				onsuccess: function (HTML)
				{
					var wrapper = BX.create('div');
					wrapper.innerHTML = HTML;
					self._dlg = new BX.PopupWindow(
						dlgId,
						null,
						{
							autoHide: false,
							draggable: true,
							offsetLeft: 0,
							offsetTop: 0,
							bindOptions: {forceBindPosition: false},
							closeByEsc: true,
							closeIcon: true,
							zIndex: -12, //HACK: for tasks popup
							contentNoPaddings: true,
							titleBar: {
								content: self._prepareViewDlgTitle()
							},
							events: {
								onPopupClose: BX.delegate(
									function ()
									{
										BX.YNSIRActivityEditor.hideUploader(self.getSetting('uploadID', ''), self.getSetting('uploadControlID', ''));
										BX.YNSIRActivityEditor.hideLhe(self.getSetting('lheContainerID', ''));

										self._dlg.destroy();
									},
									self
								),
								onPopupDestroy: BX.proxy(
									function ()
									{
										self._dlg = null;
										self._wrapper = null;
										self._ttlWrapper = null;
										delete(BX.YNSIRActivityProvider.dialogs[dlgId]);
									},
									self
								)
							},
							content: wrapper,
							buttons: self._prepareViewDlgButtons()
						}
					);
					
					self._contentNode = wrapper;
					self._prepareDialogContent();

					BX.YNSIRActivityProvider.dialogs[dlgId] = self._dlg;

					self._dlg.show();
				}
			});
		},

		_getNode: function(name)
		{
			return this._contentNode ? this._contentNode.querySelector('[data-role="'+name+'"]') : null;
		},

		_prepareDialogContent: function()
		{
			var me = this;
			var additionalSwitcher = this._getNode('additional-switcher');
			var additionalFields = this._getNode('additional-fields');
			
			if (additionalSwitcher && additionalFields)
			{
				BX.bind(additionalSwitcher, 'click', function()
				{
					BX.toggleClass(additionalFields, 'active')
				});
			}

			var comSliderLeft = this._getNode('com-slider-left');
			if (comSliderLeft)
			{
				BX.bind(comSliderLeft, 'click', function()
				{
					me._changeCommunicationSlide(-1);
				});
			}

			var comSliderRight = this._getNode('com-slider-right');
			if (comSliderRight)
			{
				BX.bind(comSliderRight, 'click', function()
				{
					me._changeCommunicationSlide(1);
				});
			}

			var fieldCompleted = this._getNode('field-completed');
			if (fieldCompleted)
			{
				var enableInstantEdit = this.getOption('enableInstantEdit', true);
				if (enableInstantEdit)
				{
					BX.bind(fieldCompleted, 'click', function()
					{
						fieldCompleted.setAttribute('disabled', 'disabled');

						me._editor.setActivityCompleted(
							me.getId(),
							fieldCompleted.checked,
							function(result)
							{
								me._settings['completed'] = !!result['COMPLETED'];
								fieldCompleted.removeAttribute('disabled');
							}
						);
					});
				}
				else
				{
					fieldCompleted.setAttribute('disabled', 'disabled');
				}
			}
		},

		_changeCommunicationSlide: function(direction)
		{
			var navigator = this._getNode('com-slider-nav');
			var slides = this._getNode('com-slider-slides');
			if (!navigator || !slides)
				return false;

			var currentIndex = parseInt(navigator.getAttribute('data-current'));
			var cnt = parseInt(navigator.getAttribute('data-cnt'));

			if (isNaN(cnt) || cnt < 1)
				return false;
			
			if (isNaN(currentIndex) || currentIndex < 1)
				currentIndex = 1;

			currentIndex += direction < 0 ? -1 : 1;
			
			if (currentIndex > cnt)
				currentIndex = cnt;
			if (currentIndex < 1)
				currentIndex = 1;

			navigator.setAttribute('data-current', currentIndex.toString());
			navigator.innerHTML = currentIndex.toString() + ' / ' + cnt.toString();

			slides.style.marginLeft = ((currentIndex - 1) * -269).toString() + 'px';
		},

		closeDialog: function ()
		{
			if (this._titleMenu)
			{
				this._titleMenu.removeCreateTaskListener(this._taskCreationHandler);
				this._titleMenu.removeCreateCallListener(this._callCreationHandler);
				this._titleMenu.removeCreateMeetingListener(this._meetingCreationHandler);

				this._titleMenu.cleanLayout();
			}

			if (!this._dlg)
			{
				return;
			}

			this._notifyDialogClose();
			this._dlg.close();
		},
		addOnSave: function (handler)
		{
			if (!BX.type.isFunction(handler))
			{
				return;
			}

			for (var i = 0; i < this._onSaveHandlers.length; i++)
			{
				if (this._onSaveHandlers[i] == handler)
				{
					return;
				}
			}

			this._onSaveHandlers.push(handler);

		},
		removeOnSave: function (handler)
		{
			if (!BX.type.isFunction(handler))
			{
				return;
			}

			for (var i = 0; i < this._onSaveHandlers.length; i++)
			{
				if (this._onSaveHandlers[i] == handler)
				{
					this._onSaveHandlers.splice(i, 1);
					return;
				}
			}

		},
		addOnDialogClose: function (handler)
		{
			if (!BX.type.isFunction(handler))
			{
				return;
			}

			for (var i = 0; i < this._onDlgCloseHandlers.length; i++)
			{
				if (this._onDlgCloseHandlers[i] == handler)
				{
					return;
				}
			}

			this._onDlgCloseHandlers.push(handler);

		},
		removeOnDialogClose: function (handler)
		{
			if (!BX.type.isFunction(handler))
			{
				return;
			}

			for (var i = 0; i < this._onDlgCloseHandlers.length; i++)
			{
				if (this._onDlgCloseHandlers[i] == handler)
				{
					this._onDlgCloseHandlers.splice(i, 1);
					return;
				}
			}

		},
		isChanged: function ()
		{
			return this._isChanged;
		},
		getButtonId: function ()
		{
			return this._buttonId;
		},
		_prepareViewDlgTitle: function ()
		{
			var text = this.getSetting('subject', '');

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
		_notifyDialogClose: function ()
		{
			for (var i = 0; i < this._onDlgCloseHandlers.length; i++)
			{
				try
				{
					this._onDlgCloseHandlers[i](this);
				}
				catch (ex)
				{
				}
			}
		},
		_prepareViewDlgButtons: function ()
		{
			var result = [];

			result.push(
				{
					type: 'button',
					settings: {
						text: BX.YNSIRActivityEditor.getMessage('closeDlgButton'),
						className: 'popup-window-button-accept',
						events: {
							click: BX.delegate(this._handleCloseBtnClick, this)
						}
					}
				}
			);

			if(this.getType() ===  BX.YNSIRActivityType.call || this.getType() === BX.YNSIRActivityType.meeting)
			{
				var me = this;
				result.push(
					{
						type: 'link',
						settings:
						{
							text: BX.YNSIRActivityEditor.getMessage('editDlgButton'),
							className: "popup-window-button-link-cancel",
							events:
							{
								click : function()
								{
                                    (new BX.YNSIR.Activity.Planner()).showEdit({ID: me.getId()});
									me.closeDialog();
								}
							}
						}
					}
				);
			}

			return BX.YNSIRActivityEditor.prepareDialogButtons(result);
		},
		_handleCallCreation: function (sender)
		{
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
			}
		},
		_handleMeetingCreation: function (sender)
		{
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
			}
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
		_handleTaskCreation: function (sender)
		{
			var settings = {};
			var ownerType = this.getSetting('ownerType', '');
			var ownerID = parseInt(this.getSetting('ownerID', 0));
			if (ownerType !== '' && ownerID > 0)
			{
				settings['ownerType'] = ownerType;
				settings['ownerID'] = ownerID;
			}

			this._editor.addTask(settings);
		},
		_handleCloseBtnClick: function (e)
		{
			this._buttonId = BX.YNSIRActivityDialogButton.cancel;
			this.closeDialog();
		}
	};
	BX.YNSIRActivityProvider.dialogs = {};
	BX.YNSIRActivityProvider.create = function (settings, editor, options)
	{
		var self = new BX.YNSIRActivityProvider();
		self.initialize(settings, editor, options);
		return self;
	};
}