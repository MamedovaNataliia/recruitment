if (typeof(YNSIRConfigurations) === "undefined") {
    var YNSIRConfigurations = function (settings,
                                        containerId) {
        this.settings = null;
        this.containerId = '';
        this.container = null;

        this._messages = null;

        this.init(
            settings,
            containerId
        );
    };
    YNSIRConfigurations.prototype = {
        init: function (settings, containerId) {
            this.settings = settings;
            this.containerId = containerId;
            this.container = BX(containerId);
            if(typeof this.container !== 'undefined') {
                this.tabIndex =  BX.findChildrenByClassName(this.container,'tab_index');
                this.tabContent =  BX.findChildrenByClassName(this.container,'tab_content');
            }
            this._messages = this.getSetting("messages", {});
        },
        getSetting: function (name, defaultval) {
            return typeof(this.settings[name]) != 'undefined' ? this.settings[name] : defaultval;
        },
        getMessage: function (name) {
            return this._messages.hasOwnProperty(name) ? this._messages[name] : name;
        },
        getNode: function(name_query,value, scope)
        {
            if (!scope) {
                scope = this.getTabNode();
            }
            return scope ? scope.querySelector('['+name_query+'="'+value+'"]') : null;
        },
        getTabNode: function()
        {
            return this.container;
        },
        create: function() {
            var me = this;
            if (this.tabIndex)
            {
                for (var i = 0; i < this.tabIndex.length; i++) {
                    BX.bind(BX(this.tabIndex[i]), 'click', function()
                    {
                        me.onActiveTab(this);
                    });

                }
            }
        },
        onActiveTab: function (obj) {
            var attrTabIndex = obj.getAttribute('tab-index-value');
            this.activeTab(attrTabIndex);
        },
        activeTab: function(attrTab) {
            for (var i = 0; i < this.tabIndex.length; i++) {
                BX.removeClass(this.tabIndex[i], 'status_tab_active');
            }
            for (var i = 0; i < this.tabContent.length; i++) {
                BX.removeClass(this.tabContent[i], 'active');
            }
            var contentActive = this.getNode('tab-content-value',attrTab);
            var indexActive = this.getNode('tab-index-value',attrTab);
            if(indexActive) BX.addClass(indexActive,'status_tab_active');
            if(contentActive) BX.addClass(contentActive,'active');
        },
        showWait:function() {
            // if(BX(this.getSetting('saveBTN')))
            // BX.addClass(BX(this.getSetting('saveBTN')),'bp-button-wait');
        },
        closeWait:function() {
            // if(BX(this.getSetting('saveBTN')))
            // BX.removeClass(BX(this.getSetting('saveBTN')),'bp-button-wait');
        },

        /*
        function submitConfig
         */
        submitConfig: function () {
            this.showWait();
            var me = this;
            var node_error = null;
            var section_id = $('#YNSIR_BIZ_APPROVE_ORDER_ID').val();
            var biz_scan_cv_id = $('#BIZPROC_BIZ_SCAN_CV_ID').val();

            var ACCEPT_OFFER_STATUS_ID = $('#ACCEPT_OFFER_STATUS').val();
            var REJECT_OFFER_STATUS_ID = $('#REJECT_OFFER_STATUS').val();

            var err = false;
            $('#YNSIR_BIZ_APPROVE_ORDER_ID').removeClass('element-error');
            $('#BIZPROC_BIZ_SCAN_CV_ID').removeClass('element-error');

            $('#ACCEPT_OFFER_STATUS').removeClass('element-error');
            $('#REJECT_OFFER_STATUS').removeClass('element-error');
            var error_text = '';
            $('#YNSIR_BIZ_APPROVE_ORDER_ID').parent().find('.error').hide().find('span').first().text('');
            $('#BIZPROC_BIZ_SCAN_CV_ID').parent().find('.error').hide().find('span').first().text('');

            $('#ACCEPT_OFFER_STATUS').parent().find('.error').hide().find('span').first().text('');
            $('#REJECT_OFFER_STATUS').parent().find('.error').hide().find('span').first().text('');
            // if (ACCEPT_OFFER_STATUS_ID.length <= 0) {
            //     err = true;
            //     $('#ACCEPT_OFFER_STATUS').addClass('element-error');
            //     $('#ACCEPT_OFFER_STATUS').parent().find('.error').show().find('span').first().show().append(MESSAGE_ERROR.ACCEPT_OFFER_STATUS_EMPTY);
            //     node_error = 'ACCEPT_OFFER_STATUS';
            // }
            //
            // if (REJECT_OFFER_STATUS_ID.length <= 0) {
            //     err = true;
            //     $('#REJECT_OFFER_STATUS').addClass('element-error');
            //     $('#REJECT_OFFER_STATUS').parent().find('.error').show().find('span').first().show().append(MESSAGE_ERROR.REJECT_OFFER_STATUS_EMPTY);
            //     node_error = 'REJECT_OFFER_STATUS';
            // }
            //
            // if(REJECT_OFFER_STATUS_ID == ACCEPT_OFFER_STATUS_ID && ACCEPT_OFFER_STATUS_ID.length > 0) {
            //     err = true;
            //     $('#REJECT_OFFER_STATUS').addClass('element-error');
            //     $('#REJECT_OFFER_STATUS').parent().find('.error').show().find('span').first().show().append(MESSAGE_ERROR.REJECT_OFFER_STATUS_DUPPLICATE);
            //     node_error = 'REJECT_OFFER_STATUS';
            // }

            // if (parseInt(section_id) <= 0 || isNaN(parseInt(section_id))) {
            //     err = true;
            //     $('#YNSIR_BIZ_APPROVE_ORDER_ID').addClass('element-error');
            //     $('#YNSIR_BIZ_APPROVE_ORDER_ID').parent().find('.error').show().find('span').first().show().append(MESSAGE_ERROR.ERROR_WORFLOW_EMPTY);
            //     node_error = 'YNSIR_BIZ_APPROVE_ORDER_ID';
            // }
            // if (parseInt(biz_scan_cv_id) <= 0 || isNaN(parseInt(biz_scan_cv_id))) {
            //     err = true;
            //     $('#BIZPROC_BIZ_SCAN_CV_ID').addClass('element-error');
            //     $('#BIZPROC_BIZ_SCAN_CV_ID').parent().find('.error').show().find('span').first().show().append(MESSAGE_ERROR.ERROR_SCAN_WF_EMPTY);
            //     node_error = 'BIZPROC_BIZ_SCAN_CV_ID';
            // }

            if (!err) {
                var data = BX.ajax.prepareForm(BX(me.getSetting('formID'))).data;
                BX.ajax({
                    url: this.getSetting('url','/recruitment/config/configs/'),
                    method: 'POST',
                    dataType: 'json',
                    data: data,
                    onsuccess: function (result) {
                        me.closeWait();
                        if (result.SUCCESS == 1) {
                            window.location.reload();
                        } else {
                            for (var key in result.MESSAGE_ERROR) {
                                $('#' + key).addClass('element-error');
                                $('#' + key).parent().find('.error').show().find('span').first().show().append(result.MESSAGE_ERROR[key]);
                            }
                            var parentContent = BX.findParent(BX(key),{attribute:'tab-content-value'});
                            if(typeof parentContent !== 'undefined' && parentContent != null)
                            {
                                let attrTabIndex = parentContent.getAttribute('tab-content-value');
                                me.activeTab(attrTabIndex);
                            }
                        }
                    }
                });
            } else {
                me.closeWait();
                var parentContent = BX.findParent(BX(node_error),{attribute:'tab-content-value'});
                if(typeof parentContent !== 'undefined' && parentContent != null)
                {
                    let attrTabIndex = parentContent.getAttribute('tab-content-value');
                    me.activeTab(attrTabIndex);
                }
            }
            return false;
        }
    };
}
