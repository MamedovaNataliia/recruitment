(function() {
var BX = window.BX;
if (BX.YNSIRSocNetLogDestination)
{
	return;
}

BX.YNSIRSocNetLogDestination =
{
	popupWindow: null,
	popupSearchWindow: null,
	containerWindow: null,

	bByFocusEvent: false,
	bLoadAllInitialized: false,

	createSocNetGroupWindow: null,
	inviteEmailUserWindow: null,
	inviteEmailUserWindowSubmitted: false,
	inviteEmailCurrentName: null,

	sendEvent: true,
	extranetUser: false,

	obUseContainer: {},
	obShowSearchInput: {},
	obSendAjaxSearch: {},

	obUserNameTemplate: {},

	obCurrentElement: {
		last: null,
		search: null,
		group: false
	},
	obSearchFirstElement: null,
	obResult: {
		last: null,
		email: null,
		crmemail: null,
		search: null,
		group: false
	},
	obCursorPosition: {
		last: null,
		email: null,
		crmemail: null,
		search: null,
		group: false
	},

	obTabs: {},
	obCustomTabs: {},

	focusOnTabs: false,

	searchTimeout: null,
	createSonetGroupTimeout: null,

	obAllowAddSocNetGroup: {},
	obAllowAddUser: {},
	obAllowAddCrmContact: {},
	obAllowSearchEmailUsers: {},
	obAllowSearchCrmEmailUsers: {},
	obAllowSearchNetworkUsers: {},
	obAllowSearchSelf: {},
	obAllowSonetGroupsAjaxSearch: {},
	obAllowSonetGroupsAjaxSearchFeatures: {},

	obEmptySearchResult: {},
	obNewSocNetGroupCnt: {},

	obDepartmentEnable: {},
	obSonetgroupsEnable: {},
	obLastEnable: {},

	arDialogGroups: {},

	obWindowClass: {},
	obWindowCloseIcon: {},
	obPathToAjax: {},
	obDepartmentLoad: {},
	obDepartmentSelectDisable: {},
	obUserSearchArea: {},
	obItems: {},
	obItemsLast: {},
	obItemsSelected: {},
	obItemsSelectedUndeleted: {},
	obCallback: {},
	obShowVacations : {},

	obElementSearchInput: {},
	obElementSearchInputHidden: {},
	obElementBindMainPopup: {},
	obElementBindSearchPopup: {},
	obBindOptions: {},

	obSiteDepartmentID: {},

	obCrmFeed: {},
	obCrmTypes: {},
	obAllowUserSearch: {},

	bFinderInited: false,
	obClientDb: null,
	obClientDbData: {},
	obClientDbDataSearchIndex: {},

	oDbUserSearchResult: {},
	oAjaxUserSearchResult: {},

	obDestSort: {},

	oSearchWaiterEnabled: {},
	oSearchWaiterContentHeight: 0,

	obUseClientDatabase: {},

	bResultMoved: {
		search: false,
		last: false,
		group: false
	}, // cursor move
	oXHR: null,
	usersVacation : null,

	obTabSelected: {},

	obTemplateClass: {
		1: 'bx-finder-box-item',
		2: 'bx-finder-box-item-t2',
		3: 'bx-finder-box-item-t3',
		4: 'bx-finder-box-item-t3',
		5: 'bx-finder-box-item-t5',
		6: 'bx-finder-box-item-t6',
		7: 'bx-finder-box-item-t7',
		'department-user': 'bx-finder-company-department-employee-selected',
		'department': 'bx-finder-company-department-check-checked'
	},

	obTemplateClassSelected: {
		1: 'bx-finder-box-item-selected',
		2: 'bx-finder-box-item-t2-selected',
		3: 'bx-finder-box-item-t3-selected',
		4: 'bx-finder-box-item-t3-selected',
		5: 'bx-finder-box-item-t5-selected',
		6: 'bx-finder-box-item-t6-selected',
		7: 'bx-finder-box-item-t7-selected',
		'department-user': 'bx-finder-company-department-employee-selected',
		'department': 'bx-finder-company-department-check-checked'
	},

	searchStarted : false
};

BX.YNSIRSocNetLogDestination.init = function(arParams)
{
	if(!arParams.name)
	{
		arParams.name = 'lm';
	}

	BX.YNSIRSocNetLogDestination.obPathToAjax[arParams.name] = (!arParams.pathToAjax ? '/bitrix/components/bitrix/main.post.form/post.ajax.php' : arParams.pathToAjax);

	BX.YNSIRSocNetLogDestination.obShowSearchInput[arParams.name] = (
		typeof arParams.showSearchInput != 'undefined'
		&& !!arParams.showSearchInput
	);

	BX.YNSIRSocNetLogDestination.obSendAjaxSearch[arParams.name] = (
		typeof arParams.sendAjaxSearch != 'undefined'
			? !!arParams.sendAjaxSearch
			: true
	);

	BX.YNSIRSocNetLogDestination.obUseContainer[arParams.name] = (
		BX.YNSIRSocNetLogDestination.obShowSearchInput[arParams.name]
		|| (
			typeof arParams.useContainer != 'undefined'
			&& !!arParams.useContainer
		)
	);

	BX.YNSIRSocNetLogDestination.obUserNameTemplate[arParams.name] = (typeof arParams.userNameTemplate != 'undefined' ? arParams.userNameTemplate : '');
	BX.YNSIRSocNetLogDestination.obCallback[arParams.name] = arParams.callback;

	BX.YNSIRSocNetLogDestination.obElementBindMainPopup[arParams.name] = arParams.bindMainPopup;
	BX.YNSIRSocNetLogDestination.obElementBindSearchPopup[arParams.name] = arParams.bindSearchPopup;
	BX.YNSIRSocNetLogDestination.obElementSearchInput[arParams.name] = arParams.searchInput;
	BX.YNSIRSocNetLogDestination.obElementSearchInputHidden[arParams.name] = (typeof arParams.searchInputHidden != 'undefined' ? arParams.searchInputHidden : false);

	BX.YNSIRSocNetLogDestination.obBindOptions[arParams.name] = (typeof arParams.bindOptions != 'undefined' ? arParams.bindOptions : {});
	BX.YNSIRSocNetLogDestination.obBindOptions[arParams.name].forceBindPosition = true;

	BX.YNSIRSocNetLogDestination.obDepartmentSelectDisable[arParams.name] = (arParams.departmentSelectDisable == true);
	BX.YNSIRSocNetLogDestination.obUserSearchArea[arParams.name] = (BX.util.in_array(arParams.userSearchArea, ['I', 'E']) ? arParams.userSearchArea : false);
	BX.YNSIRSocNetLogDestination.obDepartmentLoad[arParams.name] = {};
	BX.YNSIRSocNetLogDestination.obWindowClass[arParams.name] = (!arParams.obWindowClass ? 'bx-lm-socnet-log-destination' : arParams.obWindowClass);
	BX.YNSIRSocNetLogDestination.obWindowCloseIcon[arParams.name] = (typeof (arParams.obWindowCloseIcon) == 'undefined' ? true : arParams.obWindowCloseIcon);
	BX.YNSIRSocNetLogDestination.extranetUser = arParams.extranetUser;

	BX.YNSIRSocNetLogDestination.obCrmFeed[arParams.name] = arParams.isCrmFeed;
	BX.YNSIRSocNetLogDestination.obCrmTypes[arParams.name] = (
		arParams.isCrmFeed
		&& typeof arParams.CrmTypes == 'object'
		&& arParams.CrmTypes.length > 0
			? arParams.CrmTypes
			: []
	);

	BX.YNSIRSocNetLogDestination.obAllowUserSearch[arParams.name] = !(typeof arParams.allowUserSearch != 'undefined' && arParams.allowUserSearch === false);

	BX.YNSIRSocNetLogDestination.obAllowAddSocNetGroup[arParams.name] = (arParams.allowAddSocNetGroup === true);
	BX.YNSIRSocNetLogDestination.obAllowAddUser[arParams.name] = (arParams.allowAddUser === true);
	BX.YNSIRSocNetLogDestination.obAllowAddCrmContact[arParams.name] = (arParams.allowAddCrmContact === true);
	BX.YNSIRSocNetLogDestination.obAllowSearchEmailUsers[arParams.name] = (
		typeof arParams.allowSearchEmailUsers != 'undefined'
			? (arParams.allowSearchEmailUsers === true)
			: (arParams.allowAddUser === true)
	);
	BX.YNSIRSocNetLogDestination.obAllowSearchCrmEmailUsers[arParams.name] = (
		typeof arParams.allowSearchCrmEmailUsers != 'undefined'
			? (arParams.allowSearchCrmEmailUsers === true)
			: false
	);
	BX.YNSIRSocNetLogDestination.obAllowSearchNetworkUsers[arParams.name] = (
		typeof arParams.allowSearchNetworkUsers != 'undefined'
			? (arParams.allowSearchNetworkUsers === true)
			: false
	);
	BX.YNSIRSocNetLogDestination.obAllowSearchSelf[arParams.name] = (
		typeof arParams.allowSearchSelf != 'undefined'
			? arParams.allowSearchSelf !== false
			: true
	);

	BX.YNSIRSocNetLogDestination.obSiteDepartmentID[arParams.name] = (typeof (arParams.siteDepartmentID) != 'undefined' && parseInt(arParams.siteDepartmentID) > 0 ? parseInt(arParams.siteDepartmentID) : false);

	BX.YNSIRSocNetLogDestination.obNewSocNetGroupCnt[arParams.name] = 0;

	BX.YNSIRSocNetLogDestination.obLastEnable[arParams.name] = (arParams.lastTabDisable != true);
	BX.YNSIRSocNetLogDestination.oDbUserSearchResult[arParams.name] = {};

	BX.YNSIRSocNetLogDestination.obDestSort[arParams.name] = (typeof arParams.destSort != 'undefined' ? arParams.destSort : []);

	BX.YNSIRSocNetLogDestination.obDepartmentEnable[arParams.name] = (!!arParams.enableDepartments);
	if (
		!BX.YNSIRSocNetLogDestination.obDepartmentEnable[arParams.name]
		&& arParams.items.department
	)
	{
		for(var i in arParams.items.department)
		{
			BX.YNSIRSocNetLogDestination.obDepartmentEnable[arParams.name] = true;
			break;
		}
	}

	BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[arParams.name] = (!!arParams.enableSonetgroups);
	if (
		!BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[arParams.name]
		&& arParams.items.sonetgroups
	)
	{
		for(var i in arParams.items.sonetgroups)
		{
			BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[arParams.name] = true;
			break;
		}
	}

	BX.YNSIRSocNetLogDestination.obAllowSonetGroupsAjaxSearch[arParams.name] = (
		BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[arParams.name]
		&& typeof arParams.allowSonetGroupsAjaxSearch != 'undefined'
		&& arParams.allowSonetGroupsAjaxSearch === true
	);

	BX.YNSIRSocNetLogDestination.obAllowSonetGroupsAjaxSearchFeatures[arParams.name] = (
		BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[arParams.name]
		&& typeof arParams.allowSonetGroupsAjaxSearchFeatures != 'undefined'
			? arParams.allowSonetGroupsAjaxSearchFeatures
			: {}
	);

	BX.YNSIRSocNetLogDestination.obUseClientDatabase[arParams.name] = true;

	if (
		typeof arParams.useClientDatabase != 'undefined'
		&& arParams.useClientDatabase === false
	)
	{
		BX.YNSIRSocNetLogDestination.obUseClientDatabase[arParams.name] = false;
	}

	BX.YNSIRSocNetLogDestination.obTabs[arParams.name] = [];
	if (BX.YNSIRSocNetLogDestination.obLastEnable[arParams.name])
	{
		BX.YNSIRSocNetLogDestination.obTabs[arParams.name].push('last');
	}
	if (BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[arParams.name])
	{
		BX.YNSIRSocNetLogDestination.obTabs[arParams.name].push('group');
	}
	if (BX.YNSIRSocNetLogDestination.obDepartmentEnable[arParams.name])
	{
		BX.YNSIRSocNetLogDestination.obTabs[arParams.name].push('department');
	}
	if (BX.YNSIRSocNetLogDestination.obAllowSearchEmailUsers[arParams.name])
	{
		BX.YNSIRSocNetLogDestination.obTabs[arParams.name].push('email');
	}
	if (BX.YNSIRSocNetLogDestination.obAllowSearchCrmEmailUsers[arParams.name])
	{
		BX.YNSIRSocNetLogDestination.obTabs[arParams.name].push('crmemail');
	}

	BX.addCustomEvent(BX.YNSIRSocNetLogDestination, "onTabsAdd", BX.delegate(this.onTabsAdd, this));

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name] = [];

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: true,
		groupCode: 'contacts',
		className: 'bx-lm-element-contacts',
		title: BX.message('LM_POPUP_TAB_LAST_CONTACTS')
	});

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: true,
		groupCode: 'companies',
		className: 'bx-lm-element-companies',
		title: BX.message('LM_POPUP_TAB_LAST_COMPANIES')
	});

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: true,
		groupCode: 'leads',
		className: 'bx-lm-element-leads',
		title: BX.message('LM_POPUP_TAB_LAST_LEADS')
	});

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: true,
		groupCode: 'deals',
		className: 'bx-lm-element-deals',
		avatarLessMode: true,
		title: BX.message('LM_POPUP_TAB_LAST_DEALS')
	});
	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: true,
		groupCode: 'candidate',
		className: 'bx-lm-element-candidate',
		avatarLessMode: true,
		title: BX.message('LM_POPUP_TAB_LAST_DEALS')
	});
	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: true,
		groupCode: 'joborder',
		className: 'bx-lm-element-joborder',
		avatarLessMode: true,
		title: BX.message('LM_POPUP_TAB_LAST_DEALS')
	});

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: false,
		groupCode: 'groups',
		bHideGroup: true,
		className: 'bx-lm-element-groups',
		descLessMode: true
	});

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: false,
		groupCode: 'users',
		className: 'bx-lm-element-user',
		descLessMode: true,
		title: BX.message('LM_POPUP_TAB_LAST_USERS')
	});

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: false,
		groupCode: 'crmemails',
		className: 'bx-lm-element-user',
		descLessMode: true,
		title: BX.message('LM_POPUP_TAB_LAST_CRMEMAILS')
	});

	BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
		bCrm: false,
		groupCode: 'sonetgroups',
		className: 'bx-lm-element-sonetgroup',
		classNameExtranetGroup: 'bx-lm-element-extranet',
		groupboxClassName: 'bx-lm-groupbox-sonetgroup',
		descLessMode: true,
		title: BX.message('LM_POPUP_TAB_LAST_SG')
	});

	if (BX.YNSIRSocNetLogDestination.obDepartmentEnable[arParams.name])
	{
		BX.YNSIRSocNetLogDestination.arDialogGroups[arParams.name].push({
			bCrm: false,
			groupCode: 'department',
			className: 'bx-lm-element-department',
			groupboxClassName: 'bx-lm-groupbox-department',
			descLessMode: true,
			title: BX.message('LM_POPUP_TAB_LAST_STRUCTURE')
		});
	}

	BX.YNSIRSocNetLogDestination.obItems[arParams.name] = BX.clone(arParams.items);
	BX.YNSIRSocNetLogDestination.obItemsLast[arParams.name] = BX.clone(arParams.itemsLast);
	BX.YNSIRSocNetLogDestination.obItemsSelected[arParams.name] = BX.clone(arParams.itemsSelected);
	BX.YNSIRSocNetLogDestination.obItemsSelectedUndeleted[arParams.name] = (typeof arParams.itemsSelectedUndeleted != 'undefined' ? BX.clone(arParams.itemsSelectedUndeleted) : {}) ;

	for (var itemId in BX.YNSIRSocNetLogDestination.obItemsSelected[arParams.name])
	{
		var type = BX.YNSIRSocNetLogDestination.obItemsSelected[arParams.name][itemId];
		BX.YNSIRSocNetLogDestination.runSelectCallback(itemId, type, arParams.name, false, 'init');
	}


	if (
		BX.YNSIRSocNetLogDestination.obUseClientDatabase[arParams.name]
		&& !BX.YNSIRSocNetLogDestination.bFinderInited
	)
	{
		BX.Finder(false, 'destination', [], {}, BX.YNSIRSocNetLogDestination);
		BX.onCustomEvent(BX.YNSIRSocNetLogDestination, 'initFinderDb', [ BX.YNSIRSocNetLogDestination, arParams.name, null, ['users'], BX.YNSIRSocNetLogDestination]);
		BX.YNSIRSocNetLogDestination.bFinderInited = true;
	}

	if (
		typeof (arParams.LHEObjName) != 'undefined'
		&& BX('div' + arParams.LHEObjName)
	)
	{
		BX.addCustomEvent(BX('div' + arParams.LHEObjName), 'OnShowLHE', function(show) {
			if (!show)
			{
				if (BX.YNSIRSocNetLogDestination.isOpenDialog())
				{
					BX.YNSIRSocNetLogDestination.closeDialog();
				}
				BX.YNSIRSocNetLogDestination.closeSearch();
			}
		});
	}

	BX.YNSIRSocNetLogDestination.obTabSelected[arParams.name] = (
		BX.YNSIRSocNetLogDestination.obLastEnable[arParams.name]
			? 'last'
			: ''
	);

	if (!BX.YNSIRSocNetLogDestination.bLoadAllInitialized)
	{
		BX.addCustomEvent('loadAllFinderDb', function(params) {
			BX.YNSIRSocNetLogDestination.loadAll(params);
		});
		BX.YNSIRSocNetLogDestination.bLoadAllInitialized = true;
	}

	BX.YNSIRSocNetLogDestination.obShowVacations[arParams.name] = (
		typeof arParams.showVacations != 'undefined'
		&& arParams.showVacations === true
	);

	if (
		BX.YNSIRSocNetLogDestination.obShowVacations[arParams.name]
		&& BX.YNSIRSocNetLogDestination.usersVacation === null
		&& typeof arParams.usersVacation != 'undefined'
	)
	{
		BX.YNSIRSocNetLogDestination.usersVacation = arParams.usersVacation;
	}
};

BX.YNSIRSocNetLogDestination.reInit = function(name)
{
	for (var itemId in BX.YNSIRSocNetLogDestination.obItemsSelected[name])
	{
		var type = BX.YNSIRSocNetLogDestination.obItemsSelected[name][itemId];
		BX.YNSIRSocNetLogDestination.runSelectCallback(itemId, type, name, false, 'init');
	}
};

BX.YNSIRSocNetLogDestination.openContainer = function(name, params)
{
	if(!name)
	{
		name = 'lm';
	}

	if (!params)
	{
		params = {};
	}

	if (BX.YNSIRSocNetLogDestination.containerWindow != null)
	{
/*
		if (!BX.YNSIRSocNetLogDestination.bByFocusEvent)
		{
			BX.YNSIRSocNetLogDestination.popupWindow.close();
		}
*/

		return false;
	}

	BX.YNSIRSocNetLogDestination.containerWindow = new BX.PopupWindow('BXYNSIRSocNetLogDestinationContainer', params.bindNode || BX.YNSIRSocNetLogDestination.obElementBindMainPopup[name].node, {
		autoHide: true,
		zIndex: 1200,
		className: 'bx-finder-popup bx-finder-v2',
		offsetLeft: parseInt(BX.YNSIRSocNetLogDestination.obElementBindMainPopup[name].offsetLeft),
		offsetTop: parseInt(BX.YNSIRSocNetLogDestination.obElementBindMainPopup[name].offsetTop),
		bindOptions: BX.YNSIRSocNetLogDestination.obBindOptions[name],
		closeByEsc: true,
		closeIcon: BX.YNSIRSocNetLogDestination.obWindowCloseIcon[name] ? true : false,
		lightShadow: true,
		events: {
			onPopupShow : function() {

				if (
					BX.YNSIRSocNetLogDestination.sendEvent
					&& BX.YNSIRSocNetLogDestination.obCallback[name]
					&& BX.YNSIRSocNetLogDestination.obCallback[name].openDialog
				)
				{
					BX.YNSIRSocNetLogDestination.obCallback[name].openDialog(name);
				}

				if (
					BX.YNSIRSocNetLogDestination.inviteEmailUserWindow
					&& BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.isShown()
				)
				{
					BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.close();
				}
			},
			onPopupClose : function(event) {
				this.destroy();
			},
			onPopupDestroy : BX.proxy(function() {
				BX.YNSIRSocNetLogDestination.containerWindow = null;

				if (
					BX.YNSIRSocNetLogDestination.sendEvent
					&& BX.YNSIRSocNetLogDestination.obCallback[name]
				)
				{
					if (BX.YNSIRSocNetLogDestination.obCallback[name].closeDialog)
					{
						BX.YNSIRSocNetLogDestination.obCallback[name].closeDialog(name);
					}

					if (BX.YNSIRSocNetLogDestination.obCallback[name].closeSearch)
					{
						BX.YNSIRSocNetLogDestination.obCallback[name].closeSearch(name);
					}

				}
			}, this)
		},
		content: (
			!!BX.YNSIRSocNetLogDestination.obShowSearchInput[name]
				? BX.create('DIV', {
					children: [
						BX.create('DIV', {
							props: {
								className: 'bx-finder-box bx-finder-box-vertical bx-lm-box ' + BX.YNSIRSocNetLogDestination.obWindowClass[name]
							},
							style: {
								minWidth: '650px',
								paddingBottom: '8px',
								overflow: 'hidden'
							},
							children: [
								BX.create('DIV', {
									props: {
										className: "bx-finder-search-block"
									},
									children: [
										BX.create('DIV', {
											props: {
												className: "bx-finder-search-block-cell"
											},
											children: [
												BX.create('SPAN', {
													attrs: {
														id: 'feed-add-post-destination-item'
													}
												}),
												BX.create('SPAN', {
													attrs: {
														id: "feed-add-post-destination-input-box",
														style: "display: inline-block"
													},
													props: {
														className: "feed-add-destination-input-box"
													},
													children: [
														BX.create('INPUT', {
															attrs: {
																type: "text",
																id: "feed-add-post-destination-input"
															},
															props: {
																className: "feed-add-destination-inp"
															}
														})
													]
												})
											],
											events: {
												click: function(e) {
													BX.focus(BX.YNSIRSocNetLogDestination.obElementSearchInput[name]);
													return BX.PreventDefault(e);
												}
											}
										})
									]
								}),
								BX.create('div', {
									attrs: {
										id: "BXYNSIRSocNetLogDestinationContainerContent"
									},
									props: {
										className: "bx-finder-container-content"
									}
								})
							]
						})
					]
				})
				: BX.create('div', {
					attrs: {
						id: "BXYNSIRSocNetLogDestinationContainerContent"
					},
					props: {
						className: "bx-finder-container-content"
					}
				})
		)
	});

	if (!!BX.YNSIRSocNetLogDestination.obShowSearchInput[name])
	{
		BX.bind(BX('feed-add-post-destination-input'), 'keyup', BX.delegate(BX.YNSIRSocNetLogDestination.BXfpSearch, {
			formName: name,
			inputName: 'feed-add-post-destination-input',
			sendAjax: !!BX.YNSIRSocNetLogDestination.obSendAjaxSearch[name]
		}));
		BX.bind(BX('feed-add-post-destination-input'), 'paste', BX.delegate(BX.YNSIRSocNetLogDestination.BXfpSearch, {
			formName: name,
			inputName: 'feed-add-post-destination-input',
			sendAjax: !!BX.YNSIRSocNetLogDestination.obSendAjaxSearch[name]
		}));
		BX.bind(BX('feed-add-post-destination-input'), 'keydown', BX.delegate(BX.YNSIRSocNetLogDestination.BXfpSearchBefore, {
			formName: name,
			inputName: 'feed-add-post-destination-input'
		}));
		if (
			params["itemsHidden"]
			&& BX.message('BX_FPD_LINK_1')
			&& BX.message('BX_FPD_LINK_2')
		)
		{
			for (var ii in params["itemsHidden"])
			{
				if (params["itemsHidden"].hasOwnProperty(ii))
				{
					BX.YNSIRSocNetLogDestination.BXfpSelectCallback({
						item: {
							id: 'SG' + params["itemsHidden"][ii]["ID"],
							name: params["itemsHidden"][ii]["NAME"]
						},
						type: 'sonetgroups',
						bUndeleted: true,
						containerInput: BX('feed-add-post-destination-item'),
						valueInput: BX('feed-add-post-destination-input'),
						formName: window.BXYNSIRSocNetLogDestinationFormName,
						tagInputName: 'bx-destination-tag',
						tagLink1: BX.message('BX_FPD_LINK_1'),
						tagLink2: BX.message('BX_FPD_LINK_2'),
						state: 'init'
					});
				}
			}
		}

		BX.YNSIRSocNetLogDestination.obElementSearchInput[name] = BX('feed-add-post-destination-input');
		BX.defer(BX.focus)(BX.YNSIRSocNetLogDestination.obElementSearchInput[name]);
	}

	return true;
};

BX.YNSIRSocNetLogDestination.getDialogContent = function(name)
{
	var i = 0;

	var tabs = [
		(
			BX.YNSIRSocNetLogDestination.obLastEnable[name]
				? BX.create('A', {
					attrs: {
						hidefocus: 'true',
						id: 'destLastTab_' + name,
						href: '#switchTab'
					},
					props: {
						className: 'bx-finder-box-tab bx-lm-tab-last bx-finder-box-tab-selected'
					},
					events: {
						click: function () {
							return BX.YNSIRSocNetLogDestination.SwitchTab(name, this, 'last')
						}
					},
					html: BX.message('LM_POPUP_TAB_LAST')
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[name]
				? BX.create('A', {
					attrs: {
						hidefocus: 'true',
						id: 'destGroupTab_' + name,
						href: '#switchTab'
					},
					props: {
						className: 'bx-finder-box-tab bx-lm-tab-sonetgroup'
					},
					events: {
						click: function () {
							return BX.YNSIRSocNetLogDestination.SwitchTab(name, this, 'group')
						}
					},
					html: BX.message('LM_POPUP_TAB_SG')
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obDepartmentEnable[name]
				? BX.create('A', {
					attrs: {
						hidefocus: 'true',
						id: 'destDepartmentTab_' + name,
						href: '#switchTab'
					},
					props: {
						className: 'bx-finder-box-tab bx-lm-tab-department'
					},
					events: {
						click: function () {
							return BX.YNSIRSocNetLogDestination.SwitchTab(name, this, 'department')
						}
					},
					html: (BX.YNSIRSocNetLogDestination.obUserSearchArea[name] == 'E' ? BX.message('LM_POPUP_TAB_STRUCTURE_EXTRANET') : BX.message('LM_POPUP_TAB_STRUCTURE'))
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obAllowSearchEmailUsers[name]
				? BX.create('A', {
					attrs: {
						hidefocus: 'true',
						id: 'destEmailTab_' + name,
						href: '#switchTab'
					},
					props: {
						className: 'bx-finder-box-tab bx-lm-tab-email'
					},
					events: {
						click: function () {
							return BX.YNSIRSocNetLogDestination.SwitchTab(name, this, 'email')
						}
					},
					html: BX.message('LM_POPUP_TAB_EMAIL')
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obAllowSearchCrmEmailUsers[name]
				? BX.create('A', {
					attrs: {
						hidefocus: 'true',
						id: 'destCrmEmailTab_' + name,
						href: '#switchTab'
					},
					props: {
						className: 'bx-finder-box-tab bx-lm-tab-crmemail'
					},
					events: {
						click: function () {
							return BX.YNSIRSocNetLogDestination.SwitchTab(name, this, 'crmemail')
						}
					},
					html: BX.message('LM_POPUP_TAB_CRMEMAIL')
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obShowSearchInput[name]
				? BX.create('A', {
					attrs: {
						hidefocus: 'true',
						id: 'destSearchTab_' + name,
						href: '#switchTab'
					},
					props: {
						className: 'bx-finder-box-tab bx-lm-tab-search'
					},
					events: {
						click: function () {
							return BX.YNSIRSocNetLogDestination.SwitchTab(name, this, 'search')
						}
					},
					html: BX.message('LM_POPUP_TAB_SEARCH')
				})
				: null
		)
	];

	if (typeof BX.YNSIRSocNetLogDestination.obCustomTabs[name] != 'undefined')
	{
		for (i=0; i < BX.YNSIRSocNetLogDestination.obCustomTabs[name].length; i++)
		{
			tabs.push(
				BX.create('A', {
					attrs: {
						hidefocus: 'true',
						id: 'dest' + BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].id + 'Tab_' + name,
						href: '#switchTab'
					},
					props: {
						className: 'bx-finder-box-tab bx-lm-tab-' + BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].id
					},
					events: {
						click: BX.proxy(function(e) {
								var target = e.target || e.srcElement;
								return BX.YNSIRSocNetLogDestination.SwitchTab(name, target, BX.YNSIRSocNetLogDestination.obCustomTabs[name][this.tabNum].id)
							}, {
								tabNum: i
							}
						)
					},
					html: BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].name
				})
			);

			BX.YNSIRSocNetLogDestination.obResult[BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].id] = [];
		}
	}

	tabs.push(
		BX.create('DIV', {
			props: {
				className: 'popup-window-hr popup-window-buttons-hr'
			},
			children: [
				BX.create('I', {})
			]
		})
	);

	var contents = [
		(
			BX.YNSIRSocNetLogDestination.obLastEnable[name]
				? BX.create('DIV', {
					props: {
						className: 'bx-finder-box-tab-content bx-lm-box-tab-content-last' + (BX.YNSIRSocNetLogDestination.obLastEnable[name] ? ' bx-finder-box-tab-content-selected' : '')
					},
					html: BX.YNSIRSocNetLogDestination.getItemLastHtml(false, false, name)
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[name]
				? BX.create('DIV', {
					attrs: {
						id: 'bx-lm-box-group-content'
					},
					props: {
						className: 'bx-finder-box-tab-content bx-lm-box-tab-content-sonetgroup' + (!BX.YNSIRSocNetLogDestination.obLastEnable[name] && BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[name] ? ' bx-finder-box-tab-content-selected' : '')
					}
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obDepartmentEnable[name]
				? BX.create('DIV', {
					props: {
						className: 'bx-finder-box-tab-content bx-lm-box-tab-content-department' + (!BX.YNSIRSocNetLogDestination.obLastEnable[name] && !BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[name] && BX.YNSIRSocNetLogDestination.obDepartmentEnable[name] ? ' bx-finder-box-tab-content-selected' : '')
					}
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obAllowSearchEmailUsers[name]
				? BX.create('DIV', {
					attrs: {
						id: 'bx-lm-box-email-content'
					},
					props: {
						className: 'bx-finder-box-tab-content bx-lm-box-tab-content-email'
					}
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obAllowSearchCrmEmailUsers[name]
				? BX.create('DIV', {
					attrs: {
						id: 'bx-lm-box-crmemail-content'
					},
					props: {
						className: 'bx-finder-box-tab-content bx-lm-box-tab-content-crmemail'
					}
				})
				: null
		),
		(
			BX.YNSIRSocNetLogDestination.obShowSearchInput[name]
				? BX.create('DIV', {
					attrs: {
						id: 'destSearchTabContent_' + name
					},
					props: {
						className: 'bx-finder-box-tab-content bx-lm-box-tab-content-search'
					}
				})
				: null
		)
	];

	if (typeof BX.YNSIRSocNetLogDestination.obCustomTabs[name] != 'undefined')
	{
		for (i=0; i < BX.YNSIRSocNetLogDestination.obCustomTabs[name].length; i++)
		{
			contents.push(
				BX.create('DIV', {
					attrs: {
						id: 'dest' + BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].id + 'TabContent_' + name
					},
					props: {
						className: 'bx-finder-box-tab-content bx-lm-box-tab-content-' + BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].id
					}
				})
			);
		}
	}

	return BX.create('DIV', {
		style: {
			minWidth: '650px',
			paddingBottom: '8px'
		},
		props: {
			className: 'bx-finder-box bx-finder-box-vertical bx-lm-box ' + BX.YNSIRSocNetLogDestination.obWindowClass[name]
		},
		children: [
			(
				!BX.YNSIRSocNetLogDestination.obLastEnable[name]
				&& !BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[name]
				&& !BX.YNSIRSocNetLogDestination.obDepartmentEnable[name]
					? null
					: BX.create('DIV', {
						props: {
							className: 'bx-finder-box-tabs'
						},
						children: tabs
					})
			),
			BX.create('DIV', {
				attrs: {
					id: 'bx-lm-box-last-content'
				},
				props: {
					className: 'bx-finder-box-tabs-content bx-finder-box-tabs-content-window'
				},
				children: [
					BX.create('TABLE', {
						props: {
							className: 'bx-finder-box-tabs-content-table'
						},
						children: [
							BX.create('TR', {
								children: [
									BX.create('TD', {
										props: {
											className: 'bx-finder-box-tabs-content-cell'
										},
										children: contents
									})
								]
							})
						]
					})
				]
			}),
			(!!BX.YNSIRSocNetLogDestination.obUseContainer[name] ? BX.YNSIRSocNetLogDestination.getSearchWaiter() : null)
		]
	});
};

BX.YNSIRSocNetLogDestination.getSearchContent = function(items, name, params)
{
	return BX.create('DIV', {
		props: {
			className: 'bx-finder-box bx-finder-box-vertical bx-lm-box ' + BX.YNSIRSocNetLogDestination.obWindowClass[name]
		},
		style: {
			minWidth: '450px',
			paddingBottom: '8px'
		},
		children: [
			BX.create('DIV', {
				attrs : {
					id : 'bx-lm-box-search-tabs-content'
				},
				props: {
					className: 'bx-finder-box-tabs-content' + (!!BX.YNSIRSocNetLogDestination.obUseContainer[name] ? ' bx-finder-box-tabs-content-search' : '')
				},
				children: [
					BX.create('TABLE', {
						props: {
							className: 'bx-finder-box-tabs-content-table'
						},
						children: [
							BX.create('TR', {
								children: [
									BX.create('TD', {
										props: {
											className: 'bx-finder-box-tabs-content-cell'
										},
										children: [
											BX.create('DIV', {
												attrs : {
													id : 'bx-lm-box-search-content'
												},
												props: {
													className: 'bx-finder-box-tab-content bx-finder-box-tab-content-selected'
												},
												html: BX.YNSIRSocNetLogDestination.getItemLastHtml(items, true, name)
											})
										]
									})
								]
							})
						]
					})
				]
			}),
			(!!BX.YNSIRSocNetLogDestination.obUseContainer[name] ? null : BX.YNSIRSocNetLogDestination.getSearchWaiter())
		]
	});
};

BX.YNSIRSocNetLogDestination.getHidden = function(prefix, item, varName)
{
	if (
		typeof varName == 'undefined'
		|| !varName
	)
	{
		varName = 'SPERM';
	}

	var value = (
		typeof item.id != 'undefined'
		&& (
			item.id.indexOf("C_") === 0
			|| item.id.indexOf("CO_") === 0
			|| item.id.indexOf("L_") === 0
		)
			? item.desc
			: item.id
	);

	return [
		BX.create("input", {
			attrs : {
				type : 'hidden',
				name : varName + '[' + prefix + '][]',
				value : value
			}
		}),
		(
			prefix == 'UE'
			&& typeof item.params != 'undefined'
			&& typeof item.params.name != 'undefined'
				? BX.create("input", {
					attrs : {
						'type' : 'hidden',
						'name' : 'INVITED_USER_NAME[' + value + ']',
						'value' : item.params.name
					}
				})
				: null
		),
		(
			prefix == 'UE'
			&& typeof item.params != 'undefined'
			&& typeof item.params.lastName != 'undefined'
				? BX.create("input", {
					attrs : {
						'type' : 'hidden',
						'name' : 'INVITED_USER_LAST_NAME[' + value + ']',
						'value' : item.params.lastName
					}
				})
				: null
		),
		(
			prefix == 'UE'
			&& typeof item.id != 'undefined'
			&& (
				item.id.indexOf("C_") === 0
				|| item.id.indexOf("CO_") === 0
				|| item.id.indexOf("L_") === 0
			)
				? BX.create("input", {
					attrs : {
						'type' : 'hidden',
						'name' : 'INVITED_USER_CRM_ENTITY[' + value + ']',
						'value' : item.id
					}
				})
				: null
		),
		(
			prefix == 'UE'
			&& typeof item.params != 'undefined'
			&& typeof item.params.createCrmContact != 'undefined'
			&& !!item.params.createCrmContact
				? BX.create("input", {
					attrs : {
						'type' : 'hidden',
						'name' : 'INVITED_USER_CREATE_CRM_CONTACT[' + value + ']',
						'value' : 'Y'
					}
				})
				: null
		)
	];
};

BX.YNSIRSocNetLogDestination.getSearchWaiter = function()
{
	return BX.create('DIV', {
		attrs : {
			id : 'bx-lm-box-search-waiter'
		},
		props: {
			className: 'bx-finder-box-search-waiter'
		},
		style: {
			height: '0px'
		},
		children: [
			BX.create('IMG', {
				props: {
					className: 'bx-finder-box-search-waiter-background'
				},
				attrs: {
					src: '/bitrix/js/main/core/images/waiter-white.gif'
				}
			}),
			BX.create('DIV', {
				props: {
					className: 'bx-finder-box-search-waiter-text'
				},
				text: BX.message('LM_POPUP_WAITER_TEXT')
			})
		]
	})
};


BX.YNSIRSocNetLogDestination.openDialog = function(name, params)
{
	if(!name)
	{
		name = 'lm';
	}

	if (!params)
	{
		params = {};
	}

	if (
		typeof params.bByFocusEvent != 'undefined'
		&& params.bByFocusEvent
	)
	{
		BX.YNSIRSocNetLogDestination.bByFocusEvent = true;
	}

	if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
	{
		BX.YNSIRSocNetLogDestination.popupSearchWindow.close();
	}

	if (BX.YNSIRSocNetLogDestination.popupWindow != null)
	{
		if (!BX.YNSIRSocNetLogDestination.bByFocusEvent)
		{
			BX.YNSIRSocNetLogDestination.popupWindow.close();
		}
		return false;
	}

	if (
		typeof params.bByFocusEvent == 'undefined'
		|| !params.bByFocusEvent
	)
	{
		BX.YNSIRSocNetLogDestination.bByFocusEvent = false;
	}

	if (!!BX.YNSIRSocNetLogDestination.obUseContainer[name])
	{
		if (!BX.YNSIRSocNetLogDestination.openContainer(name))
		{
			return false;
		}

		BX.cleanNode(BX('BXYNSIRSocNetLogDestinationContainerContent'));
		BX('BXYNSIRSocNetLogDestinationContainerContent').appendChild(BX.YNSIRSocNetLogDestination.getDialogContent(name));

		if (!!BX.YNSIRSocNetLogDestination.obShowSearchInput[name])
		{
			for (var itemId in BX.YNSIRSocNetLogDestination.obItemsSelected[name])
			{
				var type = BX.YNSIRSocNetLogDestination.obItemsSelected[name][itemId];
				BX.YNSIRSocNetLogDestination.runSelectCallback(itemId, type, name, false, 'init');
			}
		}

		BX.YNSIRSocNetLogDestination.containerWindow.setAngle({});
		if(BX.type.isElementNode(params.bindNode))
		{
			BX.YNSIRSocNetLogDestination.containerWindow.setBindElement(params.bindNode);
		}
		BX.YNSIRSocNetLogDestination.containerWindow.show();
	}
	else
	{
		BX.YNSIRSocNetLogDestination.popupWindow = new BX.PopupWindow('BXYNSIRSocNetLogDestination', params.bindNode || BX.YNSIRSocNetLogDestination.obElementBindMainPopup[name].node, {
			autoHide: true,
			zIndex: 1200,
			className: 'bx-finder-popup bx-finder-v2',
			offsetLeft: parseInt(BX.YNSIRSocNetLogDestination.obElementBindMainPopup[name].offsetLeft),
			offsetTop: parseInt(BX.YNSIRSocNetLogDestination.obElementBindMainPopup[name].offsetTop),
			bindOptions: BX.YNSIRSocNetLogDestination.obBindOptions[name],
			closeByEsc: true,
			closeIcon: BX.YNSIRSocNetLogDestination.obWindowCloseIcon[name] ? {'top': '12px', 'right': '15px'} : false,
			lightShadow: true,
			events: {
				onPopupShow : function() {
					if (
						BX.YNSIRSocNetLogDestination.sendEvent
						&& BX.YNSIRSocNetLogDestination.obCallback[name]
						&& BX.YNSIRSocNetLogDestination.obCallback[name].openDialog
					)
					{
						BX.YNSIRSocNetLogDestination.obCallback[name].openDialog(name);
					}

					if (
						BX.YNSIRSocNetLogDestination.inviteEmailUserWindow
						&& BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.isShown()
					)
					{
						BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.close();
					}
				},
				onPopupClose : function(event) {
					this.destroy();
				},
				onPopupDestroy : BX.proxy(function() {
					BX.YNSIRSocNetLogDestination.popupWindow = null;
					if (
						BX.YNSIRSocNetLogDestination.sendEvent
						&& BX.YNSIRSocNetLogDestination.obCallback[name]
						&& BX.YNSIRSocNetLogDestination.obCallback[name].closeDialog
					)
					{
						BX.YNSIRSocNetLogDestination.obCallback[name].closeDialog(name);
					}
				}, this)
			},
			content: BX.YNSIRSocNetLogDestination.getDialogContent(name)
		});

		BX.YNSIRSocNetLogDestination.popupWindow.setAngle({});
		BX.YNSIRSocNetLogDestination.popupWindow.show();
	}

	if (BX.YNSIRSocNetLogDestination.obLastEnable[name])
	{
		BX.YNSIRSocNetLogDestination.initResultNavigation(name, 'last', BX.YNSIRSocNetLogDestination.obItemsLast[name]);
		BX.YNSIRSocNetLogDestination.obTabSelected[name] = 'last';
	}

	if (
		!BX.YNSIRSocNetLogDestination.obLastEnable[name]
		&& !BX.YNSIRSocNetLogDestination.obSonetgroupsEnable[name]
		&& BX.YNSIRSocNetLogDestination.obDepartmentEnable[name]
		&& BX('destDepartmentTab_'+name)
	)
	{
		BX.YNSIRSocNetLogDestination.SwitchTab(name, BX('destDepartmentTab_'+name), 'department');
		BX.YNSIRSocNetLogDestination.popupWindow.adjustPosition();
	}
};

BX.YNSIRSocNetLogDestination.search = function(text, sendAjax, name, nameTemplate, params)
{
	if(!name)
		name = 'lm';

	if (!params)
		params = {};

	if (
		typeof nameTemplate == 'undefined'
		|| nameTemplate.length <= 0
	)
	{
		nameTemplate = BX.YNSIRSocNetLogDestination.obUserNameTemplate[name];
	}

	sendAjax = (sendAjax != false);

	if (BX.YNSIRSocNetLogDestination.extranetUser)
	{
		sendAjax = false;
	}

	BX.YNSIRSocNetLogDestination.obSearchFirstElement = null;
	BX.YNSIRSocNetLogDestination.obCurrentElement.search = null;
	BX.YNSIRSocNetLogDestination.obResult.search = [];
	BX.YNSIRSocNetLogDestination.obCursorPosition.search = {
		group: 0,
		row: 0,
		column: 0
	};

	text = BX.util.trim(text);

	if (text.length <= 0)
	{
		clearTimeout(BX.YNSIRSocNetLogDestination.searchTimeout);
		if(BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
		{
			BX.YNSIRSocNetLogDestination.popupSearchWindow.close();
		}
		return false;
	}
	else
	{
		var items = {
			'groups': {}, 'users': {}, 'crmemails': {}, 'sonetgroups': {}, 'department': {},
			'contacts': {}, 'companies': {}, 'leads': {}, 'deals': {},'candidate': {}, 'joborder': {}
		};
		var count = 0;

		var resultGroupIndex = 0;
		var resultRowIndex = 0;
		var resultColumnIndex = 0;
		var bNewGroup = null;
		var storedItem = false;
		var bSkip = false;

		var partsItem = [];
		var bFound = false;
		var bPartFound = false;
		var partsSearchText = null;
		var arSearchStringAlternatives = [text];
		var searchString = null;

		var arTmp = [];
		var tmpVal = false;

		var key = null;
		var i = null;
		var k = null;

		if (sendAjax) // before AJAX request
		{
			BX.YNSIRSocNetLogDestination.abortSearchRequest();

			var obSearch = { searchString: text };

			if (!!BX.YNSIRSocNetLogDestination.obUseClientDatabase[name])
			{
				BX.onCustomEvent('findEntityByName', [
					BX.YNSIRSocNetLogDestination,
					obSearch,
					{ },
					BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name]
				]); // get result from the clientDb
			}

			if (obSearch.searchString != text) // if text was converted to another charset
			{
				arSearchStringAlternatives.push(obSearch.searchString);
			}
			BX.YNSIRSocNetLogDestination.bResultMoved.search = false;
		}
		else // from AJAX results
		{
			if (
				typeof params != 'undefined'
				&& typeof params.textAjax != 'undefined'
				&& params.textAjax != text
			)
			{
				arSearchStringAlternatives.push(params.textAjax);
			}

			// syncronize local DB
			if (
				!BX.YNSIRSocNetLogDestination.obUserSearchArea[name]
				&& !BX.YNSIRSocNetLogDestination.obAllowSearchNetworkUsers[name]
			)
			{
				for (key = 0; key < arSearchStringAlternatives.length; key++)
				{
					searchString = arSearchStringAlternatives[key].toLowerCase();
					if (
						searchString.length > 1
						&& typeof BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString] != 'undefined'
						&& BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString].length > 0
					)
					{
						/* sync minus */
						BX.onCustomEvent('syncClientDb', [
							BX.YNSIRSocNetLogDestination,
							name,
							BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString],
							(
								typeof BX.YNSIRSocNetLogDestination.oAjaxUserSearchResult[name][searchString] != 'undefined'
									? BX.YNSIRSocNetLogDestination.oAjaxUserSearchResult[name][searchString]
									: {}
							)
						]);
					}
				}
			}
		}

		for (var group in items)
		{
			bNewGroup = true;
			arTmp = [];

			if (
				BX.YNSIRSocNetLogDestination.obDepartmentSelectDisable[name]
				&& group == 'department'
			)
			{
				continue;
			}

			for (key = 0; key < arSearchStringAlternatives.length; key++)
			{
				searchString = arSearchStringAlternatives[key].toLowerCase();
				if (
					group == 'users'
					&& sendAjax
					&& typeof BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString] != 'undefined'
					&& BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString].length > 0 // results from local DB
				)
				{
					for (i in BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString])
					{
						if (!BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString].hasOwnProperty(i))
						{
							continue;
						}

						if (
							!BX.YNSIRSocNetLogDestination.obAllowSearchSelf[name]
							&& BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString][i] == 'U' + BX.message('USER_ID')
						)
						{
							continue;
						}

						if (
							!BX.YNSIRSocNetLogDestination.obUserSearchArea[name]
							|| (
								BX.YNSIRSocNetLogDestination.obUserSearchArea[name] == 'E'
								&& BX.YNSIRSocNetLogDestination.obClientDbData.users[BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString][i]]['isExtranet'] == 'Y'
							)
							|| (
								BX.YNSIRSocNetLogDestination.obUserSearchArea[name] == 'I'
								&& BX.YNSIRSocNetLogDestination.obClientDbData.users[BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString][i]]['isExtranet'] != 'Y'
							)
						)
						{
							BX.YNSIRSocNetLogDestination.obItems[name][group][BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString][i]] = BX.YNSIRSocNetLogDestination.obClientDbData.users[BX.YNSIRSocNetLogDestination.oDbUserSearchResult[name][searchString][i]];
						}
					}
				}
			}

			for (i in BX.YNSIRSocNetLogDestination.obItems[name][group])
			{
				if (!BX.YNSIRSocNetLogDestination.obItems[name][group].hasOwnProperty(i))
				{
					continue;
				}

				if (BX.YNSIRSocNetLogDestination.obItemsSelected[name][i]) // if already in selected
				{
					continue;
				}

				for (key = 0; key < arSearchStringAlternatives.length; key++)
				{
					bFound = false;

					searchString = arSearchStringAlternatives[key];
					partsSearchText = searchString.toLowerCase().split(" ");
					partsItem = BX.YNSIRSocNetLogDestination.obItems[name][group][i].name.toLowerCase().split(" ");
					for (k in partsItem)
					{
						partsItem[k] = BX.util.htmlspecialcharsback(partsItem[k]).replace('"', '');
					}

					if (
						typeof BX.YNSIRSocNetLogDestination.obItems[name][group][i].email != 'undefined'
						&& BX.YNSIRSocNetLogDestination.obItems[name][group][i].email.length > 0
					)
					{
						partsItem.push(BX.YNSIRSocNetLogDestination.obItems[name][group][i].email.toLowerCase());
					}

					if (
						typeof BX.YNSIRSocNetLogDestination.obItems[name][group][i].login != 'undefined'
						&& BX.YNSIRSocNetLogDestination.obItems[name][group][i].login.length > 0
						&& partsSearchText.length <= 1
						&& searchString.length > 2
					)
					{
						partsItem.push(BX.YNSIRSocNetLogDestination.obItems[name][group][i].login.toLowerCase());
					}

					BX.onCustomEvent(window, 'YNSIRSocNetLogDestinationSearchFillItemParts', [group, BX.YNSIRSocNetLogDestination.obItems[name][group][i], partsItem]);

					if (partsSearchText.length <= 1)
					{
						for (k in partsItem)
						{
							if (searchString.toLowerCase().localeCompare(partsItem[k].substring(0, searchString.length), 'en-US', { sensitivity: 'base' }) === 0)
							{
								bFound = true;
								break;
							}
						}
					}
					else
					{
						bFound = true;

						for (var j in partsSearchText)
						{
							if (!partsSearchText.hasOwnProperty(j))
							{
								continue;
							}

							bPartFound = false;
							for (k in partsItem)
							{
								if (partsSearchText[j].toLowerCase().localeCompare(partsItem[k].substring(0, partsSearchText[j].length), 'en-US', { sensitivity: 'base' }) === 0)
								{
									bPartFound = true;
									break;
								}
							}

							if (!bPartFound)
							{
								bFound = false;
								break;
							}
						}

						if (!bFound)
						{
							continue;
						}
					}

					if (bFound)
					{
						break;
					}
				}

				if (!bFound)
				{
					continue;
				}

				if (bNewGroup)
				{
					if (typeof BX.YNSIRSocNetLogDestination.obResult.search[resultGroupIndex] != 'undefined')
					{
						resultGroupIndex++;
					}
					bNewGroup = false;
				}

				tmpVal = {
					value: i
				};

				if (typeof BX.YNSIRSocNetLogDestination.obDestSort[name][i] != 'undefined')
				{
					tmpVal.sort = BX.YNSIRSocNetLogDestination.obDestSort[name][i];
				}

				if (BX.YNSIRSocNetLogDestination.obItems[name][group][i].isNetwork == 'Y')
				{
					tmpVal.isNetwork = true;
				}

				arTmp.push(tmpVal);
			}

			arTmp.sort(BX.YNSIRSocNetLogDestination.compareDestinations);

			var sort = 0;
			for (key = 0; key < arTmp.length; key++)
			{
				i = arTmp[key].value;
				items[group][i] = ++sort;

				bSkip = false;
				if (BX.YNSIRSocNetLogDestination.obItems[name][group][i]['id'] == 'UA')
				{
					bSkip = true;
				}
				else // calculate position
				{
					if (typeof BX.YNSIRSocNetLogDestination.obResult.search[resultGroupIndex] == 'undefined')
					{
						BX.YNSIRSocNetLogDestination.obResult.search[resultGroupIndex] = [];
						resultRowIndex = 0;
						resultColumnIndex = 0;
					}

					if (resultColumnIndex == 2)
					{
						resultRowIndex++;
						resultColumnIndex = 0;
					}

					if (typeof BX.YNSIRSocNetLogDestination.obResult.search[resultGroupIndex][resultRowIndex] == 'undefined')
					{
						BX.YNSIRSocNetLogDestination.obResult.search[resultGroupIndex][resultRowIndex] = [];
						resultColumnIndex = 0;
					}
				}

				var item = BX.clone(BX.YNSIRSocNetLogDestination.obItems[name][group][i]);

				if (bSkip)
				{
					storedItem = item;
				}

				item.type = group;
				if (!bSkip)
				{
					if (storedItem) // add stored item / UA
					{
						BX.YNSIRSocNetLogDestination.obResult.search[resultGroupIndex][resultRowIndex][resultColumnIndex] = storedItem;
						storedItem = false;
						resultColumnIndex++;
					}

					BX.YNSIRSocNetLogDestination.obResult.search[resultGroupIndex][resultRowIndex][resultColumnIndex] = item;
				}

				if (count <= 0)
				{
					BX.YNSIRSocNetLogDestination.obSearchFirstElement = item;
					BX.YNSIRSocNetLogDestination.obCurrentElement.search = item;
				}
				count++;

				resultColumnIndex++;
			}
		}

		if (sendAjax)
		{
			if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
			{
				BX.YNSIRSocNetLogDestination.popupSearchWindowContent.innerHTML = BX.YNSIRSocNetLogDestination.getItemLastHtml(items, true, name);
				BX.YNSIRSocNetLogDestination.popupSearchWindow.setBindElement(params.bindNode);
			}
			else
			{
				BX.YNSIRSocNetLogDestination.openSearch(items, name, params);
			}

			if (!!BX.YNSIRSocNetLogDestination.obUseContainer[name])
			{
				BX.YNSIRSocNetLogDestination.containerWindow.adjustPosition();
			}
			else
			{
				BX.YNSIRSocNetLogDestination.popupSearchWindow.adjustPosition();
			}
		}
		else
		{
			if (count <= 0)
			{
				if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
				{
					if (!BX.YNSIRSocNetLogDestination.obAllowSearchNetworkUsers[name])
					{
						BX.YNSIRSocNetLogDestination.popupSearchWindow.destroy();
					}
				}
				else if (
					BX.YNSIRSocNetLogDestination.obShowSearchInput[name]
					&& BX('bx-lm-box-waiter-content-text')
				)
				{
					BX('bx-lm-box-waiter-content-text').innerHTML = BX.message('LM_EMPTY_LIST');
				}

				if (BX.YNSIRSocNetLogDestination.obAllowAddSocNetGroup[name])
				{
					BX.YNSIRSocNetLogDestination.createSonetGroupTimeout = setTimeout(function()
					{
						if (BX.YNSIRSocNetLogDestination.createSocNetGroupWindow === null)
						{
							BX.YNSIRSocNetLogDestination.createSocNetGroupWindow = new BX.PopupWindow("invite-dialog-creategroup-popup", BX.YNSIRSocNetLogDestination.obElementBindSearchPopup[name].node, {
								offsetTop : 1,
								autoHide : true,
								content : BX.YNSIRSocNetLogDestination.createSocNetGroupContent(text),
								zIndex : 1200,
								buttons : BX.YNSIRSocNetLogDestination.createSocNetGroupButtons(text, name)
							});
						}
						else
						{
							BX.YNSIRSocNetLogDestination.createSocNetGroupWindow.setContent(BX.YNSIRSocNetLogDestination.createSocNetGroupContent(text));
							BX.YNSIRSocNetLogDestination.createSocNetGroupWindow.setButtons(BX.YNSIRSocNetLogDestination.createSocNetGroupButtons(text, name));
						}

						if (BX.YNSIRSocNetLogDestination.createSocNetGroupWindow.popupContainer.style.display != "block")
						{
							BX.YNSIRSocNetLogDestination.createSocNetGroupWindow.show();
						}

					}, 1000);
				}
			}
			else
			{
				if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
				{
					BX.YNSIRSocNetLogDestination.popupSearchWindowContent.innerHTML = BX.YNSIRSocNetLogDestination.getItemLastHtml(items, true, name);
				}
				else
				{
					BX.YNSIRSocNetLogDestination.openSearch(items, name, params);
				}

				if (!!BX.YNSIRSocNetLogDestination.obUseContainer[name])
				{
					BX.YNSIRSocNetLogDestination.containerWindow.adjustPosition();
				}
				else
				{
					BX.YNSIRSocNetLogDestination.popupSearchWindow.adjustPosition();
				}
			}

			BX.YNSIRSocNetLogDestination.obEmptySearchResult[name] = (count <= 0);
		}

		clearTimeout(BX.YNSIRSocNetLogDestination.searchTimeout);

		if (sendAjax && text.toLowerCase() != '')
		{
			BX.YNSIRSocNetLogDestination.showSearchWaiter(name);

			BX.YNSIRSocNetLogDestination.searchTimeout = setTimeout(function()
			{
				var ajaxData = {
					LD_SEARCH : 'Y',
					USER_SEARCH : BX.YNSIRSocNetLogDestination.obAllowUserSearch[name] ? 'Y' : 'N',
					CRM_SEARCH : BX.YNSIRSocNetLogDestination.obCrmFeed[name] ? 'Y' : 'N',
					CRM_SEARCH_TYPES : BX.YNSIRSocNetLogDestination.obCrmTypes[name],
					EXTRANET_SEARCH : BX.util.in_array(BX.YNSIRSocNetLogDestination.obUserSearchArea[name], ['I', 'E']) ? BX.YNSIRSocNetLogDestination.obUserSearchArea[name] : 'N',
					SEARCH : text.toLowerCase(),
					SEARCH_CONVERTED : (
						BX.message('LANGUAGE_ID') == 'ru'
						&& BX.correctText
							? BX.correctText(text.toLowerCase())
							: ''
					),
					sessid: BX.bitrix_sessid(),
					nt: (typeof nameTemplate != 'undefined' && nameTemplate.length > 0 ? nameTemplate : ''),
					DEPARTMENT_ID: (parseInt(BX.YNSIRSocNetLogDestination.obSiteDepartmentID[name]) > 0 ? parseInt(BX.YNSIRSocNetLogDestination.obSiteDepartmentID[name]) : 0),
					EMAIL_USERS : (BX.YNSIRSocNetLogDestination.obAllowSearchEmailUsers[name] ? 'Y' : 'N'),
					CRMEMAIL : (BX.YNSIRSocNetLogDestination.obAllowSearchCrmEmailUsers[name] ? 'Y' : 'N'),
					CRMCONTACTEMAIL : (BX.YNSIRSocNetLogDestination.obAllowAddCrmContact[name] ? 'Y' : 'N'),
					NETWORK_SEARCH : (BX.YNSIRSocNetLogDestination.obAllowSearchNetworkUsers[name] ? 'Y' : 'N'),
					ADDITIONAL_SEARCH : 'N',
					SELF : (BX.YNSIRSocNetLogDestination.obAllowSearchSelf[name] ? 'Y' : 'N'),
					SEARCH_SONET_GROUPS : (BX.YNSIRSocNetLogDestination.obAllowSonetGroupsAjaxSearch[name] ? 'Y' : 'N'),
					SEARCH_SONET_FEATUES : BX.YNSIRSocNetLogDestination.obAllowSonetGroupsAjaxSearchFeatures[name],
					SITE_ID : BX.message('SITE_ID')
				};
				BX.YNSIRSocNetLogDestination.oXHR = BX.ajax({
					url: BX.YNSIRSocNetLogDestination.obPathToAjax[name],
					method: 'POST',
					dataType: 'json',
					data: ajaxData,
					onsuccess: function(data)
					{
						BX.YNSIRSocNetLogDestination.hideSearchWaiter(name);

						if (data)
						{
							/* sync plus */
							var textAjax = (
								typeof data.SEARCH != 'undefined'
									? data.SEARCH
									: text
							);

							var finderData = BX.clone(data);

							if (
								typeof data.USERS != 'undefined'
								&& Object.keys(finderData.USERS).length > 0
							)
							{
								for (i in finderData.USERS)
								{
									if (
										finderData.USERS.hasOwnProperty(i)
										&& (
											typeof finderData.USERS[i].email != 'undefined'
											|| (
												typeof finderData.USERS[i].active != 'undefined'
												&& finderData.USERS[i].active == 'N'
										) || (
											typeof finderData.USERS[i].isNetwork != 'undefined'
											&& finderData.USERS[i].isNetwork == 'Y'
											)
										)
									)
									{
										delete finderData.USERS[i];
									}
								}
							}

							if (BX.YNSIRSocNetLogDestination.obUseClientDatabase[name])
							{
								BX.onCustomEvent(BX.YNSIRSocNetLogDestination, 'onFinderAjaxSuccess', [ finderData.USERS, BX.YNSIRSocNetLogDestination ]);
							}

							if (!BX.YNSIRSocNetLogDestination.bResultMoved.search)
							{
								if (
									!BX.YNSIRSocNetLogDestination.oAjaxUserSearchResult[name]
									|| !BX.YNSIRSocNetLogDestination.oAjaxUserSearchResult[name][textAjax.toLowerCase()]
								)
								{
									BX.YNSIRSocNetLogDestination.oAjaxUserSearchResult[name] = {};
									BX.YNSIRSocNetLogDestination.oAjaxUserSearchResult[name][textAjax.toLowerCase()] = [];
								}

								if (typeof data.USERS != 'undefined')
								{
									if (Object.keys(data.USERS).length > 0)
									{
										for (i in data.USERS)
										{
											if (data.USERS.hasOwnProperty(i))
											{
												bFound = true;
												BX.YNSIRSocNetLogDestination.oAjaxUserSearchResult[name][textAjax.toLowerCase()].push(i);
												BX.YNSIRSocNetLogDestination.obItems[name].users[i] = data.USERS[i];
											}
										}
									}
									if (
										typeof data.CRM_EMAILS != 'undefined'
										&& Object.keys(data.CRM_EMAILS).length > 0
									)
									{
										for (i in data.CRM_EMAILS)
										{
											if (data.CRM_EMAILS.hasOwnProperty(i))
											{
												bFound = true;
//												BX.YNSIRSocNetLogDestination.oAjaxCrmEmailSearchResult[name][textAjax.toLowerCase()].push(i);
												BX.YNSIRSocNetLogDestination.obItems[name].crmemails[i] = data.CRM_EMAILS[i];
											}
										}
									}

									if (
										!bFound
										&& BX.YNSIRSocNetLogDestination.obAllowAddUser[name]
									)
									{
										var obUserEmail = BX.YNSIRSocNetLogDestination.checkEmail(text.trim());

										if (
											obUserEmail !== false
											&& obUserEmail.email.length > 0
										)
										{
											BX.YNSIRSocNetLogDestination.openInviteEmailUserDialog(obUserEmail, name, BX.YNSIRSocNetLogDestination.obAllowAddCrmContact[name]);
										}
									}
								}

								if (typeof data.SONET_GROUPS != 'undefined')
								{
									if (Object.keys(data.SONET_GROUPS).length > 0)
									{
										for (i in data.SONET_GROUPS)
										{
											if (data.SONET_GROUPS.hasOwnProperty(i))
											{
												bFound = true;
												BX.YNSIRSocNetLogDestination.obItems[name].sonetgroups[i] = data.SONET_GROUPS[i];
											}
										}
									}
								}

								if (BX.YNSIRSocNetLogDestination.obCrmFeed[name])
								{
									var types = {
										'contacts': 'CONTACTS',
										'companies': 'COMPANIES',
										'leads': 'LEADS',
										'deals': 'DEALS',
										'candidate': 'CANDIDATE',
										'joborder': 'JOBORDER'
									};
									for (type in types)
									{
										for (i in data[types[type]])
										{
											if (data[types[type]].hasOwnProperty(i))
											{
												bFound = true;
												if (!BX.YNSIRSocNetLogDestination.obItems[name][type][i])
												{
													BX.YNSIRSocNetLogDestination.obItems[name][type][i] = data[types[type]][i];
												}
											}
										}
									}
								}

								BX.YNSIRSocNetLogDestination.search(
									text,
									false,
									name,
									nameTemplate,
									{
										textAjax: textAjax
									}
								);
							}

							if (BX.YNSIRSocNetLogDestination.obAllowSearchNetworkUsers[name])
							{
								var contentArea = BX.findChildren(BX.YNSIRSocNetLogDestination.popupSearchWindowContent,
									{
										'className': 'bx-finder-groupbox-content'
									},
									true
								);
								var waiter = BX.findChildren(BX.YNSIRSocNetLogDestination.popupSearchWindowContent,
									{
										'className': 'bx-finder-box-search-waiter'
									},
									true
								);

								BX.YNSIRSocNetLogDestination.searchButton = BX.create('span', {
									props : {
										'className' : "bx-finder-box-button"
									},
									text: BX.message('LM_POPUP_SEARCH_NETWORK')
								});

								var foundUsers = BX.findChildren(contentArea[0], {tagName: 'a'}, true);
								if (!foundUsers || foundUsers.length <= 0)
								{
									contentArea[0].innerHTML = '';
								}
								contentArea[0].appendChild(BX.YNSIRSocNetLogDestination.searchButton);
								BX.bind(BX.YNSIRSocNetLogDestination.searchButton, 'click', function()
								{
									BX.YNSIRSocNetLogDestination.showSearchWaiter(name);
									BX.YNSIRSocNetLogDestination.searchNetwork(text, name, nameTemplate, finderData, textAjax, ajaxData);
								});
							}
						}
					},
					onfailure: function(data)
					{
						BX.YNSIRSocNetLogDestination.hideSearchWaiter(name);
					}
				});
			}, 1000);
		}
	}
};

BX.YNSIRSocNetLogDestination.searchNetwork = function(text, name, nameTemplate, finderData, textAjax, ajaxData)
{
	ajaxData['ADDITIONAL_SEARCH'] = 'Y';
	BX.YNSIRSocNetLogDestination.oXHR = BX.ajax({
		url: BX.YNSIRSocNetLogDestination.obPathToAjax[name],
		method: 'POST',
		dataType: 'json',
		data: ajaxData,
		onsuccess: function(data)
		{
			BX.YNSIRSocNetLogDestination.hideSearchWaiter(name);
			if (data && typeof data.USERS != 'undefined')
			{
				for (var i in data.USERS)
				{
					if (data.USERS.hasOwnProperty(i))
					{
						bFound = true;
						BX.YNSIRSocNetLogDestination.oAjaxUserSearchResult[name][textAjax.toLowerCase()].push(i);
						BX.YNSIRSocNetLogDestination.obItems[name].users[i] = data.USERS[i];
					}
				}

				BX.YNSIRSocNetLogDestination.search(
					text,
					false,
					name,
					nameTemplate,
					{
						textAjax: textAjax
					}
				);
			}
			else
			{
				BX.YNSIRSocNetLogDestination.popupSearchWindow.destroy();
			}
		},
		onfailure: function(data)
		{
			BX.YNSIRSocNetLogDestination.hideSearchWaiter(name);
		}
	});};

BX.YNSIRSocNetLogDestination.openSearch = function(items, name, params)
{
	if (!name)
	{
		name = 'lm';
	}

	if (!params)
	{
		params = {};
	}

	if (BX.YNSIRSocNetLogDestination.popupWindow != null)
	{
		BX.YNSIRSocNetLogDestination.popupWindow.close();
	}

	if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
	{
		BX.YNSIRSocNetLogDestination.popupSearchWindow.close();
		return false;
	}

	if (!!BX.YNSIRSocNetLogDestination.obUseContainer[name])
	{
		var bCreateNode = false;
		if (BX('bx-lm-box-search-content'))
		{
			BX('bx-lm-box-search-content').innerHTML = BX.YNSIRSocNetLogDestination.getItemLastHtml(items, true, name);
		}
		else
		{
			bCreateNode = true;
			BX.cleanNode(BX('destSearchTabContent_' + name));
			BX('destSearchTabContent_' + name).appendChild(BX.YNSIRSocNetLogDestination.getSearchContent(items, name, params));
		}
		BX.YNSIRSocNetLogDestination.SwitchTab(name, BX('destSearchTab_' + name), 'search');
		BX.YNSIRSocNetLogDestination.containerWindow.setAngle({});

		if (bCreateNode)
		{
			BX.YNSIRSocNetLogDestination.oSearchWaiterContentHeight = BX.pos(BX('bx-lm-box-search-tabs-content')).height;
		}
	}
	else
	{
		BX.YNSIRSocNetLogDestination.popupSearchWindow = new BX.PopupWindow('BXYNSIRSocNetLogDestinationSearch', params.bindNode || BX.YNSIRSocNetLogDestination.obElementBindSearchPopup[name].node, {
			autoHide: true,
			zIndex: 1200,
			className: 'bx-finder-popup bx-finder-v2',
			offsetLeft: parseInt(BX.YNSIRSocNetLogDestination.obElementBindSearchPopup[name].offsetLeft),
			offsetTop: parseInt(BX.YNSIRSocNetLogDestination.obElementBindSearchPopup[name].offsetTop),
			bindOptions: BX.YNSIRSocNetLogDestination.obBindOptions[name],
			closeByEsc: true,
			lightShadow: true,
			events: {
				onPopupShow : function() {
					if (
						BX.YNSIRSocNetLogDestination.sendEvent
						&& BX.YNSIRSocNetLogDestination.obCallback[name]
						&& BX.YNSIRSocNetLogDestination.obCallback[name].openSearch
					)
					{
						BX.YNSIRSocNetLogDestination.obCallback[name].openSearch(name);
					}

					if (
						BX.YNSIRSocNetLogDestination.inviteEmailUserWindow
						&& BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.isShown()
					)
					{
						BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.close();
					}
				},
				onPopupClose : function() {
					this.destroy();
					if (
						BX.YNSIRSocNetLogDestination.sendEvent
						&& BX.YNSIRSocNetLogDestination.obCallback[name]
						&& BX.YNSIRSocNetLogDestination.obCallback[name].closeSearch
					)
					{
						BX.YNSIRSocNetLogDestination.obCallback[name].closeSearch(name);
					}
				},
				onPopupDestroy : BX.proxy(function() {
					BX.YNSIRSocNetLogDestination.popupSearchWindow = null;
					BX.YNSIRSocNetLogDestination.popupSearchWindowContent = null;

					if (
						BX.YNSIRSocNetLogDestination.sendEvent
						&& BX.YNSIRSocNetLogDestination.obCallback[name]
					)
					{
						if (BX.YNSIRSocNetLogDestination.obCallback[name].closeSearch)
						{
							BX.YNSIRSocNetLogDestination.obCallback[name].closeSearch(name);
						}
					}
				}, this)
			},
			content: BX.YNSIRSocNetLogDestination.getSearchContent(items, name, params)
		});

		BX.YNSIRSocNetLogDestination.popupSearchWindow.setAngle({});
		BX.YNSIRSocNetLogDestination.popupSearchWindow.show();

		BX.YNSIRSocNetLogDestination.oSearchWaiterContentHeight = BX.pos(BX('bx-lm-box-search-tabs-content')).height;
	}

	BX.YNSIRSocNetLogDestination.popupSearchWindowContent = BX('bx-lm-box-search-content');
};

BX.YNSIRSocNetLogDestination.drawItemsGroup = function(lastItems, groupCode, name, search, count, params)
{
	var itemsHtml = (
		typeof params.itemsHtml != 'undefined'
		&& params.itemsHtml
			? params.itemsHtml
			: ''
	);

	for (var i in lastItems[groupCode])
	{
		if (!BX.YNSIRSocNetLogDestination.obItems[name][groupCode][i])
		{
			continue;
		}

		itemsHtml += BX.YNSIRSocNetLogDestination.getHtmlByTemplate7(
			name,
			BX.YNSIRSocNetLogDestination.obItems[name][groupCode][i],
			{
				className: params.className + (
					groupCode == 'sonetgroups'
					&& typeof params.classNameExtranetGroup != 'undefined'
					&& typeof window['arExtranetGroupID'] != 'undefined'
					&& BX.util.in_array(BX.YNSIRSocNetLogDestination.obItems[name][groupCode][i].entityId, window['arExtranetGroupID'])
						? ' ' + params.classNameExtranetGroup
						: ''
				) + (
					typeof BX.YNSIRSocNetLogDestination.obItems[name][groupCode][i].active != 'undefined'
					&& BX.YNSIRSocNetLogDestination.obItems[name][groupCode][i].active == 'N'
						? ' bx-lm-element-inactive'
						: ''
				),
				descLessMode: (typeof params.descLessMode != 'undefined' && params.descLessMode ? true : false),
				itemType: groupCode,
				search: search,
				avatarLessMode: (typeof params.avatarLessMode != 'undefined' && params.avatarLessMode ? true : false),
				itemHover: (
//					search &&
					count <= 0
				)
			},
			(search ? 'search' : 'last')
		);

		count++;
	}

	if (
		itemsHtml != ''
		&& (
			typeof params.bHideGroup == 'undefined'
			|| !params.bHideGroup
		)
	)
	{
		itemsHtml = '<span class="bx-finder-groupbox ' + (typeof params.groupboxClassName != 'undefined' ? params.groupboxClassName : 'bx-lm-groupbox-last')+ '">' +
			'<span class="bx-finder-groupbox-name">' + params.title + ':</span>' +
			'<span class="bx-finder-groupbox-content">' + itemsHtml + '</span>' +
		'</span>';
	}

	return {
		html: itemsHtml,
		count: count
	};
};
/* vizualize lastItems - search result */

BX.YNSIRSocNetLogDestination.getItemLastHtml = function(lastItems, search, name)
{
	if(!name)
	{
		name = 'lm';
	}

	if (!lastItems)
	{
		lastItems = BX.YNSIRSocNetLogDestination.obItemsLast[name];
	}

	var html = '';
	var tmpHtml = null;
	var count = 0;
	var drawResult = null;
	var dialogGroup = null;

	for (var i = 0; i < BX.YNSIRSocNetLogDestination.arDialogGroups[name].length; i++)
	{
		dialogGroup = BX.YNSIRSocNetLogDestination.arDialogGroups[name][i];
		if (
			dialogGroup.bCrm
			&& BX.YNSIRSocNetLogDestination.obCrmFeed[name]
			|| (
				!dialogGroup.bCrm
				&& (
					search
					|| !BX.YNSIRSocNetLogDestination.obCrmFeed[name]
				)
			)
		)
		{
			drawResult = BX.YNSIRSocNetLogDestination.drawItemsGroup(
				lastItems,
				dialogGroup.groupCode,
				name,
				search,
				count,
				{
					itemsHtml: (tmpHtml ? tmpHtml : false),
					bHideGroup: (
						typeof dialogGroup.bHideGroup != 'undefined'
							? dialogGroup.bHideGroup
							: false
					),
					className: (
						typeof dialogGroup.className != 'undefined'
							? dialogGroup.className
							: false
					),
					classNameExtranetGroup: (
						typeof dialogGroup.classNameExtranetGroup != 'undefined'
							? dialogGroup.classNameExtranetGroup
							: false
					),
					groupboxClassName: (
						typeof dialogGroup.groupboxClassName != 'undefined'
							? dialogGroup.groupboxClassName
							: false
					),
					avatarLessMode: (
						typeof dialogGroup.avatarLessMode != 'undefined'
							? dialogGroup.avatarLessMode
							: false
					),
					descLessMode: (
						typeof dialogGroup.descLessMode != 'undefined'
							? dialogGroup.descLessMode
							: false
					),
					title: (
						typeof dialogGroup.title != 'undefined'
							? dialogGroup.title
							: ''
					)
				}
			);

			if (drawResult.html.length > 0)
			{
				if (
					dialogGroup.bHideGroup != 'undefined'
					&& dialogGroup.bHideGroup
				)
				{
					tmpHtml = drawResult.html;
				}
				else
				{
					html += drawResult.html;
					tmpHtml = null;
				}
			}
			count = drawResult.count;
		}
	}

	if (html.length <= 0)
	{
		html = '<span class="bx-finder-groupbox bx-lm-groupbox-search">'+
			'<span class="bx-finder-groupbox-content" id="bx-lm-box-waiter-content-text">' + BX.message(search ? 'LM_SEARCH_PLEASE_WAIT' : 'LM_EMPTY_LIST') + '</span>'+
		'</span>';
	}

	return html;
};

BX.YNSIRSocNetLogDestination.getItemDepartmentHtml = function(name, relation, categoryId, categoryOpened)
{
	if(!name)
	{
		name = 'lm';
	}

	categoryId = categoryId ? categoryId: false;
	categoryOpened = categoryOpened ? true: false;

	var bFirstRelation = false;
	var activeClass = null;

	if (
		typeof relation == 'undefined'
		|| !relation
	) // root
	{
		relation = BX.YNSIRSocNetLogDestination.obItems[name].departmentRelation;
		bFirstRelation = true;
	}

	var html = '';
	for (var i in relation)
	{
		if (relation[i].type == 'category')
		{
			var category = BX.YNSIRSocNetLogDestination.obItems[name].department[relation[i].id];
			activeClass = (
				BX.YNSIRSocNetLogDestination.obItemsSelected[name][relation[i].id]
					? BX.YNSIRSocNetLogDestination.obTemplateClassSelected['department']
					: ''
			);
			bFirstRelation = (bFirstRelation && category.id != 'EX');

			html += '<div class="bx-finder-company-department' + (bFirstRelation ? ' bx-finder-company-department-opened' : '') + '">\
				<a href="#' + category.id + '" class="bx-finder-company-department-inner" onclick="return BX.YNSIRSocNetLogDestination.OpenCompanyDepartment(\'' + name + '\', this.parentNode, \'' + category.entityId + '\')" hidefocus="true">\
					<div class="bx-finder-company-department-arrow"></div>\
					<div class="bx-finder-company-department-text">' + category.name + '</div>\
				</a>\
			</div>';

			html += '<div class="bx-finder-company-department-children'+(bFirstRelation? ' bx-finder-company-department-children-opened': '')+'">';
			if(
				!BX.YNSIRSocNetLogDestination.obDepartmentSelectDisable[name]
				&& !bFirstRelation
				&& category.id != 'EX'
			)
			{
				html += '<a class="bx-finder-company-department-check '+activeClass+' bx-finder-element" hidefocus="true" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, \'department\', \''+relation[i].id+'\', \'department\')" rel="'+relation[i].id+'" href="#'+relation[i].id+'">';
				html += '<span class="bx-finder-company-department-check-inner">\
						<div class="bx-finder-company-department-check-arrow"></div>\
						<div class="bx-finder-company-department-check-text" rel="'+category.name+': '+BX.message("LM_POPUP_CHECK_STRUCTURE")+'">'+BX.message("LM_POPUP_CHECK_STRUCTURE")+'</div>\
					</span>\
				</a>';
			}
			html += BX.YNSIRSocNetLogDestination.getItemDepartmentHtml(name, relation[i].items, category.entityId, bFirstRelation);
			html += '</div>';
		}
	}

	if (categoryId)
	{
		html += '<div class="bx-finder-company-department-employees" id="bx-lm-category-relation-'+categoryId+'">';
		userCount = 0;
		for (var i in relation)
		{
			if (relation[i].type == 'user')
			{
				var user = BX.YNSIRSocNetLogDestination.obItems[name].users[relation[i].id];
				if (user == null)
				{
					continue;
				}

				activeClass = (
					BX.YNSIRSocNetLogDestination.obItemsSelected[name][relation[i].id]
						? BX.YNSIRSocNetLogDestination.obTemplateClassSelected['department-user']
						: ''
				);
				html += '<a href="#'+user.id+'" class="bx-finder-company-department-employee '+activeClass+' bx-finder-element" rel="'+user.id+'" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, \'department-user\', \''+user.id+'\', \'users\')" hidefocus="true">\
					<div class="bx-finder-company-department-employee-info">\
						<div class="bx-finder-company-department-employee-name">'+user.name+'</div>\
						<div class="bx-finder-company-department-employee-position">'+user.desc+'</div>\
					</div>\
					<div style="'+(user.avatar? 'background:url(\''+user.avatar+'\') no-repeat center center; background-size: cover;': '')+'" class="bx-finder-company-department-employee-avatar"></div>\
				</a>';
				userCount++;
			}
		}
		if (userCount <= 0)
		{
			if (!BX.YNSIRSocNetLogDestination.obDepartmentLoad[name][categoryId])
			{
				html += '<div class="bx-finder-company-department-employees-loading">' + BX.message('LM_PLEASE_WAIT') + '</div>';
			}

			if (categoryOpened)
			{
				BX.YNSIRSocNetLogDestination.getDepartmentRelation(name, categoryId);
			}
		}
		html += '</div>';
	}

	return html;
};

BX.YNSIRSocNetLogDestination.getTabContentHtml = function(name, type, params)
{
	if(!name)
	{
		name = 'lm';
	}

	var html = '';
	var count = 0;
	var itemType = (!!params.itemType ? params.itemType : false);

	var className = null;

	if (type == 'email')
	{
		className = 'bx-lm-element-user bx-lm-element-email';
	}
	else if (type == 'crmemail')
	{
		className = 'bx-lm-element-user bx-lm-element-email bx-lm-element-crmemail';
	}

	if (itemType)
	{
		for (var i in BX.YNSIRSocNetLogDestination.obItems[name][itemType])
		{
			if (type == 'group')
			{
				className = 'bx-lm-element-sonetgroup' + (
					typeof window['arExtranetGroupID'] != 'undefined'
					&& BX.util.in_array(BX.YNSIRSocNetLogDestination.obItems[name].sonetgroups[i].entityId, window['arExtranetGroupID'])
						? ' bx-lm-element-extranet'
						: ''
				);
			}

			html += BX.YNSIRSocNetLogDestination.getHtmlByTemplate7(
				name,
				BX.YNSIRSocNetLogDestination.obItems[name][itemType][i],
				{
					className: className,
					descLessMode : true,
					itemType: itemType,
					itemHover: (count <= 0)
				},
				type
			);
			count++;
		}
	}

	return html;
};

BX.YNSIRSocNetLogDestination.getDepartmentRelation = function(name, departmentId)
{
	if (BX.YNSIRSocNetLogDestination.obDepartmentLoad[name][departmentId])
	{
		return false;
	}

	BX.ajax({
		url: BX.YNSIRSocNetLogDestination.obPathToAjax[name],
		method: 'POST',
		dataType: 'json',
		data: {
			LD_DEPARTMENT_RELATION : 'Y',
			DEPARTMENT_ID : departmentId,
			sessid: BX.bitrix_sessid(),
			nt: BX.YNSIRSocNetLogDestination.obUserNameTemplate[name]
		},
		onsuccess: function(data){
			BX.YNSIRSocNetLogDestination.obDepartmentLoad[name][departmentId] = true;
			var departmentItem = BX.util.object_search_key((departmentId == 'EX' ? departmentId : 'DR'+departmentId), BX.YNSIRSocNetLogDestination.obItems[name].departmentRelation);

			html = '';
			for(var i in data.USERS)
			{
				if (data.USERS.hasOwnProperty(i))
				{
					if (!BX.YNSIRSocNetLogDestination.obItems[name].users[i])
					{
						BX.YNSIRSocNetLogDestination.obItems[name].users[i] = data.USERS[i];
					}

					if (!departmentItem.items[i])
					{
						departmentItem.items[i] = {'id': i,	'type': 'user'};
						var activeClass = (
							BX.YNSIRSocNetLogDestination.obItemsSelected[name][data.USERS[i].id]
								? BX.YNSIRSocNetLogDestination.obTemplateClassSelected['department-user']
								: ''
						);
						html += '<a href="#'+data.USERS[i].id+'" class="bx-finder-company-department-employee '+activeClass+' bx-finder-element" rel="'+data.USERS[i].id+'" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, \'department-user\', \''+data.USERS[i].id+'\', \'users\')" hidefocus="true">\
							<div class="bx-finder-company-department-employee-info">\
								<div class="bx-finder-company-department-employee-name">'+data.USERS[i].name+'</div>\
								<div class="bx-finder-company-department-employee-position">'+data.USERS[i].desc+'</div>\
							</div>\
							<div style="'+(data.USERS[i].avatar? 'background:url(\''+data.USERS[i].avatar+'\') no-repeat center center; background-size: cover;': '')+'" class="bx-finder-company-department-employee-avatar"></div>\
						</a>';
					}
				}
			}
			BX('bx-lm-category-relation-'+departmentId).innerHTML = html;
			BX.YNSIRSocNetLogDestination.popupWindow.adjustPosition();
		},
		onfailure: function(data)	{}
	});
};

BX.YNSIRSocNetLogDestination.getHtmlByTemplate1 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = (
		BX.YNSIRSocNetLogDestination.obItemsSelected[name][item.id]
			? ' ' + BX.YNSIRSocNetLogDestination.obTemplateClassSelected[1]
			: ''
	);
	var hoverClass = params.itemHover? 'bx-finder-box-item-hover': '';
	return '<a id="' + name + '_' + item.id + '" class="' + BX.YNSIRSocNetLogDestination.obTemplateClass[1] + ' '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" hidefocus="true" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, 1, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" href="#'+item.id+'">\
		<div class="bx-finder-box-item-text">'+item.name+'</div>\
	</a>';
};

BX.YNSIRSocNetLogDestination.getHtmlByTemplate2 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = (
		BX.YNSIRSocNetLogDestination.obItemsSelected[name][item.id]
			? ' ' + BX.YNSIRSocNetLogDestination.obTemplateClassSelected[2]
			: ''
	);
	var hoverClass = params.itemHover? 'bx-finder-box-item-t2-hover': '';
	return '<a id="' + name + '_' + item.id + '" class="' + BX.YNSIRSocNetLogDestination.obTemplateClass[2] + ' '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" hidefocus="true" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, 2, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" href="#'+item.id+'">\
		<div class="bx-finder-box-item-t2-text">'+item.name+'</div>\
	</a>';
};

BX.YNSIRSocNetLogDestination.getHtmlByTemplate3 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = (
		BX.YNSIRSocNetLogDestination.obItemsSelected[name][item.id]
			? ' ' + BX.YNSIRSocNetLogDestination.obTemplateClassSelected[3]
			: ''
	);
	var hoverClass = params.itemHover? 'bx-finder-box-item-t3-hover': '';

	return '<a id="' + name + '_' + item.id + '" hidefocus="true" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, 3, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="' + BX.YNSIRSocNetLogDestination.obTemplateClass[3] + ' '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t3-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center; background-size: cover;"':'')+'></div>'+
		'<div class="bx-finder-box-item-t3-info">'+
			'<div class="bx-finder-box-item-t3-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t3-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
};

BX.YNSIRSocNetLogDestination.getHtmlByTemplate5 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = (
		BX.YNSIRSocNetLogDestination.obItemsSelected[name][item.id]
			? ' ' + BX.YNSIRSocNetLogDestination.obTemplateClassSelected[5]
			: ''
	);
	var hoverClass = params.itemHover? 'bx-finder-box-item-t5-hover': '';
	return '<a id="' + name + '_' + item.id + '" hidefocus="true" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, 5, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="' + BX.YNSIRSocNetLogDestination.obTemplateClass[5] + ' '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t5-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center; background-size: cover;"':'')+'></div>'+
		'<div class="bx-finder-box-item-t5-info">'+
			'<div class="bx-finder-box-item-t5-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t5-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
};

BX.YNSIRSocNetLogDestination.getHtmlByTemplate6 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = (
		BX.YNSIRSocNetLogDestination.obItemsSelected[name][item.id]
			? ' ' + BX.YNSIRSocNetLogDestination.obTemplateClassSelected[6]
			: ''
	);
	var hoverClass = params.itemHover? 'bx-finder-box-item-t6-hover': '';
	return '<a id="' + name + '_' + item.id + '" hidefocus="true" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, 6, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="' + BX.YNSIRSocNetLogDestination.obTemplateClass[6] + ' '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t6-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center; background-size: cover;"':'')+'></div>'+
		'<div class="bx-finder-box-item-t6-info">'+
			'<div class="bx-finder-box-item-t6-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t6-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
};

BX.YNSIRSocNetLogDestination.getHtmlByTemplate7 = function(name, item, params, type)
{
	if(!name)
	{
		name = 'lm';
	}

	if(!params)
	{
		params = {};
	}

	if(!type)
	{
		type = '';
	}

	var showDesc = BX.type.isNotEmptyString(item.desc);
	showDesc = params.descLessMode && params.descLessMode == true ? false : showDesc;
	showDesc = showDesc || item.showDesc;

	var itemClass = BX.YNSIRSocNetLogDestination.obTemplateClass[7] + " bx-finder-element";
	itemClass += BX.YNSIRSocNetLogDestination.obItemsSelected[name][item.id]
		? ' ' + BX.YNSIRSocNetLogDestination.obTemplateClassSelected[7]
		: '';
	itemClass += params.itemHover ? ' bx-finder-box-item-t7-hover': '';
	itemClass += showDesc ? ' bx-finder-box-item-t7-desc-mode': '';
	itemClass += params.className ? ' ' + params.className: '';
	itemClass += params.avatarLessMode && params.avatarLessMode == true ? ' bx-finder-box-item-t7-avatarless' : '';

	if (
		(typeof item.isExtranet != 'undefined' && item.isExtranet == 'Y')
		|| (typeof item.isNetwork != 'undefined' && item.isNetwork == 'Y')
	)
	{
		itemClass += ' bx-lm-element-extranet';
	}

	if (
		typeof item.isCrmEmail != 'undefined'
		&& item.isCrmEmail == 'Y'
	)
	{
		itemClass += ' bx-lm-element-crmemail';
	}

	if (
		typeof item.isEmail != 'undefined'
		&& item.isEmail == 'Y'
	)
	{
		itemClass += ' bx-lm-element-email';
	}

	if (
		typeof BX.YNSIRSocNetLogDestination.obShowVacations[name] != 'undefined'
		&& BX.YNSIRSocNetLogDestination.obShowVacations[name] === true
		&& typeof BX.YNSIRSocNetLogDestination.usersVacation[item.entityId] != 'undefined'
		&& BX.YNSIRSocNetLogDestination.usersVacation[item.entityId]
	)
	{
		itemClass += ' bx-lm-element-vacation';
	}

	var itemName = item.name + (
		typeof item.showEmail != 'undefined'
		&& item.showEmail == 'Y'
		&& typeof item.email != 'undefined'
		&& item.email.length > 0
			? ' (' + item.email + ')'
			: ''
	);

	return '<a id="' + name + '_' + type + '_' + item.id + '" hidefocus="true" onclick="return BX.YNSIRSocNetLogDestination.selectItem(\''+name+'\', this, 7, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="' + itemClass + '" href="#'+item.id+'">'+
		(
			item.avatar
				? '<div class="bx-finder-box-item-t7-avatar"><img bx-lm-item-id="' + item.id + '" bx-lm-item-type="' + params.itemType + '" class="bx-finder-box-item-t7-avatar-img" src="' + item.avatar + '" onerror="BX.onCustomEvent(\'removeClientDbObject\', [BX.YNSIRSocNetLogDestination, this.getAttribute(\'bx-lm-item-id\'), this.getAttribute(\'bx-lm-item-type\')]); BX.cleanNode(this, true);"><span class="bx-finder-box-item-avatar-status"></span></div>'
				: '<div class="bx-finder-box-item-t7-avatar"><span class="bx-finder-box-item-avatar-status"></span></div>'
		) +
		'<div class="bx-finder-box-item-t7-space"></div>' +
		'<div class="bx-finder-box-item-t7-info">'+
		'<div class="bx-finder-box-item-t7-name">'+itemName+'</div>'+
		(showDesc? '<div class="bx-finder-box-item-t7-desc">'+item.desc+'</div>': '')+
		'</div>'+
	'</a>';
};


BX.YNSIRSocNetLogDestination.SwitchTab = function(name, currentTab, type)
{
	var tabsContent = BX.findChildren(
		BX.findChild(
			currentTab.parentNode.parentNode,
			{ tagName : "td", className : "bx-finder-box-tabs-content-cell"},
			true
		),
		{ tagName : "div" }
	);

	if (!tabsContent)
	{
		return false;
	}

	var tabIndex = 0;
	var i = 0;
	var tabs = BX.findChildren(currentTab.parentNode, { tagName : "a" });
	for (i = 0; i < tabs.length; i++)
	{
		if (tabs[i] === currentTab)
		{
			BX.addClass(tabs[i], "bx-finder-box-tab-selected");
			tabIndex = i;
		}
		else
		{
			BX.removeClass(tabs[i], "bx-finder-box-tab-selected");
		}
	}

	for (i = 0; i < tabsContent.length; i++)
	{
		if (tabIndex === i)
		{
			if (type == 'last')
			{
				tabsContent[i].innerHTML = BX.YNSIRSocNetLogDestination.getItemLastHtml(false, false, name);
			}
			else if (type == 'department')
			{
				tabsContent[i].innerHTML = BX.YNSIRSocNetLogDestination.getItemDepartmentHtml(name);
			}
			else if (BX.util.in_array(type, ['group', 'email', 'crmemail']))
			{
				var itemType = null;

				if (type == 'email')
				{
					itemType = 'emails';
				}
				else if (type == 'crmemail')
				{
					itemType = 'crmemails';
				}
				else if (type == 'group')
				{
					itemType = 'sonetgroups';
				}

				tabsContent[i].innerHTML = BX.YNSIRSocNetLogDestination.getTabContentHtml(name, type, {
					itemType: itemType
				});
			}
			else if (typeof BX.YNSIRSocNetLogDestination.obCustomTabs[name] != 'undefined')
			{
				var customTab = null;
				for (var j=0;j<BX.YNSIRSocNetLogDestination.obCustomTabs[name].length;j++)
				{
					customTab = BX.YNSIRSocNetLogDestination.obCustomTabs[name][j];
					if (customTab.id == type)
					{
						if (typeof customTab.itemType != 'undefined')
						{
							tabsContent[i].innerHTML = BX.YNSIRSocNetLogDestination.getTabContentHtml(name, type, {
								itemType: customTab.itemType
							});
						}

						break;
					}
				}
			}

			BX.addClass(tabsContent[i], "bx-finder-box-tab-content-selected");
		}
		else
		{
			BX.removeClass(tabsContent[i], "bx-finder-box-tab-content-selected");
		}
	}

	var ob = {
		id: name
	};
	BX.onCustomEvent(window, 'BX.YNSIRSocNetLogDestination:onBeforeSwitchTabFocus', [ ob ]);
	setTimeout(function() {
		if (
			typeof ob.blockFocus == 'undefined'
			|| !ob.blockFocus
		)
		{
			BX.focus(BX.YNSIRSocNetLogDestination.obElementSearchInput[name]);
		}
	}, 1);

	if (type == 'last')
	{
		BX.YNSIRSocNetLogDestination.initResultNavigation(name, 'last', BX.YNSIRSocNetLogDestination.obItemsLast[name]);
	}
	else if (type == 'group')
	{
		BX.YNSIRSocNetLogDestination.initResultNavigation(name, type, {
			sonetgroups: BX.YNSIRSocNetLogDestination.obItems[name].sonetgroups
		});
	}
	else if (type == 'email')
	{
		BX.YNSIRSocNetLogDestination.initResultNavigation(name, type, {
			emails: BX.YNSIRSocNetLogDestination.obItems[name].emails
		});
	}
	else if (type == 'crmemail')
	{
		BX.YNSIRSocNetLogDestination.initResultNavigation(name, type, {
			crmemails: BX.YNSIRSocNetLogDestination.obItems[name].crmemails
		});
	}

	if (typeof BX.YNSIRSocNetLogDestination.obCustomTabs[name] != 'undefined')
	{
		for (i=0; i < BX.YNSIRSocNetLogDestination.obCustomTabs[name].length; i++)
		{
			if (BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].id == type)
			{
				var oParams = {};
				oParams[BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].itemType] = BX.YNSIRSocNetLogDestination.obItems[name][BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].itemType];

				BX.YNSIRSocNetLogDestination.initResultNavigation(name, BX.YNSIRSocNetLogDestination.obCustomTabs[name][i].id, oParams);
				break;
			}
		}
	}

	BX.YNSIRSocNetLogDestination.obTabSelected[name] = type;

	if (!!BX.YNSIRSocNetLogDestination.obUseContainer[name])
	{
		BX.YNSIRSocNetLogDestination.containerWindow.adjustPosition();
	}
	else
	{
		BX.YNSIRSocNetLogDestination.popupWindow.adjustPosition();
	}

	return false;
};

BX.YNSIRSocNetLogDestination.OpenCompanyDepartment = function(name, department, categoryId)
{
	if(!name)
		name = 'lm';

	BX.toggleClass(department, "bx-finder-company-department-opened");

	var nextDiv = BX.findNextSibling(department, { tagName : "div"} );
	if (BX.hasClass(nextDiv, "bx-finder-company-department-children"))
		BX.toggleClass(nextDiv, "bx-finder-company-department-children-opened");

	BX.YNSIRSocNetLogDestination.getDepartmentRelation(name, categoryId);

	return false;
};

Object.size = function(obj) {
	var size = 0, key;
	for (key in obj) {
		if (obj.hasOwnProperty(key)) size++;
	}
	return size;
};

BX.YNSIRSocNetLogDestination.selectItem = function(name, element, template, itemId, type, search)
{
	if(!name)
	{
		name = 'lm';
	}

	var ob = {
		id: name
	};
	BX.onCustomEvent(window, 'BX.YNSIRSocNetLogDestination:onBeforeSelectItemFocus', [ ob ]);
	setTimeout(function() {
		if (
			typeof ob.blockFocus == 'undefined'
			|| !ob.blockFocus
		)
		{
			BX.focus(BX.YNSIRSocNetLogDestination.obElementSearchInput[name]);
		}
	}, 1);

	if (BX.YNSIRSocNetLogDestination.obItemsSelected[name][itemId])
	{
		return BX.YNSIRSocNetLogDestination.unSelectItem(name, element, template, itemId, type, search);
	}

	BX.YNSIRSocNetLogDestination.obItemsSelected[name][itemId] = type;

	if (!BX.type.isArray(BX.YNSIRSocNetLogDestination.obItemsLast[name][type]))
	{
		BX.YNSIRSocNetLogDestination.obItemsLast[name][type] = {};
	}
	BX.YNSIRSocNetLogDestination.obItemsLast[name][type][itemId] = itemId;

	if (!(element == null || template == null))
	{
		BX.YNSIRSocNetLogDestination.changeItemClass(element, template, true);
	}

	BX.YNSIRSocNetLogDestination.runSelectCallback(itemId, type, name, search, 'select');

	if (search === true)
	{
		if (BX.YNSIRSocNetLogDestination.popupWindow != null)
		{
			BX.YNSIRSocNetLogDestination.popupWindow.close();
		}

		BX.YNSIRSocNetLogDestination.abortSearchRequest();

		if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
		{
			BX.YNSIRSocNetLogDestination.popupSearchWindow.close();
		}
	}
	else
	{
		if (BX.YNSIRSocNetLogDestination.popupWindow != null)
			BX.YNSIRSocNetLogDestination.popupWindow.adjustPosition();
		if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
			BX.YNSIRSocNetLogDestination.popupSearchWindow.adjustPosition();
	}

	var objSize = Object.size(BX.YNSIRSocNetLogDestination.obItemsLast[name][type]);
	var destLast = null;
	var i = 0;

	if(objSize > 5)
	{
		destLast = {};
		var ii = 0;
		var jj = objSize-5;

		for(i in BX.YNSIRSocNetLogDestination.obItemsLast[name][type])
		{
			if(ii >= jj)
				destLast[BX.YNSIRSocNetLogDestination.obItemsLast[name][type][i]] = BX.YNSIRSocNetLogDestination.obItemsLast[name][type][i];
			ii++;
		}
	}
	else
	{
		destLast = BX.YNSIRSocNetLogDestination.obItemsLast[name][type];
	}

	BX.userOptions.save('socialnetwork', 'log_destination', type, JSON.stringify(destLast));

	if (BX.util.in_array(type, ['contacts', 'companies', 'leads', 'deals','candidate','joborder']) && BX.YNSIRSocNetLogDestination.obCrmFeed[name])
	{
		var lastCrmItems = [itemId];
		for (i = 0; i < BX.YNSIRSocNetLogDestination.obItemsLast[name].crm.length && lastCrmItems.length < 20; i++)
		{
			if (BX.YNSIRSocNetLogDestination.obItemsLast[name].crm[i] != itemId)
			{
				lastCrmItems.push(BX.YNSIRSocNetLogDestination.obItemsLast[name].crm[i]);
			}
		}

		BX.YNSIRSocNetLogDestination.obItemsLast[name].crm = lastCrmItems;

		BX.userOptions.save('crm', 'log_destination', 'items', lastCrmItems);
	}

	return false;
};

BX.YNSIRSocNetLogDestination.unSelectItem = function(name, element, template, itemId, type, search)
{
	if(!name)
	{
		name = 'lm';
	}

	if (!BX.YNSIRSocNetLogDestination.obItemsSelected[name][itemId])
	{
		return false;
	}
	else
	{
		delete BX.YNSIRSocNetLogDestination.obItemsSelected[name][itemId];
	}

	BX.YNSIRSocNetLogDestination.changeItemClass(element, template, false);
	BX.YNSIRSocNetLogDestination.runUnSelectCallback(itemId, type, name, search);

	if (search === true)
	{
		if (BX.YNSIRSocNetLogDestination.popupWindow != null)
		{
			BX.YNSIRSocNetLogDestination.popupWindow.close();
		}

		if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
		{
			BX.YNSIRSocNetLogDestination.popupSearchWindow.close();
		}
	}
	else
	{
		if (BX.YNSIRSocNetLogDestination.popupWindow != null)
			BX.YNSIRSocNetLogDestination.popupWindow.adjustPosition();
		if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
			BX.YNSIRSocNetLogDestination.popupSearchWindow.adjustPosition();
	}

	return false;
};

BX.YNSIRSocNetLogDestination.runSelectCallback = function(itemId, type, name, search, state)
{
	if(!name)
	{
		name = 'lm';
	}

	if(!search)
	{
		search = false;
	}

	if(
		BX.YNSIRSocNetLogDestination.obCallback[name]
		&& BX.YNSIRSocNetLogDestination.obCallback[name].select
		&& BX.YNSIRSocNetLogDestination.obItems[name][type]
		&& BX.YNSIRSocNetLogDestination.obItems[name][type][itemId]
	)
	{
		BX.YNSIRSocNetLogDestination.obCallback[name].select(
			BX.YNSIRSocNetLogDestination.obItems[name][type][itemId],
			type,
			search,
			(BX.util.in_array(itemId, BX.YNSIRSocNetLogDestination.obItemsSelectedUndeleted[name])),
			name,
			state
		);
	}
};

BX.YNSIRSocNetLogDestination.runUnSelectCallback = function(itemId, type, name, search)
{
	if(!name)
		name = 'lm';

	if(!search)
		search = false;

	delete BX.YNSIRSocNetLogDestination.obItemsSelected[name][itemId];

	if (
		BX.YNSIRSocNetLogDestination.obCallback[name]
		&& BX.YNSIRSocNetLogDestination.obCallback[name].unSelect
		&& BX.YNSIRSocNetLogDestination.obItems[name][type]
		&& BX.YNSIRSocNetLogDestination.obItems[name][type][itemId]
	)
	{
		BX.YNSIRSocNetLogDestination.obCallback[name].unSelect(BX.YNSIRSocNetLogDestination.obItems[name][type][itemId], type, search, name);
	}
};

/* public function */
BX.YNSIRSocNetLogDestination.deleteItem = function(itemId, type, name)
{
	if(!name)
		name = 'lm';

	for (var tab in BX.YNSIRSocNetLogDestination.obResult)
	{
		if (BX.YNSIRSocNetLogDestination.obResult.hasOwnProperty(tab))
		{
			var elementId = name + '_' + tab + '_' + itemId;
			if (BX(elementId))
			{
				var itemTemplate = null;

				for (var template in BX.YNSIRSocNetLogDestination.obTemplateClassSelected)
				{
					if (
						BX.YNSIRSocNetLogDestination.obTemplateClassSelected.hasOwnProperty(template)
						&& BX.hasClass(BX(elementId), BX.YNSIRSocNetLogDestination.obTemplateClassSelected[template])
					)
					{
						itemTemplate = template;
						break;
					}
				}

				if (!!itemTemplate)
				{
					BX.YNSIRSocNetLogDestination.changeItemClass(BX(elementId), template, false);
				}
			}
		}
	}

	BX.YNSIRSocNetLogDestination.runUnSelectCallback(itemId, type, name);
};

BX.YNSIRSocNetLogDestination.deleteLastItem = function(name)
{
	if(!name)
		name = 'lm';

	var lastId = false;
	for (var itemId in BX.YNSIRSocNetLogDestination.obItemsSelected[name])
		lastId = itemId;

	if (lastId)
	{
		var type = BX.YNSIRSocNetLogDestination.obItemsSelected[name][lastId];
		BX.YNSIRSocNetLogDestination.runUnSelectCallback(lastId, type, name);
	}
};

BX.YNSIRSocNetLogDestination.initResultNavigation = function(name, type, obSource)
{
	BX.YNSIRSocNetLogDestination.obCurrentElement[type] = null;
	BX.YNSIRSocNetLogDestination.obResult[type] = [];
	BX.YNSIRSocNetLogDestination.obCursorPosition[type] = {
		group: 0,
		row: 0,
		column: 0
	};

	var itemCount = 0;
	var cntInGroup = null;
	var groupCode = null;
	var itemCode = null;
	var resultGroupIndex = -1;
	var resultRowIndex = 0;
	var resultColumnIndex = 0;
	var bSkipNewGroup = false;
	var item = null;
	var i = 0;

	for (i=0;i<BX.YNSIRSocNetLogDestination.arDialogGroups[name].length;i++)
	{
		groupCode = BX.YNSIRSocNetLogDestination.arDialogGroups[name][i].groupCode;
		if (groupCode == 'users')
		{
			if (type == 'email')
			{
				groupCode = 'emails'
			}
			else if (type == 'crmemails')
			{
				groupCode = 'crmemails'
			}
		}
		if (typeof obSource[groupCode] == 'undefined')
		{
			continue;
		}
		if (bSkipNewGroup)
		{
			bSkipNewGroup = false;
		}
		else
		{
			cntInGroup = 0;
		}

		for (itemCode in obSource[groupCode])
		{
			if (!BX.YNSIRSocNetLogDestination.obItems[name][groupCode][itemCode])
			{
				continue;
			}

			if (cntInGroup == 0)
			{
				if (groupCode == 'groups')
				{
					bSkipNewGroup = true;
				}
				resultGroupIndex++;
				BX.YNSIRSocNetLogDestination.obResult[type][resultGroupIndex] = [];
				resultRowIndex = 0;
				resultColumnIndex = 0;
			}

			if (resultColumnIndex == 2)
			{
				resultRowIndex++;
				resultColumnIndex = 0;
			}

			if (typeof BX.YNSIRSocNetLogDestination.obResult[type][resultGroupIndex][resultRowIndex] == 'undefined')
			{
				BX.YNSIRSocNetLogDestination.obResult[type][resultGroupIndex][resultRowIndex] = [];
			}

			item = {
				id: itemCode,
				type: groupCode
			};

			BX.YNSIRSocNetLogDestination.obResult[type][resultGroupIndex][resultRowIndex][resultColumnIndex] = item;

			if (itemCount <= 0)
			{
				BX.YNSIRSocNetLogDestination.obCurrentElement[type] = item;
			}

			resultColumnIndex++;
			cntInGroup++;
			itemCount++;
		}
	}
};

BX.YNSIRSocNetLogDestination.selectFirstSearchItem = function(name)
{
	if(!name)
		name = 'lm';
	var item = BX.YNSIRSocNetLogDestination.obSearchFirstElement;
	if (item != null)
	{
		BX.YNSIRSocNetLogDestination.selectItem(name, null, null, item.id, item.type, true);
		BX.YNSIRSocNetLogDestination.obSearchFirstElement = null;
	}
};

BX.YNSIRSocNetLogDestination.selectCurrentSearchItem = function(name)
{
	BX.YNSIRSocNetLogDestination.selectCurrentItem('search', name);
};

BX.YNSIRSocNetLogDestination.selectCurrentItem = function(type, name, params)
{
	if (
		BX.YNSIRSocNetLogDestination.popupSearchWindow == null
		&& BX.YNSIRSocNetLogDestination.popupWindow == null
		&& BX.YNSIRSocNetLogDestination.containerWindow == null
	)
	{
		return;
	}

	if(!name)
	{
		name = 'lm';
	}

	if (type == 'search')
	{
		clearTimeout(BX.YNSIRSocNetLogDestination.searchTimeout);
		BX.YNSIRSocNetLogDestination.abortSearchRequest();
	}

	var item = BX.YNSIRSocNetLogDestination.obCurrentElement[type];
	if (item != null)
	{
		var element = BX(name + '_' + type + '_' + item.id);
		var template = BX.YNSIRSocNetLogDestination.getTemplateByItemClass(element);
		BX.YNSIRSocNetLogDestination.selectItem(name, (element ? element : null), (template ? template : null), item.id, item.type, (item.type === 'search'));
		if (
			typeof params == 'undefined'
			|| typeof params.closeDialog == 'undefined'
			|| params.closeDialog
		)
		{
			BX.YNSIRSocNetLogDestination.obCurrentElement[type] = null;
			if (BX.YNSIRSocNetLogDestination.isOpenDialog())
			{
				BX.YNSIRSocNetLogDestination.closeDialog();
			}
			BX.YNSIRSocNetLogDestination.closeSearch();
		}
	}
};

BX.YNSIRSocNetLogDestination.moveCurrentSearchItem = function(name, direction)
{
	BX.YNSIRSocNetLogDestination.moveCurrentItem('search', name, direction)
};

BX.YNSIRSocNetLogDestination.moveCurrentItem = function(type, name, direction)
{
	if (
		BX.YNSIRSocNetLogDestination.popupSearchWindow == null
		&& BX.YNSIRSocNetLogDestination.popupWindow == null
		&& BX.YNSIRSocNetLogDestination.containerWindow == null
	)
	{
		return;
	}

	BX.YNSIRSocNetLogDestination.bResultMoved[type] = true;

	if (
		type == 'search'
		&& BX.YNSIRSocNetLogDestination.oXHR
	)
	{
		BX.YNSIRSocNetLogDestination.abortSearchRequest();
		BX.YNSIRSocNetLogDestination.hideSearchWaiter(name);
	}

	if (!BX.YNSIRSocNetLogDestination.obCursorPosition[type])
	{
		BX.YNSIRSocNetLogDestination.obCursorPosition[type] = {
			group: 0,
			row: 0,
			column: 0
		};
	}

	var bMoved = false;

	switch (direction)
	{
		case 'left':
			if (BX.YNSIRSocNetLogDestination.focusOnTabs)
			{
				BX.YNSIRSocNetLogDestination.moveCurrentTab(type, name, direction);
			}
			else if (BX.YNSIRSocNetLogDestination.obCursorPosition[type].column == 1)
			{
				if (typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row][BX.YNSIRSocNetLogDestination.obCursorPosition[type].column - 1] != 'undefined')
				{
					BX.YNSIRSocNetLogDestination.obCursorPosition[type].column--;
					bMoved = true;
				}
			}
			break;
		case 'right':
			if (BX.YNSIRSocNetLogDestination.focusOnTabs)
			{
				BX.YNSIRSocNetLogDestination.moveCurrentTab(type, name, direction);
			}
			else if (BX.YNSIRSocNetLogDestination.obCursorPosition[type].column == 0)
			{
				if (
					typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group] != 'undefined'
					&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row][BX.YNSIRSocNetLogDestination.obCursorPosition[type].column + 1] != 'undefined'
				)
				{
					BX.YNSIRSocNetLogDestination.obCursorPosition[type].column++;
					bMoved = true;
				}
			}
			break;
		case 'up':
			if (
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].row > 0
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row - 1] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row - 1][BX.YNSIRSocNetLogDestination.obCursorPosition[type].column] != 'undefined'
			)
			{
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].row--;
				bMoved = true;
			}
			else if (
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].row == 0
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group - 1] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group - 1][BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group - 1].length - 1] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group - 1][BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group - 1].length - 1][0] != 'undefined'
			)
			{
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].row = BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group - 1].length - 1;
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].column = 0;
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].group--;
				bMoved = true;
			}
			else if (
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].group == 0
				&& BX.YNSIRSocNetLogDestination.obCursorPosition[type].row == 0
				&& BX.util.in_array(type, BX.YNSIRSocNetLogDestination.obTabs[name])
			)
			{
//				BX.YNSIRSocNetLogDestination.focusOnTabs = true;
			}
			break;
		case 'down':
			if (BX.YNSIRSocNetLogDestination.focusOnTabs)
			{
//				BX.YNSIRSocNetLogDestination.focusOnTabs = false;
			}
			else if (
				typeof BX.YNSIRSocNetLogDestination.obResult[type] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row + 1] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row + 1][BX.YNSIRSocNetLogDestination.obCursorPosition[type].column] != 'undefined'
			)
			{
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].row++;
				bMoved = true;
			}
			else if (
				typeof BX.YNSIRSocNetLogDestination.obResult[type] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row + 1] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row + 1][0] != 'undefined'
			)
			{
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].column = 0;
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].row++;
				bMoved = true;
			}
			else if (
				typeof BX.YNSIRSocNetLogDestination.obResult[type] != 'undefined'
				&& BX.YNSIRSocNetLogDestination.obCursorPosition[type].row == (BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group].length - 1)
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group + 1] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group + 1][0] != 'undefined'
				&& typeof BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group + 1][0][0] != 'undefined'
			)
			{
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].group++;
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].row = 0;
				BX.YNSIRSocNetLogDestination.obCursorPosition[type].column = 0;
				bMoved = true;
			}
			break;
		default:
	}

	if (bMoved)
	{
		var oldId = BX.YNSIRSocNetLogDestination.obCurrentElement[type].id;
		BX.YNSIRSocNetLogDestination.obCurrentElement[type] = BX.YNSIRSocNetLogDestination.obResult[type][BX.YNSIRSocNetLogDestination.obCursorPosition[type].group][BX.YNSIRSocNetLogDestination.obCursorPosition[type].row][BX.YNSIRSocNetLogDestination.obCursorPosition[type].column];

		if (BX(name + '_' + type + '_' + oldId))
		{
			BX.YNSIRSocNetLogDestination.unhoverItem(BX(name + '_' + type + '_' + oldId));
		}

		var hoveredNode = BX(name + '_' + type + '_' + BX.YNSIRSocNetLogDestination.obCurrentElement[type].id);
		var containerNode = null;

		if (type == 'search')
		{
			containerNode = BX('bx-lm-box-search-tabs-content');
		}
		else if (type == 'last')
		{
			containerNode = BX('bx-lm-box-last-content');
		}
		else if (type == 'group')
		{
			containerNode = BX('bx-lm-box-group-content');
		}
		else if (type == 'email')
		{
			containerNode = BX('bx-lm-box-email-content');
		}
		else if (type == 'crmemail')
		{
			containerNode = BX('bx-lm-box-crmemail-content');
		}
		else if (BX('dest' + type + 'TabContent_' + name)) // custom tabs
		{
			containerNode = BX('dest' + type + 'TabContent_' + name);
		}

		if (
			hoveredNode
			&& containerNode
		)
		{
			var arPosContainer = BX.pos(containerNode);
			var arPosNode = BX.pos(hoveredNode);

			if (
				arPosNode.bottom > arPosContainer.bottom
				|| arPosNode.top < arPosContainer.top
			)
			{
				containerNode.scrollTop += (
					arPosNode.bottom > arPosContainer.bottom
						? (arPosNode.bottom - arPosContainer.bottom)
						: (arPosNode.top - arPosContainer.top)
				);
			}

			BX.YNSIRSocNetLogDestination.hoverItem(hoveredNode);
		}
	}
};

BX.YNSIRSocNetLogDestination.moveCurrentTab = function(type, name, direction)
{
	var obTypeToTab = {
		'last': 'destLastTab',
		'group': 'destGroupTab',
		'department': 'destDepartmentTab'
	};

	var curTabPos = BX.util.array_search(type, BX.YNSIRSocNetLogDestination.obTabs[name]);

	if (curTabPos >= 0)
	{
		if (direction == 'right')
		{
			curTabPos++;
		}
		else if (direction == 'left')
		{
			curTabPos--;
		}

		if (
			curTabPos <= (BX.YNSIRSocNetLogDestination.obTabs[name].length - 1)
			&& curTabPos >= 0
			&& typeof BX.YNSIRSocNetLogDestination.obTabs[name][curTabPos] != 'undefined'
		)
		{
			BX.YNSIRSocNetLogDestination.SwitchTab(
				name,
				BX(obTypeToTab[BX.YNSIRSocNetLogDestination.obTabs[name][curTabPos]] + '_' + name),
				BX.YNSIRSocNetLogDestination.obTabs[name][curTabPos]
			);
		}
	}
};

BX.YNSIRSocNetLogDestination.getItemHoverClassName = function(node)
{
	if (!node)
	{
		return false;
	}

	if (node.classList.contains('bx-finder-box-item-t1'))
	{
		return 'bx-finder-box-item-t1-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t2'))
	{
		return 'bx-finder-box-item-t2-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t3'))
	{
		return 'bx-finder-box-item-t3-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t4'))
	{
		return 'bx-finder-box-item-t4-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t5'))
	{
		return 'bx-finder-box-item-t5-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t6'))
	{
		return 'bx-finder-box-item-t6-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t7'))
	{
		return 'bx-finder-box-item-t7-hover';
	}

	return  false;
}

BX.YNSIRSocNetLogDestination.hoverItem = function(node)
{
	var hoverClassName = BX.YNSIRSocNetLogDestination.getItemHoverClassName(node);

	if (hoverClassName)
	{
		BX.addClass(
			node,
			hoverClassName
		);
	}
};

BX.YNSIRSocNetLogDestination.unhoverItem = function(node)
{
	var hoverClassName = BX.YNSIRSocNetLogDestination.getItemHoverClassName(node);

	if (hoverClassName)
	{
		BX.removeClass(
			node,
			hoverClassName
		);
	}
};

BX.YNSIRSocNetLogDestination.getSelectedCount = function(name)
{
	if(!name)
		name = 'lm';

	var count = 0;
	for (var i in BX.YNSIRSocNetLogDestination.obItemsSelected[name])
		count++;

	return count;
};

BX.YNSIRSocNetLogDestination.getSelected = function(name)
{
	if(!name)
		name = 'lm';
	return BX.YNSIRSocNetLogDestination.obItemsSelected[name];
};

BX.YNSIRSocNetLogDestination.isOpenDialog = function()
{
	return (BX.YNSIRSocNetLogDestination.popupWindow != null || BX.YNSIRSocNetLogDestination.containerWindow != null);
};

BX.YNSIRSocNetLogDestination.isOpenSearch = function()
{
	return (BX.YNSIRSocNetLogDestination.popupSearchWindow != null || BX.YNSIRSocNetLogDestination.containerWindow != null);
};

BX.YNSIRSocNetLogDestination.isOpenContainer = function()
{
	return (BX.YNSIRSocNetLogDestination.containerWindow != null);
};

BX.YNSIRSocNetLogDestination.closeDialog = function(silent)
{
	silent = (silent === true);
	if (BX.YNSIRSocNetLogDestination.popupWindow != null)
	{
		if (silent)
		{
			BX.YNSIRSocNetLogDestination.popupWindow.destroy();
		}
		else
		{
			BX.YNSIRSocNetLogDestination.popupWindow.close();
		}
	}
	else if (BX.YNSIRSocNetLogDestination.containerWindow != null)
	{
		if (silent)
		{
			BX.YNSIRSocNetLogDestination.containerWindow.destroy();
		}
		else
		{
			BX.YNSIRSocNetLogDestination.containerWindow.close();
		}
	}

	return true;
};

BX.YNSIRSocNetLogDestination.closeSearch = function()
{
	if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
	{
		BX.YNSIRSocNetLogDestination.popupSearchWindow.close();
	}
	else if (BX.YNSIRSocNetLogDestination.containerWindow != null)
	{
		BX.YNSIRSocNetLogDestination.containerWindow.close();
	}

	return true;
};

BX.YNSIRSocNetLogDestination.createSocNetGroupContent = function(text)
{
	return BX.create('div', {
		children: [
			BX.create('div', {
				text: BX.message('LM_CREATE_SONETGROUP_TITLE').replace("#TITLE#", text)
			})
		]
	});
};

BX.YNSIRSocNetLogDestination.createSocNetGroupButtons = function(text, name)
{
	return [
		new BX.PopupWindowButton({
			text : BX.message("LM_CREATE_SONETGROUP_BUTTON_CREATE"),
			events : {
				click : function() {
					var groupCode = 'SGN'+ BX.YNSIRSocNetLogDestination.obNewSocNetGroupCnt[name] + '';
					BX.YNSIRSocNetLogDestination.obItems[name]['sonetgroups'][groupCode] = {
						id: groupCode,
						entityId: BX.YNSIRSocNetLogDestination.obNewSocNetGroupCnt[name],
						name: text,
						desc: ''
					};

					var itemsNew = {
						'sonetgroups': {
						}
					};
					itemsNew['sonetgroups'][groupCode] = true;

					if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
					{
						BX.YNSIRSocNetLogDestination.popupSearchWindowContent.innerHTML = BX.YNSIRSocNetLogDestination.getItemLastHtml(itemsNew, true, name);
					}
					else
					{
						BX.YNSIRSocNetLogDestination.openSearch(itemsNew, name);
					}

					BX.YNSIRSocNetLogDestination.obNewSocNetGroupCnt[name]++;
					BX.YNSIRSocNetLogDestination.createSocNetGroupWindow.close();
				}
			}
		}),
		new BX.PopupWindowButtonLink({
			text : BX.message("LM_CREATE_SONETGROUP_BUTTON_CANCEL"),
			className : "popup-window-button-link-cancel",
			events : {
				click : function() {
					BX.YNSIRSocNetLogDestination.createSocNetGroupWindow.close();
				}
			}
		})
	];
};

BX.YNSIRSocNetLogDestination.showSearchWaiter = function(name)
{
	if (
		typeof BX.YNSIRSocNetLogDestination.oSearchWaiterEnabled[name] == 'undefined'
		|| !BX.YNSIRSocNetLogDestination.oSearchWaiterEnabled[name]
	)
	{
		if (BX.YNSIRSocNetLogDestination.oSearchWaiterContentHeight > 0)
		{
			BX.YNSIRSocNetLogDestination.oSearchWaiterEnabled[name] = true;
			var startHeight = 0;
			var finishHeight = 40;

			BX.YNSIRSocNetLogDestination.animateSearchWaiter(startHeight, finishHeight, name);
		}
	}
};

BX.YNSIRSocNetLogDestination.hideSearchWaiter = function(name)
{
	if (
		typeof BX.YNSIRSocNetLogDestination.oSearchWaiterEnabled[name] != 'undefined'
		&& BX.YNSIRSocNetLogDestination.oSearchWaiterEnabled[name]
	)
	{
		BX.YNSIRSocNetLogDestination.oSearchWaiterEnabled[name] = false;

		var startHeight = 40;
		var finishHeight = 0;
		BX.YNSIRSocNetLogDestination.animateSearchWaiter(startHeight, finishHeight, name);
	}
};

BX.YNSIRSocNetLogDestination.animateSearchWaiter = function(startHeight, finishHeight, name)
{
	var contentBlock = (
		!!BX.YNSIRSocNetLogDestination.obUseContainer[name]
			? BX('bx-lm-box-last-content')
			: BX('bx-lm-box-search-tabs-content')
	);

	if (
		BX('bx-lm-box-search-waiter')
		&& contentBlock
	)
	{
		(new BX.fx({
			time: 0.5,
			step: 0.05,
			type: 'linear',
			start: startHeight,
			finish: finishHeight,
			callback: BX.delegate(function(height)
			{
				if (this)
				{
					this.waiterBlock.style.height = height + 'px';
//					this.contentBlock.style.height = (BX.YNSIRSocNetLogDestination.oSearchWaiterContentHeight) - height + 'px';
				}
			},
			{
				waiterBlock: BX('bx-lm-box-search-waiter'),
				contentBlock: contentBlock
			}),
			callback_complete: function()
			{
			}
		})).start();
	}
};

BX.YNSIRSocNetLogDestination.changeItemClass = function(element, template, bSelect)
{
	if (
		element
		&& typeof BX.YNSIRSocNetLogDestination.obTemplateClassSelected[template] != 'undefined'
	)
	{
		if (bSelect)
		{
			BX.addClass(element, BX.YNSIRSocNetLogDestination.obTemplateClassSelected[template]);
		}
		else
		{
			BX.removeClass(element, BX.YNSIRSocNetLogDestination.obTemplateClassSelected[template]);
		}
	}
};

BX.YNSIRSocNetLogDestination.getTemplateByItemClass = function(element)
{
	if (element)
	{
		for (var key in BX.YNSIRSocNetLogDestination.obTemplateClass)
		{
			if (BX.hasClass(element, BX.YNSIRSocNetLogDestination.obTemplateClass[key]))
			{
				return key;
			}
		}
	}
};

BX.YNSIRSocNetLogDestination.BXfpSetLinkName = function(ob)
{
	if (
		typeof (ob.tagInputName) != 'undefined'
		&& !!ob.tagInputName
	)
	{
		BX(ob.tagInputName).innerHTML = (
			BX.YNSIRSocNetLogDestination.getSelectedCount(ob.formName) <= 0
				? ob.tagLink1
				: ob.tagLink2
		);
	}
};

BX.YNSIRSocNetLogDestination.BXfpSelectCallback = function(params)
{
	if (!BX.findChild(params.containerInput, { attr : { 'data-id' : params.item.id }}, false, false))
	{
		var type1 = params.type;
		var prefix = 'S';

		if (BX.util.in_array(params.type, ['candidate','joborder','contacts', 'companies', 'leads', 'deals']))
		{
			type1 = 'crm';
		}

		if (params.type == 'sonetgroups')
		{
			prefix = 'SG';
			if (
				typeof window['arExtranetGroupID'] != 'undefined'
				&& BX.util.in_array(params.item.entityId, window['arExtranetGroupID'])
			)
			{
				type1 = 'extranet';
			}
		}
		else if (params.type == 'groups')
		{
			prefix = 'UA';
			type1 = 'all-users';
		}
		else if (BX.util.in_array(type1, ['users', 'emails']))
		{
			prefix = (BX.YNSIRSocNetLogDestination.checkEmail(params.item.id) ? 'UE' : 'U');
			if (
				typeof params.item.isCrmEmail != 'undefined'
				&& params.item.isCrmEmail == 'Y'
			)
			{
				type1 = 'crmemail';
			}
			else if (
				typeof params.item.isEmail != 'undefined'
				&& params.item.isEmail == 'Y'
			)
			{
				type1 = 'email';
			}
			else if (
				typeof params.item.isExtranet != 'undefined'
				&& params.item.isExtranet == 'Y'
			)
			{
				type1 = 'extranet';
			}
		}
		else if (params.type == 'crmemails')
		{
			prefix = (params.item.id.match(/(C|CO|L)_\d+/) ? 'UE' : 'U');
			type1 = 'crmemail';
		}
		else if (params.type == 'department')
		{
			prefix = 'DR';
		}
		else if (params.type == 'contacts')
		{
			prefix = 'CRMCONTACT';
		}
		else if (params.type == 'companies')
		{
			prefix = 'CRMCOMPANY';
		}
		else if (params.type == 'leads')
		{
			prefix = 'CRMLEAD';
		}
		else if (params.type == 'deals')
		{
			prefix = 'CRMDEAL';
		}
		else if (params.type == 'candidate')
		{
			prefix = 'YNSIRCANDIDATE';
		}
		else if (params.type == 'joborder')
		{
			prefix = 'YNSIRJOBODER';
		}


		var stl = (params.bUndeleted ? ' feed-add-post-destination-undelete' : '');

		var itemName = params.item.name + (
			typeof params.item.showEmail != 'undefined'
			&& params.item.showEmail == 'Y'
			&& typeof params.item.email != 'undefined'
			&& params.item.email.length > 0
				? ' (' + params.item.email + ')'
				: ''
		);

		var arChildren = [
			BX.create("span", {
				props : {
					'className' : "feed-add-post-destination-text"
				},
				html : itemName
			})
		];

		var arHidden = BX.YNSIRSocNetLogDestination.getHidden(prefix, params.item, (typeof params.varName != 'undefined' ? params.varName : false));
		if (!BX.YNSIRSocNetLogDestination.obShowSearchInput[params.formName])
		{
			arChildren = BX.util.array_merge(arChildren, arHidden)
		}

		var el = BX.create("span", {
			attrs : {
				'data-id' : params.item.id,
				'data-type' : params.type
			},
			props : {
				className : "feed-add-post-destination feed-add-post-destination-" + type1 + stl
			},
			children: arChildren
		});

		if(!params.bUndeleted)
		{
			el.appendChild(BX.create("span", {
				props : {
					'className' : "feed-add-post-del-but"
				},
				events : {
					'click' : function(e){
						BX.YNSIRSocNetLogDestination.deleteItem(params.item.id, params.type, params.formName);
						BX.PreventDefault(e)
					},
					'mouseover' : function(){
						BX.addClass(this.parentNode, 'feed-add-post-destination-hover');
					},
					'mouseout' : function(){
						BX.removeClass(this.parentNode, 'feed-add-post-destination-hover');
					}
				}
			}));
		}

		params.containerInput.appendChild(el);
	}

	if (
		!!BX.YNSIRSocNetLogDestination.obShowSearchInput[params.formName]
		&& !!BX.YNSIRSocNetLogDestination.obElementSearchInputHidden[params.formName]
	)
	{
		if (!BX.findChild(BX.YNSIRSocNetLogDestination.obElementSearchInputHidden[params.formName], { attr : { 'data-id' : params.item.id }}, false, false))
		{
			BX.YNSIRSocNetLogDestination.obElementSearchInputHidden[params.formName].appendChild(BX.create("span", {
				attrs : {
					'data-id' : params.item.id,
					'data-type' : params.type
				},
				children: arHidden
			}));
		}
	}

	params.valueInput.value = '';

	BX.YNSIRSocNetLogDestination.BXfpSetLinkName({
		formName: params.formName,
		tagInputName: (typeof params.tagInputName != 'undefined' ? params.tagInputName : false),
		tagLink1: params.tagLink1,
		tagLink2: params.tagLink2
	});
};

BX.YNSIRSocNetLogDestination.BXfpUnSelectCallback = function(item)
{
	var elements = BX.findChildren(BX(this.inputContainerName), {attribute: {'data-id': '' + item.id + ''}}, true);
	if (elements !== null)
	{
		for (var j = 0; j < elements.length; j++)
		{
			if (
				typeof (this.undeleteClassName) == 'undefined'
				|| !BX.hasClass(elements[j], this.undeleteClassName)
			)
			{
				BX.remove(elements[j]);
			}
		}
	}
	BX(this.inputName).value = '';
	BX.YNSIRSocNetLogDestination.BXfpSetLinkName(this);

	if (
		!!BX.YNSIRSocNetLogDestination.obShowSearchInput[this.formName]
		&& !!BX.YNSIRSocNetLogDestination.obElementSearchInputHidden[this.formName]
	)
	{
		elements = BX.findChildren(BX.YNSIRSocNetLogDestination.obElementSearchInputHidden[this.formName], {attribute: {'data-id': '' + item.id + ''}}, true);
		if (elements !== null)
		{
			for (var j = 0; j < elements.length; j++)
			{
				if (
					typeof (this.undeleteClassName) == 'undefined'
					|| !BX.hasClass(elements[j], this.undeleteClassName)
				)
				{
					BX.remove(elements[j]);
				}
			}
		}
	}
};

BX.YNSIRSocNetLogDestination.BXfpSearch = function(event)
{
	return BX.YNSIRSocNetLogDestination.searchHandler(event, {
		formName: this.formName,
		inputId: this.inputName,
		inputNode: (BX.type.isDomNode(this.inputNode) ? this.inputNode : null),
		linkId: this.tagInputName,
		sendAjax: (typeof this.sendAjax != 'undefined' ? this.sendAjax : true),
		multiSelect: true
	});
};

BX.YNSIRSocNetLogDestination.BXfpSearchBefore = function(event)
{
	return BX.YNSIRSocNetLogDestination.searchBeforeHandler(event, {
		formName: this.formName,
		inputId: this.inputName,
		inputNode: (BX.type.isDomNode(this.inputNode) ? this.inputNode : null)
	});
};

BX.YNSIRSocNetLogDestination.BXfpOpenDialogCallback = function()
{
	if (typeof this.inputBoxName != 'undefined')
	{
		BX.style(BX(this.inputBoxName), 'display', 'inline-block');
	}

	if (typeof this.tagInputName != 'undefined')
	{
		BX.style(BX(this.tagInputName), 'display', 'none');
	}

	BX.defer(BX.focus)(BX(this.inputName));
};

BX.YNSIRSocNetLogDestination.BXfpCloseDialogCallback = function()
{
	if (
		!BX.YNSIRSocNetLogDestination.isOpenSearch()
		&& BX(this.inputName).value.length <= 0
	)
	{
		if (typeof this.inputBoxName != 'undefined')
		{
			BX.style(BX(this.inputBoxName), 'display', 'none');
		}

		if (typeof this.tagInputName != 'undefined')
		{
			BX.style(BX(this.tagInputName), 'display', 'inline-block');
		}

		BX.YNSIRSocNetLogDestination.BXfpDisableBackspace();
	}
};

BX.YNSIRSocNetLogDestination.BXfpCloseSearchCallback = function()
{
	if (
		!BX.YNSIRSocNetLogDestination.isOpenSearch()
		&& BX(this.inputName).value.length > 0
	)
	{
		if (typeof this.inputBoxName != 'undefined')
		{
			BX.style(BX(this.inputBoxName), 'display', 'none');
		}

		if (typeof this.tagInputName != 'undefined')
		{
			BX.style(BX(this.tagInputName), 'display', 'inline-block');
		}

		BX(this.inputName).value = '';
		BX.YNSIRSocNetLogDestination.BXfpDisableBackspace();
	}
};

BX.YNSIRSocNetLogDestination.BXfpDisableBackspace = function(event)
{
	if (
		BX.YNSIRSocNetLogDestination.backspaceDisable
		|| BX.YNSIRSocNetLogDestination.backspaceDisable !== null
	)
	{
		BX.unbind(window, 'keydown', BX.YNSIRSocNetLogDestination.backspaceDisable);
	}

	BX.bind(window, 'keydown', BX.YNSIRSocNetLogDestination.backspaceDisable = function(event)
	{
		if (
			event.keyCode == 8
			&& !BX.util.in_array(event.target.tagName.toLowerCase(), ['input', 'textarea'])
		)
		{
			BX.PreventDefault(event);
			return false;
		}
	});

	setTimeout(function()
	{
		BX.unbind(window, 'keydown', BX.YNSIRSocNetLogDestination.backspaceDisable);
		BX.YNSIRSocNetLogDestination.backspaceDisable = null;
	}, 5000);
};

BX.YNSIRSocNetLogDestination.searchHandler = function(event, params)
{
	if (!this.searchStarted)
	{
		return false;
	}

	this.searchStarted = false;

	if (
		typeof event.clipboardData == 'undefined'
		&& (
			event.keyCode == 16
			|| event.keyCode == 17 // ctrl
			|| event.keyCode == 18
			|| event.keyCode == 20
			|| event.keyCode == 244
			|| event.keyCode == 224 // cmd
			|| event.keyCode == 91 // left cmd
			|| event.keyCode == 93 // right cmd
			|| event.keyCode == 9 // tab
		)
	)
	{
		return false;
	}

	var type = null;
	if (BX.YNSIRSocNetLogDestination.popupSearchWindow != null)
	{
		type = 'search';
	}
	else if (
		typeof event.keyCode != 'undefined'
		&& BX.util.in_array(event.keyCode, [37,38,39,40,13])
		&& BX.util.in_array(BX.YNSIRSocNetLogDestination.obTabSelected[params.formName], ['department'])
	)
	{
		return true;
	}
	else
	{
		type = BX.YNSIRSocNetLogDestination.obTabSelected[params.formName];
	}

	if (
		typeof event.keyCode != 'undefined'
		&& type
	)
	{
		if (event.keyCode == 37)
		{
			BX.YNSIRSocNetLogDestination.moveCurrentItem(type, params.formName, 'left');
			BX.PreventDefault(event);
			return false;
		}
		else if (event.keyCode == 38)
		{
			BX.YNSIRSocNetLogDestination.moveCurrentItem(type, params.formName, 'up');
			BX.PreventDefault(event);
			return false;
		}
		else if (event.keyCode == 39)
		{
			BX.YNSIRSocNetLogDestination.moveCurrentItem(type, params.formName, 'right');
			BX.PreventDefault(event);
			return false;
		}
		else if (event.keyCode == 40)
		{
			BX.YNSIRSocNetLogDestination.moveCurrentItem(type, params.formName, 'down');
			BX.PreventDefault(event);
			return false;
		}
		else if (event.keyCode == 13)
		{
			BX.YNSIRSocNetLogDestination.selectCurrentItem(type, params.formName);
			return BX.PreventDefault(event);
		}
		else if (
			typeof params.multiSelect != 'undefined'
			&& params.multiSelect
			&& event.keyCode == 32 // space
			&& type != 'search'
		)
		{
			BX.YNSIRSocNetLogDestination.selectCurrentItem(type, params.formName, {
				closeDialog: false
			});
			return true;
		}
	}

	var inputNode = (BX.type.isDomNode(params.inputNode) ? BX(params.inputNode) : BX(params.inputId));
	if (!inputNode)
	{
		return false;
	}

	var searchText = '';
	if (event.keyCode == 27)
	{
		if (
			BX.YNSIRSocNetLogDestination.inviteEmailUserWindow == null
			|| !BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.isShown()
		)
		{
			inputNode.value = '';
			BX.style(BX(params.linkId), 'display', 'inline');

			if (
				typeof params.formName != 'undefined'
				&& !BX.YNSIRSocNetLogDestination.obShowSearchInput[params.formName]
			)
			{
				BX.PreventDefault(event);
			}
		}
		else
		{
			BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.close();
			return false;
		}
	}
	else
	{
		if (typeof event.clipboardData != 'undefined')
		{
			searchText = event.clipboardData.getData('Text');
		}
		else
		{
			searchText = inputNode.value;
		}

		BX.YNSIRSocNetLogDestination.search(
			searchText,
			params.sendAjax,
			params.formName
		);
	}

	if (
		!BX.YNSIRSocNetLogDestination.isOpenDialog()
		&& searchText.length <= 0
	)
	{
		BX.YNSIRSocNetLogDestination.openDialog(params.formName);
	}
	else
	{
		if (
			BX.YNSIRSocNetLogDestination.sendEvent
			&& BX.YNSIRSocNetLogDestination.isOpenDialog()
			&& !BX.YNSIRSocNetLogDestination.isOpenContainer()
		)
		{
			BX.YNSIRSocNetLogDestination.closeDialog();
		}
	}

	if (event.keyCode == 8)
	{
		BX.YNSIRSocNetLogDestination.sendEvent = true;
	}

	return true;
};

BX.YNSIRSocNetLogDestination.searchBeforeHandler = function(event, params)
{
	var inputNode = (BX.type.isDomNode(params.inputNode) ? params.inputNode : BX(params.inputId));
	if (!inputNode)
	{
		return false;
	}

	if (
		event.keyCode == 8
		&& inputNode.value.length <= 0
	)
	{
		BX.YNSIRSocNetLogDestination.sendEvent = false;
		BX.YNSIRSocNetLogDestination.deleteLastItem(params.formName);
	}
	else if (event.keyCode == 13)
	{
		this.searchStarted = true;
		return BX.PreventDefault(event);
	}
	else if (
		event.keyCode == 17 // ctrl
		|| event.keyCode == 224 // cmd
		|| event.keyCode == 91 // left cmd
		|| event.keyCode == 93 // right cmd
	)
	{
		return BX.PreventDefault(event);
	}

	this.searchStarted = true;

	return true;
};

BX.YNSIRSocNetLogDestination.loadAll = function(params)
{
	if (
		typeof params != 'undefined'
		&& typeof params.name != 'undefined'
		&& typeof params.callback == 'function'
		&& (typeof params.entity == 'undefined' || params.entity == 'users')
	)
	{
		BX.ajax({
			url: '/bitrix/components/bitrix/main.post.form/post.ajax.php',
			method: 'POST',
			dataType: 'json',
			data: {
				'LD_ALL' : 'Y',
				'sessid': BX.bitrix_sessid()
			},
			onsuccess: function(data)
			{
				if (typeof data.USERS != 'undefined')
				{
					BX.onCustomEvent('onFinderAjaxLoadAll', [ data.USERS, BX.YNSIRSocNetLogDestination, 'users' ]);
				}
				params.callback();
			},
			onfailure: function(data)
			{
			}
		});
	}
};

BX.YNSIRSocNetLogDestination.compareDestinations = function(a, b)
{
	if (
		typeof a.isNetwork == 'undefined'
		&& typeof b.isNetwork != 'undefined'
	)
	{
		return -1;
	}
	else if (
		typeof a.isNetwork != 'undefined'
		&& typeof b.isNetwork == 'undefined'
	)
	{
		return 1;
	}
	else if (
		typeof a.sort == 'undefined'
		&& typeof b.sort == 'undefined'
	)
	{
		return 0;
	}
	else if (
		typeof a.sort != 'undefined'
		&& typeof b.sort == 'undefined'
	)
	{
		return -1;
	}
	else if (
		typeof a.sort == 'undefined'
		&& typeof b.sort != 'undefined'
	)
	{
		return 1;
	}
	else
	{
		if (
			typeof a.sort.Y != 'undefined'
			&& typeof b.sort.Y == 'undefined'
		)
		{
			return -1;
		}
		else if (
			typeof a.sort.Y == 'undefined'
			&& typeof b.sort.Y != 'undefined'
		)
		{
			return 1;
		}
		else if (
			typeof a.sort.Y != 'undefined'
			&& typeof b.sort.Y != 'undefined'
		)
		{
			if (parseInt(a.sort.Y) > parseInt(b.sort.Y))
			{
				return -1;
			}
			else if (parseInt(a.sort.Y) < parseInt(b.sort.Y))
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			if (parseInt(a.sort.N) > parseInt(b.sort.N))
			{
				return -1;
			}
			else if (parseInt(a.sort.N) < parseInt(b.sort.N))
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
	}
};

BX.YNSIRSocNetLogDestination.checkEmail = function(searchString)
{
	var re = /^([^<]+)\s<([^>]+)>$/igm;
	var matches = re.exec(searchString);
	var userName = '';
	var userLastName = '';

	if (
		matches != null
		&& matches.length == 3
	)
	{
		userName = matches[1];
		var parts = userName.split(/[\s]+/);
		userLastName = parts.pop();
		userName = parts.join(' ');

		searchString = matches[2].trim();
	}

	re = /^[=_0-9a-z+~'!\$&*^`|\#%/?{}-]+(\.[=_0-9a-z+~'!\$&*^`|\#%/?{}-]+)*@(([-0-9a-z_]+\.)+)([a-z0-9-]{2,20})$/igm;

	if (
		searchString.length >= 6
		&& re.test(searchString)
	)
	{
		return {
			name: userName,
			lastName: userLastName,
			email: searchString.toLowerCase()
		};
	}
	else
	{
		return false;
	}
};

BX.YNSIRSocNetLogDestination.openInviteEmailUserDialog = function(obUserEmail, name, bCrm)
{
	BX.YNSIRSocNetLogDestination.inviteEmailCurrentName = name;

	if (BX.YNSIRSocNetLogDestination.inviteEmailUserWindow === null)
	{
		BX.YNSIRSocNetLogDestination.inviteEmailUserWindow = new BX.PopupWindow("invite-email-email-user-popup", BX.YNSIRSocNetLogDestination.obElementSearchInput[name], {
			offsetTop : 1,
			content : BX.YNSIRSocNetLogDestination.inviteEmailUserContent(obUserEmail, name, bCrm),
			zIndex : 1250,
			lightShadow : true,
			autoHide : true,
			closeByEsc: true,
			angle: {
				position: "bottom",
				offset : 20
			},
			events: {
				onPopupClose : function()
				{
					if (
						BX.YNSIRSocNetLogDestination.inviteEmailUserWindow != null
						|| !BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.isShown()
					)
					{
						var params = {
							name: (BX.YNSIRSocNetLogDestination.inviteEmailUserWindowSubmitted ? BX('invite_email_user_name').value : ''),
							lastName: (BX.YNSIRSocNetLogDestination.inviteEmailUserWindowSubmitted ? BX('invite_email_user_last_name').value : ''),
							email: BX('invite_email_user_email').value,
							createCrmContact: (BX('invite_email_user_create_crm_contact') && BX('invite_email_user_create_crm_contact').checked)
						};

						BX.YNSIRSocNetLogDestination.inviteEmailAddUser(BX.YNSIRSocNetLogDestination.inviteEmailCurrentName, params);
					}
					BX.YNSIRSocNetLogDestination.inviteEmailUserWindowSubmitted = false;

					if (
						BX.YNSIRSocNetLogDestination.sendEvent
						&& BX.YNSIRSocNetLogDestination.obCallback[BX.YNSIRSocNetLogDestination.inviteEmailCurrentName]
						&& BX.YNSIRSocNetLogDestination.obCallback[BX.YNSIRSocNetLogDestination.inviteEmailCurrentName].closeEmailAdd
					)
					{
						BX.YNSIRSocNetLogDestination.obCallback[BX.YNSIRSocNetLogDestination.inviteEmailCurrentName].closeEmailAdd(BX.YNSIRSocNetLogDestination.inviteEmailCurrentName);
					}
				},
				onPopupShow: function()
				{
					BX.defer(BX.focus)(BX('invite_email_user_name'));

					if (
						BX.YNSIRSocNetLogDestination.sendEvent
						&& BX.YNSIRSocNetLogDestination.obCallback[BX.YNSIRSocNetLogDestination.inviteEmailCurrentName]
						&& BX.YNSIRSocNetLogDestination.obCallback[BX.YNSIRSocNetLogDestination.inviteEmailCurrentName].openEmailAdd
					)
					{
						BX.YNSIRSocNetLogDestination.obCallback[BX.YNSIRSocNetLogDestination.inviteEmailCurrentName].openEmailAdd(BX.YNSIRSocNetLogDestination.inviteEmailCurrentName);
					}
				}
			}
		});
	}
	else
	{
		BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.setContent(
			BX.YNSIRSocNetLogDestination.inviteEmailUserContent(obUserEmail, BX.YNSIRSocNetLogDestination.inviteEmailCurrentName, bCrm)
		);
		BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.setBindElement(BX.YNSIRSocNetLogDestination.obElementSearchInput[BX.YNSIRSocNetLogDestination.inviteEmailCurrentName]);
	}

	if (BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.popupContainer.style.display != "block")
	{
		BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.show();
	}
};

BX.YNSIRSocNetLogDestination.inviteEmailAddUser = function(name, params)
{
	var bShowEmail = false;
	var userEmail = params.email;
	var userName = BX.util.htmlspecialchars(params.name) + (params.name.length > 0 ? ' ' : '') + BX.util.htmlspecialchars(params.lastName);

	if (userName.length <= 0)
	{
		userName = userEmail;
	}
	else
	{
		bShowEmail = true;
	}

	if (typeof BX.YNSIRSocNetLogDestination.obItems[name]['users'] == 'undefined')
	{
		BX.YNSIRSocNetLogDestination.obItems[name]['users'] = [];
	}

	BX.YNSIRSocNetLogDestination.obItems[name]['users'][userEmail] = {
		name: userName,
		email: userEmail,
		id: userEmail,
		isEmail: 'Y',
		isCrmEmail: (typeof params.createCrmContact != 'undefined' && !!params.createCrmContact ? 'Y' : 'N'),
		showEmail: (bShowEmail ? 'Y' : 'N'),
		params: params
	};

	// add to form

	BX.YNSIRSocNetLogDestination.runSelectCallback(userEmail, 'users', name, false, 'select');
};

BX.YNSIRSocNetLogDestination.inviteEmailUserContent = function(obUserEmail, name, bCrm)
{
	bCrm = !!bCrm;

	return BX.create('DIV', {
		props: {
			className: 'bx-feed-email-popup'
		},
		children: [
			BX.create('DIV', {
				props: {
					className: 'bx-feed-email-title'
				},
				text: BX.message('LM_INVITE_EMAIL_USER_TITLE')
			}),
			BX.create('FORM', {
				style: {
					padding: 0,
					margin: 0
				},
				events : {
					submit : function(e) {
						BX.YNSIRSocNetLogDestination.inviteEmailUserSubmitForm(name);
						BX.PreventDefault(e);
					}
				},
				children: [
					BX.create('DIV', {
						children: [
							BX.create('INPUT', {
								attrs: {
									id: 'invite_email_user_email',
									type: "hidden",
									value: obUserEmail.email
								}
							}),
							BX.create('INPUT', {
								attrs: {
									id: 'invite_email_user_name',
									type: "text",
									placeholder: BX.message('LM_INVITE_EMAIL_USER_PLACEHOLDER_NAME'),
									value: obUserEmail.name
								},
								props: {
									className: 'bx-feed-email-input'
								}
							}),
							BX.create('INPUT', {
								attrs: {
									id: 'invite_email_user_last_name',
									type: "text",
									placeholder: BX.message('LM_INVITE_EMAIL_USER_PLACEHOLDER_LAST_NAME'),
									value: obUserEmail.lastName
								},
								props: {
									className: 'bx-feed-email-input'
								},
								events : {
									keyup : function(e) {
										if (
											BX('invite_email_user_name').value.length > 0
											|| BX('invite_email_user_last_name').value.length > 0
										)
										{
											BX.removeClass(BX('invite_email_user_button'), 'webform-button-disable');
										}
										else
										{
											BX.addClass(BX('invite_email_user_button'), 'webform-button-disable');
										}
										BX.PreventDefault(e);
									}
								}
							}),
							BX.create('SPAN', {
								attrs: {
									id: 'invite_email_user_button'
								},
								props: {
									className: 'webform-small-button webform-small-button-blue webform-button-disable'
								},
								text: BX.message("LM_INVITE_EMAIL_USER_BUTTON_OK"),
								style: {
									cursor: 'pointer'
								},
								events : {
									click : function() {
										BX.YNSIRSocNetLogDestination.inviteEmailUserSubmitForm(name);
									}
								}
							}),
							BX.create('INPUT', {
								style: {
									display: 'none'
								},
								attrs: {
									type: 'submit'
								}
							})
						]
					}),
					(
						bCrm
						? BX.create('DIV', {
							props: {
								className: 'bx-feed-email-crm-contact'
							},
							children: [
								BX.create('INPUT', {
									attrs: {
										className: 'bx-feed-email-checkbox',
										type: 'checkbox',
										id: 'invite_email_user_create_crm_contact',
										value: 'Y'
									}
								}),
								BX.create('LABEL', {
									attrs: {
										for: 'invite_email_user_create_crm_contact'
									},
									html: BX.message('LM_INVITE_EMAIL_CRM_CREATE_CONTACT')
								})
							]
						})
						: null
					)
				]
			})
		]
	});
};

BX.YNSIRSocNetLogDestination.inviteEmailUserSubmitForm = function(name)
{
	BX.YNSIRSocNetLogDestination.inviteEmailUserWindowSubmitted = true;
	BX.YNSIRSocNetLogDestination.inviteEmailUserWindow.close();
};

BX.YNSIRSocNetLogDestination.buildDepartmentRelation = function(department)
{
	var relation = {}, p, iid;
	for(iid in department)
	{
		if (department.hasOwnProperty(iid))
		{
			p = department[iid]['parent'];
			if (!relation[p])
				relation[p] = [];
			relation[p][relation[p].length] = iid;
		}
	}

	function makeDepartmentTree(id, relation)
	{
		var arRelations = {}, relId, arItems;
		if (relation[id])
		{
			for (var x in relation[id])
			{
				if (relation[id].hasOwnProperty(x))
				{
					relId = relation[id][x];
					arItems = [];
					if (relation[relId] && relation[relId].length > 0)
						arItems = makeDepartmentTree(relId, relation);

					arRelations[relId] = {
						id: relId,
						type: 'category',
						items: arItems
					};
				}
			}
		}

		return arRelations;
	}

	return makeDepartmentTree('DR0', relation);
};

BX.YNSIRSocNetLogDestination.abortSearchRequest = function()
{
	if (BX.YNSIRSocNetLogDestination.oXHR)
	{
		BX.YNSIRSocNetLogDestination.oXHR.abort();
	}
};

BX.YNSIRSocNetLogDestination.onTabsAdd = function(name, oTab)
{
	if (!BX.util.in_array(oTab.id, BX.YNSIRSocNetLogDestination.obTabs[name]))
	{
		BX.YNSIRSocNetLogDestination.obTabs[name].push(oTab.id);
		if (typeof BX.YNSIRSocNetLogDestination.obCustomTabs[name] == 'undefined')
		{
			BX.YNSIRSocNetLogDestination.obCustomTabs[name] = [];
		}
		BX.YNSIRSocNetLogDestination.obCustomTabs[name].push(oTab);

		if (oTab.dialogGroup != 'undefined')
		{
			var bFound = false;
			for (var j=0; j < BX.YNSIRSocNetLogDestination.arDialogGroups[name].length; j++)
			{
				if (BX.YNSIRSocNetLogDestination.arDialogGroups[name][j].groupCode == oTab.dialogGroup.groupCode)
				{
					bFound = true;
					break;
				}
			}

			if (!bFound)
			{
				BX.YNSIRSocNetLogDestination.arDialogGroups[name].push({
					bCrm: (
						typeof oTab.dialogGroup.bCrm != 'undefined'
							? !!oTab.dialogGroup.bCrm
							: false
					),
					groupCode: oTab.dialogGroup.groupCode,
					className: (
						typeof oTab.dialogGroup.className != 'undefined'
							? oTab.dialogGroup.className
							: ''
					),
					groupboxClassName: (
						typeof oTab.dialogGroup.groupboxClassName != 'undefined'
							? oTab.dialogGroup.groupboxClassName
							: ''
					),
					title: oTab.dialogGroup.title
				});
			}
		}
	}
};

})();