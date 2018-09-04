BX.YNSIRConfigSettingClass = (function ()
{
	var YNSIRConfigSettingClass = function (parameters)
	{
		this.randomString = parameters.randomString;
		this.tabs = parameters.tabs;
		this.ajaxUrl = parameters.ajaxUrl;
		this.data = parameters.data;
		this.oldData = BX.clone(this.data);
		this.max_sort = {};
		this.requestIsRunning = false;
		this.totalNumberFields = parameters.totalNumberFields;
		this.checkSubmit = false;

		this.defaultColor = '#4C99DA';
		this.defaultFinalSuccessColor = '#96B833';
		this.defaultFinalUnSuccessColor = '#F54819';
		this.defaultLineColor = '#D3EEF9';
		this.textColorLight = '#FFF';
		this.textColorDark = '#545C69';

		this.entityId = parameters.entityId;
		this.hasSemantics = !!parameters.hasSemantics;

		this.jsClass = 'YNSIRConfigSettingClass_'+parameters.randomString;
		this.contentIdPrefix = 'content_';
		this.contentClass = 'crm-status-content';
		this.contentActiveClass = 'crm-status-content active';

		this.fieldNameIdPrefix = 'field-name-';
		this.fieldEditNameIdPrefix = 'field-edit-name-';
		this.fieldHiddenNameIdPrefix = 'field-hidden-name-';
		this.spanStoringNameIdPrefix = 'field-title-inner-';
		this.mainDivStorageFieldIdPrefix = 'field-phase-';
		this.fieldSortHiddenIdPrefix = 'field-sort-';
		this.fieldHiddenNumberIdPrefix = 'field-number-';
		this.extraStorageFieldIdPrefix = 'extra-storage-';
		this.finalSuccessStorageFieldIdPrefix = 'final-success-storage-';
		this.finalStorageFieldIdPrefix = 'final-storage-';
		this.previouslyScaleIdPrefix = 'previously-scale-';
		this.previouslyScaleNumberIdPrefix = 'previously-scale-number-';
		this.previouslyScaleFinalSuccessIdPrefix = 'previously-scale-final-success-';
		this.previouslyScaleNumberFinalSuccessIdPrefix = 'previously-scale-number-final-success-';
		this.previouslyScaleFinalUnSuccessIdPrefix = 'previously-scale-final-un-success-';
		this.previouslyScaleNumberFinalUnSuccessIdPrefix = 'previously-scale-number-final-un-success-';
		this.previouslyScaleFinalCellIdPrefix = 'previously-scale-final-cell-';
		this.previouslyScaleNumberFinalCellIdPrefix = 'previously-scale-number-final-cell-';
		this.funnelSuccessIdPrefix = 'config-funnel-success-';
		this.funnelUnSuccessIdPrefix = 'config-funnel-unsuccess-';

		this.successFields = parameters.successFields;
		this.unSuccessFields = parameters.unSuccessFields;
		this.initialFields = parameters.initialFields;
		this.extraFields = parameters.extraFields;
		this.finalFields = parameters.finalFields;
		this.extraFinalFields = parameters.extraFinalFields;

		this.dataFunnel = [];
		this.colorFunnel = [];
		this.initAmChart = false;

		this.footer = BX('crm-configs-footer');
		this.windowSize = {};
		this.scrollPosition = {};
		this.contentPosition = {};
		this.footerPosition = {};
		this.limit = 0;
		this.footerFixed = true;
		this.blockFixed = !!parameters.blockFixed;

		this.initAmCharts();
		this.showError();
		this.init();
	};

	YNSIRConfigSettingClass.prototype.selectTab = function(tabId)
	{
		var div = BX(this.contentIdPrefix+tabId);
		if(div.className == this.contentActiveClass)
			return;

		for (var i = 0, cnt = this.tabs.length; i < cnt; i++)
		{
			var content = BX(this.contentIdPrefix+this.tabs[i]);
			if(content.className == this.contentActiveClass)
			{
				this.showTab(this.tabs[i], false);
				content.className = this.contentClass;
				break;
			}
		}

		this.showTab(tabId, true);
		div.className = this.contentActiveClass;

		BX('ACTIVE_TAB').value = 'status_tab_' + tabId;
		this.entityId = tabId;
		this.hasSemantics = YNSIRConfigSettingClass.hasSemantics(this.entityId);

		this.processingFooter();

		if(this.hasSemantics)
		{
			AmCharts.handleLoad();
		}
	};

	YNSIRConfigSettingClass.prototype.showTab = function(tabId, on)
	{
		var sel = (on? 'status_tab_active':'');
		BX('status_tab_'+tabId).className = 'status_tab '+sel;
	};

	YNSIRConfigSettingClass.prototype.statusReset = function()
	{
		BX('ACTION').value = 'reset';
		document.forms["typeListForm"].submit();
	};

	YNSIRConfigSettingClass.prototype.YNSIRMoveryName = function(fieldId, name)
	{
		var fieldHiddenNumber = this.searchElement('input', this.fieldHiddenNumberIdPrefix+fieldId),
			fieldName = this.searchElement('span', this.fieldNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement('input', this.fieldHiddenNameIdPrefix+fieldId);

		fieldName.innerHTML = BX.util.htmlspecialchars(fieldHiddenNumber.value+'. '+name);
		fieldHiddenName.value = name;
		this.data[this.entityId][fieldId].NAME = name;

		if(this.initAmChart)
		{
			this.YNSIRCalculateSort();
		}
	};

	YNSIRConfigSettingClass.prototype.editField = function(fieldId)
	{
		var domElement, fieldDiv = this.searchElement('div', this.mainDivStorageFieldIdPrefix+fieldId),
			spanStoring = this.searchElement('span', this.spanStoringNameIdPrefix+fieldId),
			fieldName = this.searchElement('span', this.fieldNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement('input', this.fieldHiddenNameIdPrefix+fieldId);

		if(!fieldHiddenName)
		{
			return;
		}

		domElement = BX.create('span', {
			props: {className: 'transaction-stage-phase-title-input-container'},
			children: [
				BX.create('input', {
					props: {id: this.fieldEditNameIdPrefix+fieldId},
					attrs: {
						type: 'text',
						value: fieldHiddenName.value,
						onkeydown: 'if (event.keyCode==13) {BX["'+this.jsClass+'"].saveFieldValue(\''+fieldId+'\', this);}',
						onblur: 'BX["'+this.jsClass+'"].saveFieldValue(\''+fieldId+'\', this);',
						'data-onblur': '1'
					}
				})
			]
		});

		spanStoring.style.width = "100%";
		fieldDiv.setAttribute('ondblclick', '');
		fieldName.innerHTML = '';
		fieldName.appendChild(domElement);

		var fieldEditName = this.searchElement('input', this.fieldEditNameIdPrefix+fieldId);
		fieldEditName.focus();
		fieldEditName.selectionStart = BX(this.fieldEditNameIdPrefix+fieldId+'').value.length;
	};

	YNSIRConfigSettingClass.prototype.openPopupBeforeDeleteField = function(fieldId)
	{
		if(isNaN(parseInt(fieldId)))
		{
			this.deleteField(fieldId);
			return;
		}

		var message = "";
		if(this.hasSemantics)
		{
			message = BX.message('YNSIR_STATUS_DELETE_FIELD_QUESTION_' + this.entityId);
		}

		if(!BX.type.isNotEmptyString(message))
		{
			message = BX.message('YNSIR_STATUS_DELETE_FIELD_QUESTION');
		}

		var content = BX.create(
			'p',
			{
				props: { className: 'bx-crm-popup-paragraph' },
				html: message
			}
		);


		this.modalWindow({
			modalId: 'bx-crm-popup',
			title: BX.message("YNSIR_STATUS_CONFIRMATION_DELETE_TITLE"),
			overlay: false,
			content: [content],
			events : {
				onPopupClose : function() {
					this.destroy();
				},
				onAfterPopupShow : function(popup) {
					var title = BX.findChild(popup.contentContainer, {className: 'bx-crm-popup-title'}, true);
					if (title)
					{
						title.style.cursor = "move";
						BX.bind(title, "mousedown", BX.proxy(popup._startDrag, popup));
					}
				}
			},
			buttons: [
				BX.create('a', {
					text : BX.message("YNSIR_STATUS_CONFIRMATION_DELETE_CANCEL_BUTTON"),
					props: {
						className: 'webform-small-button webform-small-button-accept'
					},
					events : {
						click : BX.delegate(function (e) {
							BX.PopupWindowManager.getCurrentPopup().close();
						}, this)
					}
				}),
				BX.create('a', {
					text : BX.message("YNSIR_STATUS_CONFIRMATION_DELETE_SAVE_BUTTON"),
					props: {
						className: 'webform-small-button webform-button-cancel'
					},
					events : {
						click : BX.delegate(function (e)
						{
							this.deleteField(fieldId);
							BX.PopupWindowManager.getCurrentPopup().close();
						}, this)
					}
				})
			]
		});
	};

	YNSIRConfigSettingClass.prototype.deleteField = function(fieldId)
	{
		var fieldDiv = this.searchElement('div', this.mainDivStorageFieldIdPrefix+fieldId),
			parentNode = fieldDiv.parentNode;

		var fieldHidden = BX.create('input', {
			attrs: {
				type: 'hidden',
				value: fieldId,
				name: 'LIST['+this.entityId+'][REMOVE]['+fieldId+'][FIELD_ID]'
			}
		});

		BX(this.contentIdPrefix+this.entityId).appendChild(fieldHidden);
		parentNode.removeChild(fieldDiv);
		this.YNSIRCalculateSort();
	};

	YNSIRConfigSettingClass.prototype.modalWindow = function(params)
	{
		params = params || {};
		params.title = params.title || false;
		params.bindElement = params.bindElement || null;
		params.overlay = typeof params.overlay == "undefined" ? true : params.overlay;
		params.autoHide = params.autoHide || false;
		params.closeIcon = typeof params.closeIcon == "undefined"? {right: "20px", top: "10px"} : params.closeIcon;
		params.modalId = params.modalId || 'crm' + (Math.random() * (200000 - 100) + 100);
		params.withoutContentWrap = typeof params.withoutContentWrap == "undefined" ? false : params.withoutContentWrap;
		params.contentClassName = params.contentClassName || '';
		params.contentStyle = params.contentStyle || {};
		params.content = params.content || [];
		params.buttons = params.buttons || false;
		params.events = params.events || {};
		params.withoutWindowManager = !!params.withoutWindowManager || false;

		var contentDialogChildren = [];
		if (params.title) {
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-crm-popup-title'
				},
				text: params.title
			}));
		}
		if (params.withoutContentWrap) {
			contentDialogChildren = contentDialogChildren.concat(params.content);
		}
		else {
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-crm-popup-content ' + params.contentClassName
				},
				style: params.contentStyle,
				children: params.content
			}));
		}
		var buttons = [];
		if (params.buttons) {
			for (var i in params.buttons) {
				if (!params.buttons.hasOwnProperty(i)) {
					continue;
				}
				if (i > 0) {
					buttons.push(BX.create('SPAN', {html: '&nbsp;'}));
				}
				buttons.push(params.buttons[i]);
			}

			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-crm-popup-buttons'
				},
				children: buttons
			}));
		}

		var contentDialog = BX.create('div', {
			props: {
				className: 'bx-crm-popup-container'
			},
			children: contentDialogChildren
		});

		params.events.onPopupShow = BX.delegate(function () {
			if (buttons.length) {
				firstButtonInModalWindow = buttons[0];
				BX.bind(document, 'keydown', BX.proxy(this._keyPress, this));
			}

			if(params.events.onPopupShow)
				BX.delegate(params.events.onPopupShow, BX.proxy_context);
		}, this);
		var closePopup = params.events.onPopupClose;
		params.events.onPopupClose = BX.delegate(function () {

			firstButtonInModalWindow = null;
			try
			{
				BX.unbind(document, 'keydown', BX.proxy(this._keypress, this));
			}
			catch (e) { }

			if(closePopup)
			{
				BX.delegate(closePopup, BX.proxy_context)();
			}

			if(params.withoutWindowManager)
			{
				delete windowsWithoutManager[params.modalId];
			}

			BX.proxy_context.destroy();
		}, this);

		var modalWindow;
		if(params.withoutWindowManager)
		{
			if(!!windowsWithoutManager[params.modalId])
			{
				return windowsWithoutManager[params.modalId]
			}
			modalWindow = new BX.PopupWindow(params.modalId, params.bindElement, {
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				autoHide: params.autoHide,
				overlay: params.overlay,
				events: params.events,
				buttons: [],
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			});
			windowsWithoutManager[params.modalId] = modalWindow;
		}
		else
		{
			modalWindow = BX.PopupWindowManager.create(params.modalId, params.bindElement, {
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				autoHide: params.autoHide,
				overlay: params.overlay,
				events: params.events,
				buttons: [],
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			});

		}

		modalWindow.show();

		return modalWindow;
	};

	YNSIRConfigSettingClass.prototype.saveFieldValue = function(fieldId, input)
	{
		var newFieldName = '', newFieldValue = input.value,
			fieldHiddenNumber = this.searchElement('input', this.fieldHiddenNumberIdPrefix+fieldId),
			fieldName = this.searchElement('span', this.fieldNameIdPrefix+fieldId),
			fieldDiv = this.searchElement('div', this.mainDivStorageFieldIdPrefix+fieldId),
			spanStoring = this.searchElement('span', this.spanStoringNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement('input', this.fieldHiddenNameIdPrefix+fieldId);

		newFieldName += fieldHiddenNumber.value+'. '+newFieldValue;
		input.onblur = '';

		if(newFieldValue == '')
		{
			if(fieldHiddenNumber.value == 1)
			{
				newFieldValue = this.data[this.entityId][fieldId].NAME_INIT;
			}
			else
			{
				var name = BX.message('YNSIR_STATUS_NEW');
				if(this.hasSemantics)
				{
					name = BX.message('YNSIR_STATUS_NEW_'+this.entityId);
				}
				newFieldValue = name;
			}

		}

		fieldName.innerHTML = BX.util.htmlspecialchars(newFieldName);
		fieldDiv.setAttribute('ondblclick', 'BX["'+this.jsClass+'"].editField(\''+fieldId+'\');');
		spanStoring.style.width = "";
		fieldHiddenName.value = newFieldValue;

		this.data[this.entityId][fieldId].NAME = newFieldValue;
		if(this.initAmChart)
		{
			this.YNSIRCalculateSort();
		}
	};

	YNSIRConfigSettingClass.prototype.searchElement = function(tag, id)
	{
		var element = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': tag, 'attribute': {'id': id}}, true);
		if(element[0])
		{
			return element[0];
		}
		return null;
	};

	YNSIRConfigSettingClass.prototype.addField = function(element)
	{
		var parentNode = element.parentNode, fieldId = 1,
			color = this.defaultColor, name = BX.message('YNSIR_STATUS_NEW');

		if(parentNode.id == 'final-storage-'+this.entityId)
		{
			
			color = this.defaultFinalUnSuccessColor;
			this.addCellFinalScale();
		}
		else
		{
			this.addCellMainScale();
		}

		for (var k in this.data[this.entityId])
		{
			fieldId++;
		}

		if(this.hasSemantics)
		{
			name = BX.message('YNSIR_STATUS_NEW_'+this.entityId);
		}
		else
		{
			color = this.defaultLineColor;
		}

		var id = 'n'+fieldId;
		this.data[this.entityId][id] = {
			ID: id,
			SORT: 10,
			NAME: name,
			ENTITY_ID: this.entityId,
		};

		parentNode.insertBefore(this.createStructureHtml(id), element);
		this.YNSIRCalculateSort();
		this.editField(id);
	};

	YNSIRConfigSettingClass.prototype.YNSIRCalculateSort = function()
	{
		var fieldId, parentId;

		var structureFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': 'div', 'attribute': {'data-calculate': '1'}}, true);
		if(!structureFields)
		{
			return;
		}

		for(var i = 0; i < structureFields.length; i++)
		{
			parentId = structureFields[i].parentNode.id;

			if(parentId == this.extraStorageFieldIdPrefix+this.entityId)
			{
				structureFields[i].setAttribute('data-success', '1');
			}
			else if(parentId == this.finalStorageFieldIdPrefix+this.entityId)
			{
				structureFields[i].setAttribute('data-success', '0');
			}

			var number = i+1;
			var sort = number*10;
			fieldId = structureFields[i].getAttribute('id').replace(this.mainDivStorageFieldIdPrefix, '');

			var inputFields = BX.findChildren(structureFields[i], {'tag': 'input', 'attribute': {'data-onblur': '1'}}, true);
			if(inputFields.length)
			{
				this.saveFieldValue(fieldId, inputFields[0]);
			}

			structureFields[i].setAttribute('data-sort', ''+sort+'');

			var fieldName = this.searchElement('span', this.fieldNameIdPrefix+fieldId),
				fieldHiddenName = this.searchElement('input', this.fieldHiddenNameIdPrefix+fieldId),
				fieldHiddenNumber = this.searchElement('input', this.fieldHiddenNumberIdPrefix+fieldId),
				fieldSortHidden = this.searchElement('input', this.fieldSortHiddenIdPrefix+fieldId);

			fieldName.innerHTML = BX.util.htmlspecialchars(number+'. '+fieldHiddenName.value);
			fieldHiddenNumber.value = number;
			fieldSortHidden.value = sort;

			this.data[this.entityId][fieldId].SORT = sort;
		}

		if(this.initAmChart && this.hasSemantics)
		{
			var successFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
				{'tag': 'div', 'attribute': {'data-success': '1'}}, true);
			if(successFields)
			{
				this.successFields[this.entityId] = [];
				for(var k = 0; k < successFields.length; k++)
				{
					fieldId = successFields[k].getAttribute('id').replace(this.mainDivStorageFieldIdPrefix, '');
					this.successFields[this.entityId][k] = this.data[this.entityId][fieldId];
				}
			}

			var unSuccessFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
				{'tag': 'div', 'attribute': {'data-success': '0'}}, true);
			if(successFields)
			{
				this.unSuccessFields[this.entityId] = [];
				for(var j = 0; j < unSuccessFields.length; j++)
				{
					fieldId = unSuccessFields[j].getAttribute('id').replace(this.mainDivStorageFieldIdPrefix, '');
					this.unSuccessFields[this.entityId][j] = this.data[this.entityId][fieldId];
				}
			}

			AmCharts.handleLoad();
		}

		this.changeCellScale();
	};

	YNSIRConfigSettingClass.prototype.changeCellScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		if(!this.successFields[this.entityId] || !this.unSuccessFields[this.entityId])
		{
			return;
		}

		var scale = BX.findChildren(BX(this.previouslyScaleIdPrefix+this.entityId), {'tag': 'td',
				'attribute': {'data-scale-type': 'main'}}, true),
			scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId), {'tag': 'td',
				'attribute': {'data-scale-type': 'main'}}, true),
			scaleFinalSuccess = BX.findChildren(BX(this.previouslyScaleFinalSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true),
			scaleNumberFinalSuccess = BX.findChildren(BX(this.previouslyScaleNumberFinalSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true);

		var mainCount = this.successFields[this.entityId].length - 1,
			scaleCount = scale.length;

		if(mainCount > scaleCount)
		{
			for(var j = scaleCount; j<mainCount; j++)
			{
				this.addCellMainScale();
			}
			this.changeCellScale();
			return;
		}
		else if(mainCount < scaleCount)
		{
			this.deleteCellMainScale(scaleCount-mainCount);
			this.changeCellScale();
			return;
		}

		var number, color;
		for(var i = 0; i < mainCount; i++)
		{
			if(scale[i] && scaleNumber[i])
			{
				if(this.successFields[this.entityId][i].COLOR)
				{
					color = this.successFields[this.entityId][i].COLOR;
				}
				else
				{
					color = this.defaultColor;
				}

				scale[i].style.background = color;
				number = i + 1;
				scaleNumber[i].getElementsByTagName('span')[0].innerHTML = number;
			}
		}

		if(scaleFinalSuccess[0] && scaleNumberFinalSuccess[0])
		{
			if(this.successFields[this.entityId][mainCount].COLOR)
			{
				color = this.successFields[this.entityId][mainCount].COLOR;
			}
			else
			{
				color = this.defaultFinalSuccessColor;
			}
			number++;
			scaleFinalSuccess[0].style.background = color;
			scaleNumberFinalSuccess[0].getElementsByTagName('span')[0].innerHTML = number;
		}

		var scaleFinalUnSuccess = BX.findChildren(BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true),
			scaleNumberFinalUnSuccess = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true);
		var finalCount = this.unSuccessFields[this.entityId].length,
			scaleFinalUnSuccessCount = scaleFinalUnSuccess.length;

		if(finalCount > scaleFinalUnSuccessCount)
		{
			for(var l = scaleFinalUnSuccessCount; l<finalCount; l++)
			{
				this.addCellFinalScale();
			}
			this.changeCellScale();
			return;
		}
		else if(finalCount < scaleFinalUnSuccessCount)
		{
			this.deleteCellFinalScale(scaleFinalUnSuccessCount-finalCount);
			this.changeCellScale();
			return;
		}
		for(var h = 0; h < finalCount; h++)
		{
			if(scaleFinalUnSuccess[h] && scaleNumberFinalUnSuccess[h])
			{
				if(this.unSuccessFields[this.entityId][h].COLOR)
				{
					color = this.unSuccessFields[this.entityId][h].COLOR;
				}
				else
				{
					color = this.defaultFinalUnSuccessColor;
				}

				scaleFinalUnSuccess[h].style.background = color;
				number++;
				scaleNumberFinalUnSuccess[h].getElementsByTagName('span')[0].innerHTML = number;
			}
		}
	};

	YNSIRConfigSettingClass.prototype.addCellMainScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId), {'tag': 'td',
				'attribute': {'data-scale-type': 'main'}}, true),
			scaleHtml = BX.create('td', {
				attrs: {'data-scale-type': 'main'},
				html: '&nbsp;'
			}),
			scaleNumberHtml = BX.create('td', {
				attrs: {'data-scale-type': 'main'},
				children: [
					BX.create('span', {
						props: {className: 'stage-name'},
						html: scaleNumber.length
					})
				]
			});

		BX(this.previouslyScaleIdPrefix+this.entityId).insertBefore(
			scaleHtml, BX(this.previouslyScaleFinalCellIdPrefix+this.entityId));
		BX(this.previouslyScaleNumberIdPrefix+this.entityId).insertBefore(
			scaleNumberHtml, BX(this.previouslyScaleNumberFinalCellIdPrefix+this.entityId));
	};

	YNSIRConfigSettingClass.prototype.addCellFinalScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true),
			scaleHtml = BX.create('td', {
				html: '&nbsp;'
			}),
			scaleNumberHtml = BX.create('td', {
				children: [
					BX.create('span', {
						props: {className: 'stage-name'},
						html: scaleNumber.length
					})
				]
			});

		BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId).appendChild(scaleHtml);
		BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId).appendChild(scaleNumberHtml);
	};

	YNSIRConfigSettingClass.prototype.deleteCellMainScale = function(quantity)
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleCell = BX.findChildren(BX(this.previouslyScaleIdPrefix+this.entityId),
				{'tag': 'td', 'attribute': {'data-scale-type': 'main'}}, true),
			scaleCellNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId),
				{'tag': 'td', 'attribute': {'data-scale-type': 'main'}}, true);

		for(var k = 0; k < quantity; k++)
		{
			BX(this.previouslyScaleIdPrefix+this.entityId).removeChild(scaleCell[k]);
			BX(this.previouslyScaleNumberIdPrefix+this.entityId).removeChild(scaleCellNumber[k]);
		}

	};

	YNSIRConfigSettingClass.prototype.deleteCellFinalScale = function(quantity)
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleCell = BX.findChildren(BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true),
			scaleCellNumber = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true);

		for(var k = 0; k < quantity; k++)
		{
			BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId).removeChild(scaleCell[k]);
			BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId).removeChild(scaleCellNumber[k]);
		}

	};

	YNSIRConfigSettingClass.prototype.createStructureHtml = function(fieldId)
	{
		var domElement, fieldObject = this.data[this.entityId][fieldId];

		var iconClass = '', color = this.textColorDark, blockClass='', img;
		if(this.hasSemantics)
		{
			iconClass = 'light-icon';
			color = this.textColorLight;
			blockClass = 'transaction-stage-phase-dark';
			img = BX.create('div', {
				props: {className: 'transaction-stage-phase-panel-button'},
				attrs: {
					onclick: 'BX["'+this.jsClass+'"].corYNSIRtionColorPicker(event, \''+fieldObject.ID+'\');'
				}
			});
		}

		domElement = BX.create('div', {
			props: {id: this.mainDivStorageFieldIdPrefix+fieldObject.ID, className: 'transaction-stage-phase draghandle'},
			attrs: {
				ondblclick: 'BX["'+this.jsClass+'"].editField(\''+fieldObject.ID+'\');',
				'data-sort': fieldObject.SORT,
				'data-calculate': 1,
				'data-space': fieldObject.ID,
				'style': 'background: '+fieldObject.COLOR+'; color:'+color+';'
			},
			children: [
				BX.create('div', {
					props: {
						id: 'phase-panel',
						className: blockClass+' transaction-stage-phase-panel'
					},
					attrs: {
						"data-class": 'transaction-stage-phase-panel'
					},
					children: [
						img,
						BX.create('div', {
							props: {className: 'transaction-stage-phase-panel-button ' +
							'transaction-stage-phase-panel-button-close'},
							attrs: {
								onclick: 'BX["'+this.jsClass+'"].openPopupBeforeDeleteField(\''+fieldObject.ID+'\');'
							}
						})
					]
				}),
				BX.create('span', {
					props: {
						id: 'transaction-stage-phase-icon',
						className: iconClass+' transaction-stage-phase-icon transaction-stage-phase-icon-move draggable'
					},
					attrs: {
						"data-class": 'transaction-stage-phase-icon transaction-stage-phase-icon-move draggable'
					},
					children: [
						BX.create('span', {
							props: {className: 'transaction-stage-phase-icon-burger'}
						})
					]
				}),
				BX.create('span', {
					props: {
						id: 'phase-panel',
						className: blockClass+' transaction-stage-phase-title'
					},
					attrs: {
						"data-class": 'transaction-stage-phase-title'
					},
					children: [
						BX.create('span', {
							props: {
								id: this.spanStoringNameIdPrefix+fieldObject.ID,
								className: 'transaction-stage-phase-title-inner'
							},
							children: [
								BX.create('span', {
									props: {id: this.fieldNameIdPrefix+fieldObject.ID, className: 'transaction-stage-phase-name'},
									html: fieldObject.ID+'. '+BX.util.htmlspecialchars(fieldObject.NAME)
								}),
								BX.create('span', {
									props: {className: 'transaction-stage-phase-icon-edit'},
									attrs: {
										onclick: 'BX["'+this.jsClass+'"].editField(\''+fieldObject.ID+'\')'
									}
								})
							]
						})
					]
				}),
				BX.create('input', {
					props: {id: this.fieldHiddenNumberIdPrefix+fieldObject.ID},
					attrs: {type: 'hidden', value: fieldObject.ID}
				}),
				BX.create('input', {
					props: {id: this.fieldSortHiddenIdPrefix+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][SORT]',
						value: fieldObject.SORT
					}
				}),
				BX.create('input', {
					props: {id: this.fieldHiddenNameIdPrefix+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][VALUE]',
						value: BX.util.htmlspecialchars(fieldObject.NAME)
					}
				}),
				BX.create('input', {
					props: {id: 'stage-color-'+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][COLOR]',
						value: fieldObject.COLOR
					}
				}),
				BX.create('input', {
					props: {id: 'stage-status-id-'+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][STATUS_ID]',
						'data-status-id': '1',
						value: this.getNewStatusId()
					}
				})
			]
		});

		return domElement;
	};

	YNSIRConfigSettingClass.prototype.getNewStatusId = function()
	{
		var newStatusId = 0;
		var listInputStatusId = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': 'input', 'attribute': {'data-status-id': '1'}}, true);

		if(!listInputStatusId)
			return newStatusId;

		for(var k = 0; k < listInputStatusId.length; k++)
		{
			var statusId = +listInputStatusId[k].value;
			if(!isNaN(statusId))
			{
				if(statusId > newStatusId)
				{
					newStatusId = statusId;
				}
			}
		}
		newStatusId = newStatusId + 1;

		return newStatusId;
	};

	YNSIRConfigSettingClass.prototype.showPlaceToInsert = function(replaceableElement, e)
	{
		if(replaceableElement.className == 'space-to-insert draghandle')
		{
			return;
		}

		var parentElement = replaceableElement.parentNode,
			spaceId = replaceableElement.getAttribute('data-space');

		var spaceToInsert = BX.create('div', {
			props: {
				id: 'space-to-insert-'+spaceId,
				className: 'space-to-insert draghandle'
			},
			attrs: {
				"data-place": '1'
			}
		});

		var coords = getCoords(replaceableElement);
		var displacementHeight = e.pageY - coords.top;
		var middleElement = replaceableElement.offsetHeight/2;
		if(displacementHeight > middleElement)
		{
			if(replaceableElement.className == 'transaction-stage-addphase draghandle')
			{
				return;
			}
			this.deleteSpaceToInsert();
			this.insertAfter(spaceToInsert, replaceableElement);
		}
		else
		{
			this.deleteSpaceToInsert();
			parentElement.insertBefore(spaceToInsert, replaceableElement);
		}
	};

	YNSIRConfigSettingClass.prototype.putDomElement = function(element, parentElement, beforeElement)
	{
		if(!element || !parentElement || !beforeElement)
		{
			return false;
		}

		parentElement.insertBefore(element, beforeElement);

		return true;
	};

	YNSIRConfigSettingClass.prototype.deleteSpaceToInsert = function()
	{
		var spacetoinsert = BX.findChildren(BX('crm-container'),
			{'tag': 'div', 'attribute': {'data-place': '1'}}, true);

		if(spacetoinsert)
		{
			for(var i = 0; i < spacetoinsert.length; i++)
			{
				var parentElement = spacetoinsert[i].parentNode;
				parentElement.removeChild(spacetoinsert[i]);
			}
		}
	};

	YNSIRConfigSettingClass.prototype.insertAfter = function(node, referenceNode)
	{
		if (!node || !referenceNode)
			return;

		var parent = referenceNode.parentNode, nextSibling = referenceNode.nextSibling;

		if (nextSibling && parent)
		{
			parent.insertBefore(node, referenceNode.nextSibling);
		}
		else if(parent)
		{
			parent.appendChild( node );
		}
	};

	YNSIRConfigSettingClass.prototype.checkChanges = function()
	{
		if(this.checkSubmit)
		{
			return;
		}

		var newTotalNumberFields = 0, changes = false;
		for(var k in this.data)
		{
			for(var i in this.data[k])
			{
				newTotalNumberFields++;
				var newSort = parseInt(this.data[k][i].SORT),
					oldSort = parseInt(this.oldData[k][i].SORT),
					newName = this.data[k][i].NAME.toLowerCase(),
					oldName = this.oldData[k][i].NAME.toLowerCase(),
					newColor = this.data[k][i].COLOR.toLowerCase(),
					oldColor = this.oldData[k][i].COLOR.toLowerCase();

				if((newSort !== oldSort) || (newName !== oldName) || (newColor !== oldColor))
				{
					changes = true;
					break;
				}
			}
		}

		if(this.totalNumberFields !== newTotalNumberFields || changes)
		{
			return BX.message('YNSIR_STATUS_CHECK_CHANGES');
		}
	};

	YNSIRConfigSettingClass.prototype.confirmSubmit = function()
	{
		this.checkSubmit = true;
	};

	/* For fix statuses */
	YNSIRConfigSettingClass.prototype.fixStatuses = function()
	{
		if(this.requestIsRunning)
		{
			return;
		}
		this.requestIsRunning = true;
		if(this.ajaxUrl === "")
		{
			throw "Error: Service URL is not defined.";
		}
		BX.ajax({
			url: this.ajaxUrl,
			method: "POST",
			dataType: "json",
			data: {
				"ACTION" : "FIX_STATUSES"
			},
			onsuccess: BX.delegate(function(){
				this.requestIsRunning = false;
				window.location.reload(true);
			}, this),
			onfailure: BX.delegate(function(){
				this.requestIsRunning = false;
			}, this)
		});
	};

	YNSIRConfigSettingClass.prototype.corYNSIRtionColorPicker = function(event, fieldId)
	{
		if(!fieldId)
		{
			return;
		}

		var blockColorPicker = BX('block-color-picker');
		blockColorPicker.style.left = event.pageX+'px';
		blockColorPicker.style.top = event.pageY+'px';
		var img = BX.findChildren(BX('block-color-picker'), {'tag': 'IMG'}, true)[0];
		img.setAttribute('data-img', fieldId);
		img.onclick();
	};

	YNSIRConfigSettingClass.prototype.paintElement = function(color, objColorPicker)
	{
		if(!objColorPicker)
		{
			return;
		}

		var fieldId = objColorPicker.pWnd.getAttribute('data-img');
		var fields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': 'div', 'attribute': {'id': this.mainDivStorageFieldIdPrefix+fieldId}}, true);

		if(fields.length)
		{
			if(!color && fields[0].parentNode.id == this.finalStorageFieldIdPrefix+this.entityId)
			{
				color = this.defaultFinalUnSuccessColor;
			}
			else if(!color && fields[0].parentNode.id == this.finalSuccessStorageFieldIdPrefix+this.entityId)
			{
				color = this.defaultFinalSuccessColor;
			}
			else if(!color)
			{
				color = this.defaultColor;
			}

			if(!this.hasSemantics)
			{
				color = this.defaultLineColor;
			}

			fields[0].style.background = color;

			var span = BX.findChildren(fields[0], {'tag': 'span', 'attribute':
				{'id': 'transaction-stage-phase-icon'}}, true);

			var phasePanel = BX.findChildren(fields[0], {'attribute': {'id': 'phase-panel'}}, true);

			if(span.length && phasePanel.length)
			{
				BX.ajax({
					url: this.ajaxUrl,
					method: "POST",
					dataType: "json",
					data: {
						"ACTION" : "GET_COLOR",
						"COLOR" : color
					},
					onsuccess: BX.delegate(function(result) {
						fields[0].style.color = result.COLOR;
						span[0].className = result.ICON_CLASS+' '+span[0].getAttribute('data-class');
						for(var k in phasePanel)
						{
							phasePanel[k].className = result.BLOCK_CLASS+' '+phasePanel[k].getAttribute('data-class');
						}
					}, this)
				});
			}
		}
		else
		{
			return;
		}

		var hiddenInputColor = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': 'input', 'attribute': {'id': 'stage-color-'+fieldId}}, true);
		if(hiddenInputColor[0])
		{
			hiddenInputColor[0].value = color;
		}
		this.data[this.entityId][fieldId].COLOR = color;

		this.YNSIRCalculateSort();
	};

	YNSIRConfigSettingClass.prototype.initAmCharts = function()
	{
		this.initAmChart = true;
		if (AmCharts.isReady)
		{
			this.renderAmCharts();
		}
		else
		{
			AmCharts.ready(BX.delegate(this.renderAmCharts, this));
		}

		if(this.hasSemantics)
		{
			AmCharts.handleLoad();
		}
	};

	YNSIRConfigSettingClass.prototype.renderAmCharts = function()
	{
		var charts = [];
		for(var k in this.initialFields)
		{
			charts.push(BX(this.funnelSuccessIdPrefix+k));
			charts.push(BX(this.funnelUnSuccessIdPrefix+k));
		}

		if(!charts.length)
		{
			return;
		}

		for(var i = 0; i < charts.length; i++)
		{
			if(!charts[i].id)
				continue;

			this.getDataForAmCharts(charts[i].id);

			var chart = AmCharts.makeChart(charts[i].id, {
				"type": "funnel",
				"theme": "none",
				"titleField": "title",
				"valueField": "value",
				"dataProvider": this.dataFunnel,
				"colors": this.colorFunnel,
				"labelsEnabled": false,
				"marginRight": 35,
				"marginLeft": 35,
				"labelPosition": "center",
				"funnelAlpha": 0.9,
				"startX": 200,
				"neckWidth": "40%",
				"startAlpha": 0,
				"depth3D": 100,
				"angle": 10,
				"outlineAlpha": 1,
				"outlineColor": "#FFFFFF",
				"outlineThickness": 1,
				"neckHeight": "30%",
				"balloonText": "[[title]]",
				"export": {
					"enabled": true
				}
			});
		}
	};

	YNSIRConfigSettingClass.prototype.getDataForAmCharts = function(chartId)
	{
		var fields = [], color = '', success = false;
		if(chartId == this.funnelSuccessIdPrefix+this.entityId)
		{
			fields = this.successFields[this.entityId];
			color = this.defaultColor;
			success = true;
		}
		else if(chartId == this.funnelUnSuccessIdPrefix+this.entityId)
		{
			fields = this.unSuccessFields[this.entityId];
			color = this.defaultFinalUnSuccessColor;
		}

		this.dataFunnel = [];
		this.colorFunnel = [];
		for(var i = 0; i < fields.length; i++)
		{
			if(i == (fields.length -1) && success)
			{
				color = this.defaultFinalSuccessColor;
			}
			this.dataFunnel[i] = {'title': BX.util.htmlspecialchars(fields[i].NAME), 'value': 1};
			if(fields[i].COLOR)
			{
				this.colorFunnel[i] = fields[i].COLOR;
			}
			else
			{
				this.colorFunnel[i] = color;
			}
		}
	};

	YNSIRConfigSettingClass.prototype.showError = function()
	{
		var error = this.getParameterByName('ERROR');
		if(error)
		{
			var content = BX.create('p', {
				props: {className: 'bx-crm-popup-paragraph'},
				html: BX.util.htmlspecialchars(error)
			});
			this.modalWindow({
				modalId: 'bx-crm-popup',
				title: BX.message("YNSIR_STATUS_REMOVE_ERROR"),
				overlay: false,
				content: [content],
				events : {
					onPopupClose : function() {
						this.destroy();
					},
					onAfterPopupShow : function(popup) {
						var title = BX.findChild(popup.contentContainer, {className: 'bx-crm-popup-title'}, true);
						if (title)
						{
							title.style.cursor = "move";
							BX.bind(title, "mousedown", BX.proxy(popup._startDrag, popup));
						}
					}
				},
				buttons: [
					BX.create('a', {
						text : BX.message("YNSIR_STATUS_CLOSE_POPUP_REMOVE_ERROR"),
						props: {
							className: 'webform-small-button webform-small-button-accept'
						},
						events : {
							click : BX.delegate(function (e) {
								BX.PopupWindowManager.getCurrentPopup().close();
							}, this)
						}
					})
				]
			});
		}
	};

	YNSIRConfigSettingClass.prototype.getParameterByName = function(name)
	{
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	};

	YNSIRConfigSettingClass.prototype.init = function()
	{
		var footer = BX('crm-configs-footer');
		if (!footer)
		{
			return;
		}

		BX.addCustomEvent(footer, 'onFooterChangeState', BX.delegate(function(state)
		{
			if (state)
			{
				BX.removeClass(footer, 'crm-configs-footer');
				BX.addClass(footer, 'webform-buttons-fixed');
			}
			else
			{
				BX.addClass(footer, 'crm-configs-footer');
				BX.removeClass(footer, 'webform-buttons-fixed');
			}
		}, this));

		BX.bind(window, 'scroll', BX.proxy(this.processingFooter, this));

		this.processingFooter();

		if(!this.blockFixed)
		{
			BX.onCustomEvent(this.footer, 'onFooterChangeState', [false]);
		}
	};

	YNSIRConfigSettingClass.prototype.processingFooter = function()
	{
		if (!this.footer || !this.blockFixed)
		{
			return;
		}

		this.windowSize = BX.GetWindowInnerSize();
		this.scrollPosition = BX.GetWindowScrollPos();
		this.contentPosition = BX.pos(BX(this.contentIdPrefix+this.entityId));
		this.footerPosition = BX.pos(this.footer);

		this.limit = this.contentPosition.top;
		var scrollBottom = this.scrollPosition.scrollTop + this.windowSize.innerHeight;
		var pos = this.contentPosition.bottom + this.footerPosition.height;

		if(this.limit > 0 && scrollBottom < this.limit)
		{
			this.footerFixed = false;
		}
		else if(!this.footerFixed && scrollBottom < pos)
		{
			this.footerFixed = true;
		}
		else if(this.footerFixed && scrollBottom >= pos)
		{
			this.footerFixed = false;
		}

		BX.onCustomEvent(this.footer, 'onFooterChangeState', [this.footerFixed]);

		var padding = parseInt(BX.style(this.footer, 'paddingLeft'));

		this.footer.style.left = this.contentPosition.left + 'px';
		this.footer.style.width = (this.contentPosition.width - padding*2) + 'px'

	};

	YNSIRConfigSettingClass.prototype.fixFooter = function(fixButton)
	{
		this.blockFixed = !this.blockFixed;
		if(this.blockFixed)
		{
			BX.userOptions.save('crm', 'crm_config_status', 'fix_footer', 'on');

			BX.addClass(fixButton, 'crm-fixedbtn-pin');
			fixButton.setAttribute('title', BX.message('YNSIR_STATUS_FOOTER_PIN_OFF'));

			this.processingFooter();
		}
		else
		{
			BX.userOptions.save('crm', 'crm_config_status', 'fix_footer', 'off');

			BX.removeClass(fixButton, 'crm-fixedbtn-pin');
			fixButton.setAttribute('title', BX.message('YNSIR_STATUS_FOOTER_PIN_ON'));

			BX.onCustomEvent(this.footer, 'onFooterChangeState', [false]);
		}
	};

	YNSIRConfigSettingClass.semanticEntityTypes = [];

	YNSIRConfigSettingClass.hasSemantics = function(entityTypeId)
	{
		var types = this.semanticEntityTypes;
		if(!BX.type.isArray(types))
		{
			return false;
		}

		for(var i = 0, l = types.length; i < l; i++)
		{
			if(types[i] === entityTypeId)
			{
				return true;
			}
		}
		return false;
	};

	return YNSIRConfigSettingClass;
})();

BX.YNSIRConfigClass = (function ()
{
	var YNSIRConfigClass = function (parameters)
	{
		this.randomString = parameters.randomString;
		this.tabs = parameters.tabs;
	};

	YNSIRConfigClass.prototype.selectTab = function(tabId)
	{
		var div = BX('tab_content_'+tabId);
		if(!div) return;
		if(div.className == 'view-report-wrapper-inner active')
			return;

		for (var i = 0, cnt = this.tabs.length; i < cnt; i++)
		{
			var content = BX('tab_content_'+this.tabs[i]);
			if(content && content.className == 'view-report-wrapper-inner active')
			{
				this.showTab(this.tabs[i], false);
				content.className = 'view-report-wrapper-inner';
				break;
			}
		}

		this.showTab(tabId, true);
		div.className = 'view-report-wrapper-inner active';
	};

	YNSIRConfigClass.prototype.showTab = function(tabId, on)
	{
		var sel = (on? 'sidebar-tab-active':'');
		BX('tab_'+tabId).className = 'sidebar-tab '+sel;
	};

	return YNSIRConfigClass;
})();

if(typeof(BX.YNSIREntityAccessManager) == "undefined")
{
	BX.YNSIREntityAccessManager = function()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._processDialogs = {};
	};

	BX.YNSIREntityAccessManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_entity_acc_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.YNSIREntityAccessManager. Could not find service url.";
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			return BX.YNSIREntityAccessManager.messages && BX.YNSIREntityAccessManager.messages.hasOwnProperty(name) ? BX.YNSIREntityAccessManager.messages[name] : "";
		},
		rebuildCompanyAttrs: function()
		{
			this._rebuildEntityAttrs("COMPANY");
		},
		rebuildContactAttrs: function()
		{
			this._rebuildEntityAttrs("CONTACT");
		},
		rebuildDealAttrs: function()
		{
			this._rebuildEntityAttrs("DEAL");
		},
		rebuildLeadAttrs: function()
		{
			this._rebuildEntityAttrs("LEAD");
		},
		rebuildQuoteAttrs: function()
		{
			this._rebuildEntityAttrs("QUOTE");
		},
		rebuildInvoiceAttrs: function()
		{
			this._rebuildEntityAttrs("INVOICE");
		},
		_rebuildEntityAttrs: function(entityTypeName)
		{
			var entityTypeNameU = entityTypeName.toUpperCase();
			var entityTypeNameC = entityTypeName.toLowerCase().replace(/(?:^)\S/, function(c){ return c.toUpperCase(); });
			var key = "rebuild" + entityTypeNameC + "AccessAttrs";

			var processDlg = null;
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				processDlg = this._processDialogs[key];
			}
			else
			{
				processDlg = BX.YNSIRLongRunningProcessDialog.create(
					key,
					{
						serviceUrl: this._serviceUrl,
						action:"REBUILD_ENTITY_ATTRS",
						params:{ "ENTITY_TYPE_NAME": entityTypeNameU },
						title: this.getMessage(key + "DlgTitle"),
						summary: this.getMessage(key + "DlgSummary")
					}
				);

				this._processDialogs[key] = processDlg;
				BX.addCustomEvent(processDlg, 'ON_STATE_CHANGE', BX.delegate(this._onProcessStateChange, this));
			}
			processDlg.show();
		},
		_onProcessStateChange: function(sender)
		{
			var key = sender.getId();
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				var processDlg = this._processDialogs[key];
				if(processDlg.getState() === BX.YNSIRLongRunningProcessState.completed)
				{
					var p = processDlg.getParams();
					var typeName = BX.type.isNotEmptyString(p["ENTITY_TYPE_NAME"]) ? p["ENTITY_TYPE_NAME"] : "";
					if(typeName === "COMPANY")
					{
						BX.onCustomEvent(this, 'ON_COMPANY_ATTRS_REBUILD_COMPLETE', [this]);
					}
					else if(typeName === "CONTACT")
					{
						BX.onCustomEvent(this, 'ON_CONTACT_ATTRS_REBUILD_COMPLETE', [this]);
					}
					else if(typeName === "DEAL")
					{
						BX.onCustomEvent(this, 'ON_DEAL_ATTRS_REBUILD_COMPLETE', [this]);
					}
					else if(typeName === "LEAD")
					{
						BX.onCustomEvent(this, 'ON_LEAD_ATTRS_REBUILD_COMPLETE', [this]);
					}
					else if(typeName === "QUOTE")
					{
						BX.onCustomEvent(this, 'ON_QUOTE_ATTRS_REBUILD_COMPLETE', [this]);
					}
					else if(typeName === "INVOICE")
					{
						BX.onCustomEvent(this, 'ON_INVOICE_ATTRS_REBUILD_COMPLETE', [this]);
					}
				}
			}
		}
	};

	if(typeof(BX.YNSIREntityAccessManager.messages) == "undefined")
	{
		BX.YNSIREntityAccessManager.messages = {};
	}

	BX.YNSIREntityAccessManager.items = {};
	BX.YNSIREntityAccessManager.create = function(id, settings)
	{
		var self = new BX.YNSIREntityAccessManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}