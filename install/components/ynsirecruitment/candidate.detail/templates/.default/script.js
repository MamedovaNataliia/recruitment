/**
 * Created by nhatth on 7/18/17.
 */

if(typeof(BX.CandidateQuickPanelView) === "undefined")
{
    BX.CandidateQuickPanelView = function()
    {
        this._id = "";
        this._settings = null;
        this._prefix = "";

        this._isFixed = false;
        this._isExpanded = false;

        this._wrapper = null;
        this._section_wrapper = null;
        this._placeholder = null;
        this._pinButton = null;

        this._wait = null;
        this._waitAnchor = null;

        this._scrollHandler = BX.delegate(this._onWindowScroll, this);
        this._resizeHandler = BX.delegate(this._onWindowResize, this);
    }
    BX.CandidateQuickPanelView.prototype =
        {
            initialize: function (id,settings) {
                this._id = BX.type.isNotEmptyString(id) ? id : "";
                this._settings = settings ? settings : {};

                this._prefix = this.getSetting("prefix", "");

                this._serviceUrl = this.getSetting("serviceUrl", "");
                this._config = this.getSetting("config", {});

                if(!BX.type.isNotEmptyString(this._serviceUrl))
                {
                    throw "CandidateQuickPanelView: Could no find service url .";
                }

                this._placeholder = BX(this.resolveElementId("placeholder"));
                if(!this._placeholder)
                {
                    throw "CandidateQuickPanelView: Could no find placeholder.";
                }

                this._wrapper = BX(this.resolveElementId("wrap"));
                if(!this._wrapper)
                {
                    throw "CandidateQuickPanelView: Could no find wrapper.";
                }

                this._section_wrapper = BX(this.resolveElementId("section_wrapper"));
                if(!this._wrapper)
                {
                    throw "CandidateQuickPanelView: Could no find section wrapper .";
                }

                this._enableUserConfig = this._config["enabled"] === "Y";
                this._isExpanded = this._config["expanded"] === "Y";
                this._isFixed = this._config["fixed"] === "Y";
                if(this._isFixed)
                {
                    this.adjust();
                    BX.bind(window, "scroll", this._scrollHandler);
                    BX.bind(window, "resize", this._resizeHandler);
                }

                this._pinButton = BX(this.resolveElementId("pin_btn"));
                if(this._pinButton)
                {
                    BX.bind(this._pinButton, "click", BX.delegate(this._onPinButtonClick, this));
                }

                this._toggleButton = BX(this.resolveElementId("toggle_btn"));
                if(this._toggleButton)
                {
                    BX.bind(this._toggleButton, "click", BX.delegate(this._onToggleButtonClick, this));
                }
                BX.addCustomEvent(
                    window,
                    "CadidateQuickPanelViewExpanded",
                    BX.delegate(this._onQuickPanelViewExpand, this)
                );
            },
            _onQuickPanelViewExpand:function(panel,isExpand) {
                this._SetViewModeVisibility(isExpand)
            },
            _SetViewModeVisibility: function(visible)
            {
                visible = !!visible;
                if(this.isVisibleInViewMode === visible)
                {
                    return;
                }

                this.isVisibleInViewMode = visible;

                var container = this._section_wrapper;
                if(container)
                {
                    container.style.display = this.isVisibleInViewMode ? "" : "none";
                }
            },
            getSetting: function (name, defaultval)
            {
                return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
            },
            resolveElementId: function(id)
            {
                return this._prefix !== "" ? (this._prefix + "_" + id) : id;
            },
            isExpanded: function()
            {
                return this._isExpanded;
            },
            isFixed: function()
            {
                return this._isFixed;
            },
            _onWindowScroll: function()
            {
                this.adjust();
            },
            _onWindowResize: function(e)
            {
                this.adjust(true);
            },
            adjust: function(force)
            {
                if(!this._isFixed)
                {
                    return;
                }

                var heightOffset = 0;
                var panel = typeof(BX.CrmControlPanel) !== "undefined" ? BX.CrmControlPanel.getDefault() : null;
                if(panel && panel.isFixed())
                {
                    heightOffset = panel.getRect().height;
                }

                if (BX.CandidateQuickPanelView.getNodeRect(this._placeholder).top <= heightOffset)
                {
                    if(this._isFixedLayout && force !== true)
                    {
                        //synchronize wrapper width
                        this._wrapper.style.width = BX.CandidateQuickPanelView.getNodeRect(this._placeholder).width.toString() + "px";
                    }
                    else
                    {
                        var r = BX.CandidateQuickPanelView.getNodeRect(this._wrapper.parentNode);
                        this._wrapper.style.height = this._placeholder.style.height = r.height.toString() + "px";
                        this._wrapper.style.width = r.width.toString() + "px";
                        this._wrapper.style.left = r.left > 0 ? (r.left.toString() + "px") : "0";
                        this._wrapper.style.top = heightOffset > 0 ? (heightOffset.toString() + "px") : "0";

                        BX.addClass(this._wrapper, "crm-lead-header-table-wrap-fixed");
                        this._isFixedLayout = true;
                    }
                }
                else if(this._isFixedLayout)
                {
                    this._isFixedLayout = false;
                    BX.removeClass(this._wrapper, "crm-lead-header-table-wrap-fixed");

                    this._placeholder.style.height = this._placeholder.style.width = "";
                    this._wrapper.style.height = this._wrapper.style.width = this._wrapper.style.left = this._wrapper.style.top = "";
                }
            },
            setExpanded: function(expanded)
            {
                expanded = !!expanded;
                if(this._isExpanded === expanded)
                {
                    return;
                }

                this._isExpanded = expanded;

                BX.onCustomEvent(
                    window,
                    "CadidateQuickPanelViewExpanded",
                    [this, this._isExpanded]
                );

                if(this._isExpanded)
                {
                    BX.removeClass(this._toggleButton, "crm-lead-header-contact-btn-close");
                    BX.addClass(this._toggleButton, "crm-lead-header-contact-btn-open");
                }
                else
                {
                    BX.removeClass(this._toggleButton, "crm-lead-header-contact-btn-open");
                    BX.addClass(this._toggleButton, "crm-lead-header-contact-btn-close");
                }
                this.saveConfig(false);
            },
            setFixed: function(fixed)
            {
                fixed = !!fixed;
                if(this._isFixed === fixed)
                {
                    return;
                }

                if(fixed)
                {
                    BX.unbind(window, "scroll", this._scrollHandler);
                    BX.bind(window, "scroll", this._scrollHandler);

                    BX.unbind(window, "resize", this._resizeHandler);
                    BX.bind(window, "resize", this._resizeHandler);

                    BX.removeClass(this._pinButton, "crm-lead-header-contact-btn-unpin");
                    BX.addClass(this._pinButton, "crm-lead-header-contact-btn-pin");
                }
                else
                {
                    BX.unbind(window, "scroll", this._scrollHandler);
                    BX.unbind(window, "resize", this._resizeHandler);

                    BX.removeClass(this._wrapper, "crm-lead-header-table-wrap-fixed");
                    BX.removeClass(this._pinButton, "crm-lead-header-contact-btn-pin");
                    BX.addClass(this._pinButton, "crm-lead-header-contact-btn-unpin");

                    this._placeholder.style.height = this._placeholder.style.width = "";
                    this._wrapper.style.height = this._wrapper.style.width = this._wrapper.style.left = this._wrapper.style.top = "";
                }

                this._isFixed = fixed;
                this._isFixedLayout = false;

                this.saveConfig(false);
            },
            getConfig: function()
            {
                var config =
                    {
                        enabled: 'Y',
                        expanded: this._isExpanded ? "Y" : "N",
                        fixed: this._isFixed ? "Y" : "N"
                    };
                return config;
            },
            saveConfig: function(forAllUsers, callback)
            {
                forAllUsers = !!forAllUsers && this.getSetting("canSaveSettingsForAll", false);

                var data = { guid: this._id, action: "saveconfig", config: this.getConfig() };
                if(forAllUsers)
                {
                    data["forAllUsers"] = "Y";
                    data["delete"] = "Y";
                }

                if(BX.type.isFunction(callback))
                {
                    this._requestCompleteCallback = callback;
                }

                this._waiter = BX.showWait(this._lastChangedSection);

                BX.ajax.post(this._serviceUrl, data, BX.delegate(this._onConfigRequestComplete, this));
            },
            _onConfigRequestComplete: function()
            {
                if(this._waiter)
                {
                    BX.closeWait(this._waitAnchor, this._waiter);
                    this._waiter = null;
                }

                // if(this._requestCompleteCallback)
                // {
                //     var callback = this._requestCompleteCallback;
                //     this._requestCompleteCallback = null;
                //     callback();
                // }
            },
            _onPinButtonClick: function(e)
            {
                this.setFixed(!this.isFixed());
            },
            _onToggleButtonClick: function(e)
            {
                if(!e)
                {
                    e = window.event;
                }

                this.setExpanded(!this.isExpanded());
                return BX.PreventDefault(e);
            },
        }
    BX.CandidateQuickPanelView.getNodeRect = function(node)
    {
        var r = node.getBoundingClientRect();
        return (
            {
                top: r.top, bottom: r.bottom, left: r.left, right: r.right,
                width: typeof(r.width) !== "undefined" ? r.width : (r.right - r.left),
                height: typeof(r.height) !== "undefined" ? r.height : (r.bottom - r.top)
            }
        );
    };
    BX.CandidateQuickPanelView.create = function(id, settings)
    {
        var self = new BX.CandidateQuickPanelView();

        self.initialize(id, settings);
        return self;
    };
}
