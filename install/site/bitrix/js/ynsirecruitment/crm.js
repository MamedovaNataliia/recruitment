if (!obYNSIR)
{
	var obYNSIR = {};
}

CRM = function(crmID, div, el, name, element, prefix, multiple, entityType, localize, disableMarkup, options)
{
	this.crmID = crmID;
	this.div = div;
	this.el = el;
	this.name = name;
	this.PopupEntityType = entityType;
	this.PopupTabs = {};
	this.PopupElement =  element;
	this.PopupPrefix = prefix;
	this.PopupMultiple = multiple;
	this.PopupBlock = {};
	this.PopupSearch = {};
	this.PopupSearchInput = null;

	this.PopupTabsIndex = 0;
	this.PopupTabsIndexId = '';
	this.PopupLocalize = localize;

	this.popup = null;
	this.onSaveListeners = [];
	this.disableMarkup = !!disableMarkup; //disable call 'PopupCreateValue' on save
	this.onBeforeSearchListeners = [];

	this.options = {
		requireRequisiteData: false,
		searchOptions: {}
	};
	if (options && typeof(options) === "object")
	{
		if (!!options["requireRequisiteData"])
			this.options.requireRequisiteData = true;

		if (BX.type.isPlainObject(options["searchOptions"]))
			this.options.searchOptions = options["searchOptions"];
	}
};

CRM.prototype.Init = function()
{
	this.popupShowMarkup();

	this.PopupTabs = BX.findChildren(BX("crm-"+this.crmID+"_"+this.name+"-tabs"), {className : "crm-block-cont-tabs"});
	if(this.PopupTabs.length > 0)
	{
		this.PopupTabsIndex = 0;
		this.PopupTabsIndexId = this.PopupTabs[0].id;
	}

	this.PopupItem = {};
	this.PopupItemSelected = {};
	for (var i in this.PopupElement)
		this.PopupAddItem(this.PopupElement[i]);

	this.PopupBlock = BX.findChildren(BX("crm-"+this.crmID+"_"+this.name+"-blocks"), {className : "crm-block-cont-block"});
	this.PopupSearch = BX.findChildren(BX("crm-"+this.crmID+"_"+this.name+"-block-cont-search"), {className : "crm-block-cont-search-tab"});
	this.PopupSearchInput = BX("crm-"+this.crmID+"_"+this.name+"-search-input");

	for(var i = 0; i<this.PopupTabs.length; i++)
		eval('BX.bind(this.PopupTabs[i], "click", function(event){ CRM.PopupShowBlock("' + this.crmID + '", this); BX.PreventDefault(event); })');

	for(var i = 0; i<this.PopupSearch.length; i++)
		eval('BX.bind(this.PopupSearch[i], "click", function(event){ CRM.PopupShowSearchBlock("' + this.crmID + '", this); BX.PreventDefault(event); })');

	eval('BX.bind(this.PopupSearchInput, "keyup", function(event){ CRM.SearchChange("' + this.crmID + '")})');

	this.PopupSave();

	BX.onCustomEvent(window, 'onYNSIRSelectorInit', [this.crmID, this.name, this]);
};

CRM.prototype.Clear = function()
{
	if (this.popup)
	{
		this.popup.close();
		this.popup.destroy();
	}

	var inputBox = BX("crm-"+this.crmID+"_"+this.name+"-input-box");
	if (inputBox)
	{
		this.div.removeChild(inputBox);
		BX.remove(inputBox);
	}

	var textBox = BX("crm-"+this.crmID+"_"+this.name+"-text-box");
	if (textBox)
	{
		BX.remove(textBox);
	}

	var htmlBox = BX("crm-"+this.crmID+"_"+this.name+"-html-box");
	if (htmlBox)
	{
		BX.remove(htmlBox);
	}
};

CRM.Set = function(el, name, subIdName, element, prefix, multiple, entityType, localize, disableMarkup, options)
{
	var crmID = false;
	if (el && BX.isNodeInDom(el))
	{
		crmID = el.id + subIdName;
		if (obYNSIR[crmID])
		{
			obYNSIR[crmID].Clear();
			delete obYNSIR[crmID];
		}

		obYNSIR[crmID] = new CRM(crmID, CRM.GetWrapperDivPa(el), el, name, element, prefix, multiple, entityType, localize, disableMarkup, options);
		obYNSIR[crmID].Init();
	}
	return crmID;
};

CRM.GetElementForm = function (pn)
{
	return BX.findParent(pn, { "tagName":"FORM" });
};

CRM.GetWrapperDivPr = function (pn, name)
{
	return BX.findPreviousSibling(pn, { "tagName": "DIV", "property": { "name": "crm-"+ name +"-box" } });
};

CRM.GetWrapperDivN = function (pn, name)
{
	return BX.findNextSibling(pn, { "tagName": "DIV", "property": { "name": "crm-"+ name +"-box" } });
};

CRM.GetWrapperDivPa = function (pn, name)
{
	while(pn.nodeName != 'DIV' && pn.name != 'crm-'+name+'-box')
		pn = pn.parentNode;

	return pn.parentNode;
};

CRM.prototype.Open = function (params)
{
	if(!BX.type.isPlainObject(params))
	{
		params = {};
	}

	var titleBar = (BX.type.isPlainObject(params["titleBar"]) || BX.type.isNotEmptyString(params["titleBar"]))
		? params["titleBar"] : null;
	var closeIcon = BX.type.isPlainObject(params["closeIcon"])
		? params["closeIcon"] : null;
	var closeByEsc = BX.type.isBoolean(params["closeByEsc"])
		? params["closeByEsc"] : false;
	var autoHide = BX.type.isBoolean(params["autoHide"])
		? params["autoHide"] : !this.PopupMultiple;
	var anchor = BX.type.isElementNode(params["anchor"])
		? params["anchor"] : this.el;
	var gainFocus = BX.type.isBoolean(params["gainFocus"]) ? params["gainFocus"] : true;

	if (BX.PopupWindowManager._currentPopup !== null
		&& BX.PopupWindowManager._currentPopup.uniquePopupId == "CRM-"+this.crmID+"-popup")
	{
		BX.PopupWindowManager._currentPopup.close();
	}
	else
	{
		var buttonsAr = [];
		if (this.PopupMultiple)
		{
			buttonsAr = [
				new BX.PopupWindowButton({
					text : this.PopupLocalize['ok'],
					className : "popup-window-button-accept",
					events : {
						click: BX.delegate(this._handleAcceptBtnClick, this)
					}
				}),

				new BX.PopupWindowButtonLink({
					text : this.PopupLocalize['cancel'],
					className : "popup-window-button-link-cancel",
					events : {
						click: function() { this.popupWindow.close(); }
					}
				})
			];
		}
		else
		{
			buttonsAr = [
				new BX.PopupWindowButton({
					text : this.PopupLocalize['close'],
					className : "popup-window-button-accept",
					events : {
						click: function() { this.popupWindow.close(); }
					}
				})
			];
		}
		this.popup = BX.PopupWindowManager.create(
			"CRM-"+this.crmID+"-popup",
			anchor,
			{
				content : BX("crm-"+this.crmID+"_"+this.name+"-block-content-wrap"),
				titleBar: titleBar,
				closeIcon: closeIcon,
				closeByEsc: closeByEsc,
				offsetTop : 2,
				offsetLeft : -15,
				zIndex : 5000,
				buttons : buttonsAr,
				autoHide : autoHide
			}
		);

		this.popup.show();

		if(gainFocus)
		{
			BX.focus(this.PopupSearchInput);
		}
	}
	return false;
};

CRM.PopupSave2 = function(crmID)
{
	if (!obYNSIR[crmID])
		return false;

	obYNSIR[crmID].PopupSave();
};

CRM.prototype._handleAcceptBtnClick = function()
{
	this.PopupSave();
	this.popup.close();
};

CRM.prototype.AddOnSaveListener = function(listener)
{
	if(typeof(listener) != 'function')
	{
		return;
	}

	var ary = this.onSaveListeners;
	for(var i = 0; i < ary.length; i++)
	{
		if(ary[i] == listener)
		{
			return;
		}
	}
	ary.push(listener);
};

CRM.prototype.RemoveOnSaveListener = function(listener)
{
	var ary = this.onSaveListeners;
	for(var i = 0; i < ary.length; i++)
	{
		if(ary[i] == listener)
		{
			ary.splice(i, 1);
			break;
		}
	}
};

CRM.prototype.AddOnBeforeSearchListener = function(listener)
{
	if(typeof(listener) != 'function')
	{
		return;
	}

	var ary = this.onBeforeSearchListeners;
	for(var i = 0; i < ary.length; i++)
	{
		if(ary[i] == listener)
		{
			return;
		}
	}
	ary.push(listener);
};

CRM.prototype.RemoveOnBeforeSearchListener = function(listener)
{
	var ary = this.onBeforeSearchListeners;
	for(var i = 0; i < ary.length; i++)
	{
		if(ary[i] == listener)
		{
			ary.splice(i, 1);
			break;
		}
	}
};

CRM.prototype.PopupSave = function()
{
	var arElements = {};
	for (var i in this.PopupEntityType)
	{
		var elements = BX.findChildren(BX("crm-"+this.crmID+"_"+this.name+"-block-"+this.PopupEntityType[i]+"-selected"), {className: "crm-block-cont-block-item"});
		if (elements !== null)
		{
			var el = 0;
			arElements[this.PopupEntityType[i]] = {};
			for(var e=0; e<elements.length; e++)
			{
				var elementIdLength = "selected-crm-"+this.crmID+"_"+this.name+"-block-item-";
				var elementId = elements[e].id.substr(elementIdLength.length);

				var data =  {
					'id' : this.PopupItem[elementId]['id'],
					'type' : this.PopupEntityType[i],
					'place' : this.PopupItem[elementId]['place'],
					'title' : this.PopupItem[elementId]['title'],
					'desc' : this.PopupItem[elementId]['desc'],
					'url' : this.PopupItem[elementId]['url'],
					'image' : this.PopupItem[elementId]['image'],
					'largeImage' : this.PopupItem[elementId]['largeImage']
				};

				if(typeof(this.PopupItem[elementId]['customData']) != 'undefined')
				{
					data['customData'] = this.PopupItem[elementId]['customData'];
				}
				if(typeof(this.PopupItem[elementId]['advancedInfo']) != 'undefined')
				{
					data['advancedInfo'] = this.PopupItem[elementId]['advancedInfo'];
				}

				arElements[this.PopupEntityType[i]][el] = data;

				el++;
			}
		}
	}

	var ary = this.onSaveListeners;
	if(ary.length > 0)
	{
		for(var j = 0; j < ary.length; j++)
		{
			try
			{
				ary[j](arElements);
			}
			catch(ex)
			{
			}
		}
	}

	if(!this.disableMarkup)
	{
		this.PopupCreateValue(arElements);
	}
};

CRM.prototype.ClearSelectItems = function()
{
	this.PopupItemSelected = {};
};

CRM.PopupShowBlock = function(crmID, element, search)
{
	if (!obYNSIR[crmID])
		return false;

	for(var i=0; i<obYNSIR[crmID].PopupTabs.length; i++)
	{
		if(obYNSIR[crmID].PopupTabs[i] == element)
		{
			obYNSIR[crmID].PopupTabsIndex=i;
			obYNSIR[crmID].PopupTabsIndexId = obYNSIR[crmID].PopupTabs[i].id;
		}
		obYNSIR[crmID].PopupBlock[i].style.display="none";
		BX.removeClass(obYNSIR[crmID].PopupTabs[i],"selected");
	}
	if(!search)
	{
		BX.addClass(element, "selected");
		obYNSIR[crmID].PopupSearchInput.value = "";
		BX('crm-'+crmID+'_'+obYNSIR[crmID].name+'-block-search').innerHTML = '';
	}
	else
		BX.addClass(obYNSIR[crmID].PopupTabs[obYNSIR[crmID].PopupTabsIndex], "selected");

	obYNSIR[crmID].PopupBlock[obYNSIR[crmID].PopupTabsIndex].style.display="block";
	BX('crm-'+crmID+'_'+obYNSIR[crmID].name+'-block-search').style.display="none";
	BX.removeClass(obYNSIR[crmID].PopupSearch[1], "selected");
	BX.addClass(obYNSIR[crmID].PopupSearch[0], "selected");

	BX.focus(obYNSIR[crmID].PopupSearchInput);
};

CRM.PopupShowSearchBlock = function(crmID, element)
{
	if (!obYNSIR[crmID])
		return false;

	for(var i=0; i<obYNSIR[crmID].PopupBlock.length; i++)
		obYNSIR[crmID].PopupBlock[i].style.display="none";

	var search=true;
	if(element == obYNSIR[crmID].PopupSearch[0])
	{
		CRM.PopupShowBlock(crmID, BX(obYNSIR[crmID].YNSIRPopupTabsIndexId), search);
		return false;
	}

	BX('crm-'+obYNSIR[crmID].crmID+"_"+obYNSIR[crmID].name+'-block-search').style.display="block";
	BX.removeClass(obYNSIR[crmID].PopupSearch[0], "selected");
	BX.addClass(element, "selected");

	BX.focus(obYNSIR[crmID].PopupSearchInput);
};

CRM.PopupSelectItem = function(crmID, element, tab, unsave, select)
{
	if (!obYNSIR[crmID])
		return false;

	var flag=element;
	if(flag.check)
	{
		if (select === undefined || select == false)
			CRM.PopupUnselectItem(crmID, element.id, "selected-"+element.id);
		return false;
	}

	elementIdLength = "crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-item-";
	elementId = element.id.substr(elementIdLength.length);
	var addYNSIRItems=document.createElement('span');
	addYNSIRItems.className = "crm-block-cont-block-item";
	addYNSIRItems.id="selected-"+element.id;

	var addYNSIRDelBut=document.createElement('i');
	var addYNSIRLink=document.createElement('a');
	addYNSIRLink.href=obYNSIR[crmID].PopupItem[elementId]['url'];
	addYNSIRLink.target="_blank";

	var blockWrap;
	if (tab === null)
	{
		if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+'_'+obYNSIR[crmID].name+"-tab-lead")
			blockWrap=BX("crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-lead-selected");

		if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+'_'+obYNSIR[crmID].name+"-tab-contact")
			blockWrap=BX("crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-contact-selected");

		if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+'_'+obYNSIR[crmID].name+"-tab-deal")
			blockWrap=BX("crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-deal-selected");

		if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+'_'+obYNSIR[crmID].name+"-tab-quote")
			blockWrap=BX("crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-quote-selected");

		if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+'_'+obYNSIR[crmID].name+"-tab-company")
			blockWrap=BX("crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-company-selected");

	}
	else
		blockWrap=BX("crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-"+tab+"-selected");

	if (obYNSIR[crmID].PopupMultiple)
	{
		blockTitle = BX.findChild(blockWrap, { className : "crm-block-cont-right-title-count"}, true);
		blockTitle.innerHTML = parseInt(blockTitle.innerHTML)+1;
		BX.addClass(element, "crm-block-cont-item-selected");
		BX.addClass(blockWrap, "crm-added-item");
		flag.check=1;
	}
	else
	{
		for (var i in obYNSIR[crmID].PopupEntityType)
		{
			BX.removeClass(BX("crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-"+obYNSIR[crmID].PopupEntityType[i]+"-selected"), "crm-added-item");
			elements = BX.findChildren(BX("crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-"+obYNSIR[crmID].PopupEntityType[i]+"-selected"), {className: "crm-block-cont-block-item"});
			if (elements !== null)
				for (var i in elements)
					BX.remove(elements[i]);

		}
	}
	blockWrap.appendChild(addYNSIRItems).appendChild(addYNSIRDelBut);

	blockWrap.appendChild(addYNSIRItems).appendChild(addYNSIRLink).innerHTML=BX.util.htmlspecialchars(obYNSIR[crmID].PopupItem[elementId]['title']);

	eval('BX.bind(addYNSIRDelBut, "click", function(event) {CRM.PopupUnselectItem("'+crmID+'", element.id, "selected-"+element.id); BX.PreventDefault(event);})');

	obYNSIR[crmID].PopupItemSelected[elementId] = element;

	if (!obYNSIR[crmID].PopupMultiple && (unsave === undefined || unsave == false))
	{
		obYNSIR[crmID].PopupSave();

		if (BX.PopupWindowManager._currentPopup !== null
			&& BX.PopupWindowManager._currentPopup.uniquePopupId == "CRM-"+this.crmID+"-popup")
		{
			BX.PopupWindowManager._currentPopup.close();
		}
	}
};

CRM.PopupUnselectItem = function(crmID, element, selected)
{
	if (!obYNSIR[crmID])
		return false;

	if (obYNSIR[crmID].PopupMultiple)
	{
		if(BX(selected).parentNode.getElementsByTagName('span').length == 3)
			BX.removeClass(BX(selected).parentNode, "crm-added-item");

		blockTitle = BX.findChild(BX(selected).parentNode, { className : "crm-block-cont-right-title-count"}, true);
		blockTitle.innerHTML = parseInt(blockTitle.innerHTML)-1;

		obj = BX(element);
		if (obj !== null)
		{
			obj.check=0;
			BX.removeClass(obj, "crm-block-cont-item-selected");
		}
	}
	elementIdLength = "crm-"+crmID+'_'+obYNSIR[crmID].name+"-block-item-";
	elementId = element.substr(elementIdLength.length);
	delete obYNSIR[crmID].PopupItemSelected[elementId];

	BX.remove(BX(selected));
};

CRM.prototype.SetPopupItems = function(place, items)
{
	this.PopupItem = {};
	this.PopupItemSelected = {};

	var placeHolder = BX('crm-' + this.crmID + '_' + this.name + '-block-' + place);
	BX.cleanNode(placeHolder);

	for (var i = 0; i < items.length; i++)
	{
		var item = items[i];
		item['place'] = place;
		//item['selected'] = 'Y';
		this.PopupAddItem(item);
	}
};

CRM.prototype.PopupSetItem = function(id)
{
	ar = id.toString().split('_');
	if (ar[1] !== undefined)
	{
		entityShortName = ar[0];
		entityId = ar[1];

		if (entityShortName == 'L')
			entityType = 'lead';
		else if (entityShortName == 'C')
			entityType = 'contact';
		else if (entityShortName == 'CO')
			entityType = 'company';
		else if (entityShortName == 'D')
			entityType = 'deal';
		else if (entityShortName == 'Q')
			entityType = 'quote';
	}
	else
	{
		for (var i in this.PopupEntityType)
			entityType = this.PopupEntityType[i];
		entityId = id;
	}

	var crm = this;

	var options = {
		'REQUIRE_REQUISITE_DATA': (crm.options.requireRequisiteData) ? 'Y' : 'N'
	};

	if (BX.type.isPlainObject(crm.options["searchOptions"]))
	{
		var searchOptions = crm.options["searchOptions"];
		for(var optionName in searchOptions)
		{
			if (searchOptions.hasOwnProperty(optionName))
				options[optionName] = searchOptions[optionName];
		}
	}

	BX.ajax({
		url: '/bitrix/components/bitrix/crm.'+entityType+'.list/list.ajax.php',
		method: 'POST',
		dataType: 'json',
		data: {'MODE' : 'SEARCH', 'VALUE' : '[' + entityId + ']', 'MULTI' : (crm.PopupPrefix? 'Y': 'N'), 'OPTIONS': options},
		onsuccess: function(data)
		{
			for (var i in data) {
				data[i]['selected'] = 'Y';
				crm.PopupAddItem(data[i]);
			}
			crm.PopupSave();
		},
		onfailure: function(data)
		{
		}
	});
};

CRM.prototype.PopupAddItem = function(arParam)
{
	if (arParam['place'] === undefined || arParam['place'] == '')
		arParam['place'] = arParam['type'];

	bElementSelected = false;
	if (this.PopupItemSelected[arParam['id']+'-'+arParam['place']] !== undefined)
		bElementSelected = true;

	itemBody = document.createElement("span");
	itemBody.id = 'crm-'+this.crmID+"_"+this.name+'-block-item-'+arParam['id']+'-'+arParam['place'];
	itemBody.className = "crm-block-cont-item"+(bElementSelected? " crm-block-cont-item-selected": "");
	itemBody.check=bElementSelected? 1: 0;

	if (arParam['type'] == 'contact' || arParam['type'] == 'company')
	{
		itemAvatar = document.createElement("span");
		itemAvatar.className = "crm-avatar";

		if (arParam['image'] !== undefined && arParam['image'] != '')
		{
			itemAvatar.style.background = 'url("' + arParam['image'] + '") no-repeat';
		}

		itemBody.appendChild(itemAvatar);
	}

	itemTitle = document.createElement("ins");
	itemTitle.appendChild(document.createTextNode(arParam['title']));
	itemId = document.createElement("var");
	itemId.className = "crm-block-cont-var-id";
	itemId.appendChild(document.createTextNode(arParam['id']));
	itemUrl = document.createElement("var");
	itemUrl.className = "crm-block-cont-var-url";
	itemUrl.appendChild(document.createTextNode(arParam['url']));

	var itemDesc = document.createElement("span");
	itemDesc.innerHTML = this.prepareDescriptionHtml(arParam['desc']);
	var bodyBox = document.createElement("span");
	bodyBox.className = "crm-block-cont-contact-info";
	bodyBox.appendChild(itemTitle);
	bodyBox.appendChild(itemDesc);
	bodyBox.appendChild(itemId);
	bodyBox.appendChild(itemUrl);
	itemBody.appendChild(bodyBox);
	itemBody.appendChild(document.createElement("i"));

	bDefinedItem = false;
	if (arParam['place'] != 'search' && this.PopupItem[arParam['id']+'-'+arParam['place']] !== undefined)
		bDefinedItem = true;
	else
		this.PopupItem[arParam['id']+'-'+arParam['place']] = arParam;

	var placeHolder = BX("crm-"+this.crmID+"_"+this.name+"-block-"+arParam['place']);

	if (placeHolder !== null)
	{
		if (!bDefinedItem)
			placeHolder.appendChild(itemBody);

		CRM._bindPopupItem(this.crmID, itemBody, arParam["type"]);

		if (arParam['selected'] !== undefined && arParam['selected'] == 'Y')
			CRM.PopupSelectItem(this.crmID, itemBody, arParam['type'], true, true);
	}
};
CRM._bindPopupItem = function(ownerId, itemBody, type)
{
	BX.bind(
		itemBody,
		"click",
		function(e){ CRM.PopupSelectItem(ownerId, itemBody, type); return BX.PreventDefault(e); });
};
CRM.prototype.prepareDescriptionHtml = function(str)
{
	if(!str.replace)
	{
		return str;
	}

	//Escape tags and quotes
	return str.replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
};
CRM.SearchChange = function(crmID)
{
	if (!obYNSIR[crmID])
		return false;

	var searchValue = obYNSIR[crmID].PopupSearchInput.value;
	if (searchValue == '')
		return false;

	var entityType = '';
	if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+"_"+obYNSIR[crmID].name+"-tab-lead")
		entityType = 'lead';
	else if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+"_"+obYNSIR[crmID].name+"-tab-contact")
		entityType = 'contact';
	else if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+"_"+obYNSIR[crmID].name+"-tab-deal")
		entityType = 'deal';
	else if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+"_"+obYNSIR[crmID].name+"-tab-quote")
		entityType = 'quote';
	else if(obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+"_"+obYNSIR[crmID].name+"-tab-company")
		entityType = 'company';
	else
		entityType = obYNSIR[crmID].PopupEntityType;

	var options = {
		'REQUIRE_REQUISITE_DATA': (obYNSIR[crmID].options.requireRequisiteData) ? 'Y' : 'N'
	};

	if (BX.type.isPlainObject(obYNSIR[crmID].options["searchOptions"]))
	{
		var searchOptions = obYNSIR[crmID].options["searchOptions"];
		for(var optionName in searchOptions)
		{
			if (searchOptions.hasOwnProperty(optionName))
				options[optionName] = searchOptions[optionName];
		}
	}

	var postData = { 'MODE' : 'SEARCH', 'VALUE' : searchValue, 'MULTI' : (obYNSIR[crmID].PopupPrefix? 'Y': 'N'),
		'OPTIONS': options };
	if (crmID === "new_invoice_product_button")
	{
		postData["ENTITY_TYPE"] = "INVOICE";
	}
	var postUrl = '/bitrix/components/bitrix/crm.' + entityType + '.list/list.ajax.php';
	var handlers = obYNSIR[crmID].onBeforeSearchListeners;
	if(handlers && BX.type.isArray(handlers) && handlers.length > 0)
	{
		var data = { 'entityType':entityType, 'postData': postData };
		for(var j = 0; j < handlers.length; j++)
		{
			try
			{
				handlers[j](data);
			}
			catch(ex)
			{
			}

			postData = data['postData'];
		}
	}

	CRM.PopupShowSearchBlock(crmID, obYNSIR[crmID].PopupSearch[1]);

	setTimeout(function() {
		if(typeof(obYNSIR[crmID]) === "undefined")
		{
			return;
		}

		if (BX('crm-'+crmID+"_"+obYNSIR[crmID].name+'-block-search').innerHTML == ''
		&& obYNSIR[crmID].PopupTabsIndexId=="crm-"+crmID+"_"+obYNSIR[crmID].name+"-tab-"+entityType) {
			var spanWait = document.createElement('div');
			spanWait.className="crm-block-cont-search-wait";
			spanWait.innerHTML=obYNSIR[crmID].PopupLocalize['wait'];
			BX('crm-'+crmID+"_"+obYNSIR[crmID].name+'-block-search').appendChild(spanWait);
		}
	}, 3000);
	BX.ajax({
		url: postUrl,
		method: 'POST',
		dataType: 'json',
		data: postData,
		onsuccess: function(data)
		{
			if (obYNSIR[crmID].PopupTabsIndexId!="crm-"+crmID+"_"+obYNSIR[crmID].name+"-tab-"+entityType)
				return false;

			BX('crm-'+crmID+"_"+obYNSIR[crmID].name+'-block-search').className = 'crm-block-cont-block crm-block-cont-block-'+entityType;
			BX('crm-'+crmID+"_"+obYNSIR[crmID].name+'-block-search').innerHTML = '';
			el = 0;
			for (var i in data) {
				data[i]['place'] = 'search';
				obYNSIR[crmID].PopupAddItem(data[i]);
				el++;
			}
			if (el == 0)
			{
				var spanWait = document.createElement('div');
				spanWait.className="crm-block-cont-search-no-result";
				spanWait.innerHTML=obYNSIR[crmID].PopupLocalize['noresult'];
				BX('crm-'+crmID+"_"+obYNSIR[crmID].name+'-block-search').appendChild(spanWait);
			}
		},
		onfailure: function(data)
		{

		}
	});
};

CRM.prototype.PopupCreateValue = function(arElements)
{
	var inputBox = BX("crm-"+this.crmID+"_"+this.name+"-input-box");
	var textBox = BX("crm-"+this.crmID+"_"+this.name+"-text-box");

	if(!inputBox || !textBox)
	{
		return;
	}

	inputBox.innerHTML = '';

	var textBoxNew = document.createElement('DIV');
	textBoxNew.id = textBox.id;
	textBox.parentNode.replaceChild(textBoxNew, textBox);
	textBox = textBoxNew;

	var tableObject = document.createElement('table');
	tableObject.className = "field_crm";
	tableObject.cellPadding = "0";
	tableObject.cellSpacing = "0";
	var tbodyObject = document.createElement('TBODY');

	var iEl = 0;
	for (var type in arElements)
	{
		var rowObject = document.createElement("TR");
		rowObject.className = "crmPermTableTrHeader";

		if (this.PopupEntityType.length > 1)
		{
			var cellObject = document.createElement("TD");
			cellObject.className = "field_crm_entity_type";
			cellObject.appendChild(document.createTextNode(this.PopupLocalize[type]+":"));
			rowObject.appendChild(cellObject);
		}

		cellObject = document.createElement("TD");
		cellObject.className = "field_crm_entity";

		var iTypeEl = 0;
		for (var i in arElements[type])
		{
			var addInput=document.createElement('input');
			addInput.type = 'text';
			addInput.name = this.name+(this.PopupMultiple? '[]': '');
			addInput.value = arElements[type][i]['id'];

			inputBox.appendChild(addInput);

			var addYNSIRLink=document.createElement('a');
			addYNSIRLink.href=arElements[type][i]['url'];
			addYNSIRLink.target="_blank";
			addYNSIRLink.appendChild(document.createTextNode(arElements[type][i]['title']));
			cellObject.appendChild(addYNSIRLink);

			var addYNSIRDeleteLink=document.createElement('span');
			addYNSIRDeleteLink.className="crm-element-item-delete";
			addYNSIRDeleteLink.id="deleted-crm-"+this.crmID+'_'+this.name+"-block-item-"+arElements[type][i]['id']+'-'+arElements[type][i]['place'];
			eval('BX.bind(addYNSIRDeleteLink, "click", function(event) { CRM.PopupUnselectItem("'+this.crmID+'", this.id.substr(8), "selected-"+this.id.substr(8)); CRM.PopupSave2("'+this.crmID+'");})');
			cellObject.appendChild(addYNSIRDeleteLink);

			iTypeEl++;
			iEl++;
		}

		if(iTypeEl > 0)
		{
			rowObject.appendChild(cellObject);
			tbodyObject.appendChild(rowObject);
		}

	}
	if (iEl == 0)
	{
		var addInput=document.createElement('input');
		addInput.type = 'text';
		addInput.name = this.name+(this.PopupMultiple? '[]': '');
		addInput.value = '';
		inputBox.appendChild(addInput);
	}
	tableObject.appendChild(tbodyObject);
	textBox.appendChild(tableObject);

	if(this.el)
	{
		if (iEl>0)
		{
			this.el.innerHTML = this.PopupLocalize['edit'];
		}
		else
		{
			BX.cleanNode(textBox, false);

			if(BX.browser.IsIE())
			{
				// HACK: empty DIV has height in IE7 - make it collapse to zero.
				textBox.style.fontSize = '0px';
				textBox.style.lineHeight = '0px';
			}
			this.el.innerHTML = this.PopupLocalize['add'];
		}
	}
};

CRM.prototype.popupShowMarkup = function()
{
	var layer1 = document.createElement("div");
	layer1.id = "crm-"+this.crmID+"_"+this.name+"-block-content-wrap";
	layer1.className = "crm-block-content";
	var table1 = document.createElement('table');
	table1.className = "crm-box-layout";
	if (!this.PopupMultiple)
		table1.className = table1.className+" crm-single-column";
	table1.cellSpacing = "0";

	var table1body = document.createElement('tbody');
	var table1bodyTr1 = document.createElement("TR");
	var table1bodyTd1 = document.createElement("TD");
	table1bodyTd1.className = "crm-block-cont-left";

	var layer4 = document.createElement("div");
	layer4.id = "crm-"+this.crmID+"_"+this.name+"-tabs";
	layer4.className = "crm-block-cont-tabs-wrap";
	if (this.PopupEntityType.length == 1)
		layer4.className = layer4.className+" crm-single-entity";

	var firstTab = true;
	for (var i in this.PopupEntityType) {
		var tab1 = document.createElement("span");
		tab1.className = "crm-block-cont-tabs"+(firstTab? " selected": '');
		tab1.id = "crm-"+this.crmID+"_"+this.name+"-tab-"+this.PopupEntityType[i];
			var tab1span = document.createElement("span");
			var tab1span1 = document.createElement("span");
			tab1span1.appendChild(document.createTextNode(this.PopupLocalize[this.PopupEntityType[i]]));
			tab1span.appendChild(tab1span1);
			tab1.appendChild(tab1span);
		layer4.appendChild(tab1);
		firstTab = false;
	}

	table1bodyTd1.appendChild(layer4);

	layer4 = document.createElement("div");
	layer4.id = "crm-"+this.crmID+"_"+this.name+"-block-cont-search";
	layer4.className = "crm-block-cont-search";

	var input = document.createElement("input");
	input.type = "text";
	input.id = "crm-"+this.crmID+"_"+this.name+"-search-input";
	layer4.appendChild(input);

	var search1 = document.createElement("span");
	search1.className = "crm-block-cont-search-tab selected";
	search1.appendChild(document.createElement("span"));

	var search1a = document.createElement("a");
	search1a.href="#";
	search1a.appendChild(document.createTextNode(this.PopupLocalize['last']));
	search1.appendChild(search1a);

	search1.appendChild(document.createElement("span"));
	layer4.appendChild(search1);

	search1 = document.createElement("span");
	search1.className = "crm-block-cont-search-tab";
		search1.appendChild(document.createElement("span"));

		search1a = document.createElement("a");
		search1a.href="#";
		search1a.appendChild(document.createTextNode(this.PopupLocalize['search']));
		search1.appendChild(search1a);

		search1.appendChild(document.createElement("span"));
	layer4.appendChild(search1);

	table1bodyTd1.appendChild(layer4);

	layer4 = document.createElement("div");
	layer4.className = "popup-window-hr popup-window-buttons-hr";
		layer4.appendChild(document.createElement("b"));
	table1bodyTd1.appendChild(layer4);

	layer4 = document.createElement("div");
	layer4.id = "crm-"+this.crmID+"_"+this.name+"-blocks";
	layer4.className = "crm-block-cont-blocks-wrap";

		firstTab = true;
		for (var i in this.PopupEntityType) {
			var layer5 = document.createElement("div");
			layer5.id = "crm-"+this.crmID+"_"+this.name+"-block-"+this.PopupEntityType[i];
			layer5.className = "crm-block-cont-block crm-block-cont-block-"+this.PopupEntityType[i];
			layer5.style.display = firstTab? "block": "none";
			layer4.appendChild(layer5);
			firstTab = false;
		}

		layer5 = document.createElement("div");
		layer5.id = "crm-"+this.crmID+"_"+this.name+"-block-search";
		layer5.className = "crm-block-cont-block";
		layer5.style.display = "none";
		layer4.appendChild(layer5);

		layer5 = document.createElement("div");
		layer5.id = "crm-"+this.crmID+"_"+this.name+"-block-declared";
		layer5.className = "crm-block-cont-block";
		layer5.style.display = "none";
		layer4.appendChild(layer5);

		table1bodyTd1.appendChild(layer4);
		table1bodyTr1.appendChild(table1bodyTd1);
		var table1bodyTd2 = document.createElement("TD");
		table1bodyTd2.className = "crm-block-cont-right";

		var layer2 = document.createElement("div");
		layer2.className = "crm-block-cont-right-wrap-item";

		for (var i in this.PopupEntityType) {
			var layer3 = document.createElement("div");
			layer3.className = "crm-block-cont-right-item";
			layer3.id = "crm-"+this.crmID+"_"+this.name+"-block-"+this.PopupEntityType[i]+"-selected";
			var layer3cont = document.createElement("span");
			layer3cont.className = "crm-block-cont-right-title";
			layer3cont.appendChild(document.createTextNode(this.PopupLocalize[this.PopupEntityType[i]]));
			layer3cont.appendChild(document.createTextNode(' ('));
			var spanDigit = document.createElement("span");
			spanDigit.className = "crm-block-cont-right-title-count";
			spanDigit.appendChild(document.createTextNode('0'));
			layer3cont.appendChild(spanDigit);
			layer3cont.appendChild(document.createTextNode(')'));
			layer3.appendChild(layer3cont);
			layer2.appendChild(layer3);
		}

		table1bodyTd2.appendChild(layer2);
		table1bodyTr1.appendChild(table1bodyTd2);
		table1body.appendChild(table1bodyTr1);
		table1.appendChild(table1body);
		layer1.appendChild(table1);

	var placeHolder = document.createElement("div");
	document.body.appendChild(placeHolder);

	placeHolder.id = "crm-"+this.crmID+"_"+this.name+"-html-box";
	placeHolder.className = "crm-place-holder";
	placeHolder.appendChild(layer1);

	if(this.div)
	{
		var inputBox = document.createElement("div");
		inputBox.id = "crm-"+this.crmID+"_"+this.name+"-input-box";
		inputBox.style.display = "none";
		this.div.appendChild(inputBox);

		var textBoxId = "crm-"+this.crmID+"_"+this.name+"-text-box";
		if(BX(textBoxId))
		{
			throw  "Already exists " + textBoxId;
		}

		var textBox = document.createElement("div");
		this.div.insertBefore(textBox, this.div.firstChild);
		textBox.id = "crm-"+this.crmID+"_"+this.name+"-text-box";
	}
};
