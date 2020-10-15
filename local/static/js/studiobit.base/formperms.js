$(function() {
    if(!$.Studiobit)
        $.Studiobit = {};

    if(!$.Studiobit.Base)
        $.Studiobit.Base = {};

    $.Studiobit.Base.FormPerms = function(formId, options){
        this.formId = formId;
        this.fields = options.fields;
        this.showSettings = options.showSettings;
        this.init();
    };

    $.Studiobit.Base.FormPerms.getInstance = function(formId, options){
        if(!this.instances)
            this.instances ={};

        if(!this.instances[formId]){
            this.instances[formId] = new $.Studiobit.Base.FormPerms(formId, options);
        }

        return this.instances[formId];
    };

    $.Studiobit.Base.FormPerms.prototype.init = function() {
        var _this = this;
        _this.popup = false;
        if(_this.formId)
        {
            var form = $('.crm-entity-card-container-content form');
            if (form.length) {
                if(this.showSettings) {
                    BX.addCustomEvent(window, 'onPopupShow', function (popup) {
                        var cid = '';
                        if (matches = popup.uniquePopupId.match(/menu-popup-([a-zA-Z_0-9]+)/i)) {
                            cid = matches[1];
                        }

                        if (cid !== '') {
                            if($('div[data-cid="' + cid+ '"]').length) {
                                var container = $(popup.contentContainer);
                                container.find('[data-action]').remove();

                                var newItems = [];
                                var items = $('.menu-popup-items', container);
                                var item = $('span.menu-popup-item-text:contains("Настроить")');
                                if (item.length) {
                                    item = item.closest('.menu-popup-item');
                                }

                                var itemPerms = $('<span class="menu-popup-item menu-popup-no-icon" data-action="perms">' +
                                    '<span class="menu-popup-item-icon"></span><span class="menu-popup-item-text">Права...</span>' +
                                    '</span>');

                                itemPerms.on('click', function () {
                                    var menu = BX.PopupMenu.getCurrentMenu();
                                    if (menu)
                                        menu.close();

                                    _this.showForm(cid);
                                });

                                newItems.push(itemPerms);

                                $.each(newItems.reverse(), function (key, newItem) {
                                    if (item) {
                                        newItem.insertAfter(item);
                                    }
                                    else {
                                        items.append(newItem);
                                    }
                                });
                            }
                        }
                    });
                }
            }

            this.initFields();
        }
    };

    $.Studiobit.Base.FormPerms.prototype.showForm = function(cid) {
        var _this = this;

        if (_this.popup)
            return false;

        $.get('/ajax/form/getfieldsettings/', {form_id: _this.formId, field: cid}, function (result) {
            if (result) {
                if (result.result == 'ok') {
                    _this.popup = new BX.PopupWindow(
                        cid,
                        null,
                        {
                            autoHide: false,
                            draggable: true,
                            offsetLeft: 0,
                            offsetTop: 0,
                            bindOptions: {forceBindPosition: true},
                            closeByEsc: true,
                            closeIcon: {top: "10px", right: "15px"},
                            overlay: 0.4,
                            titleBar: 'Настройка прав',
                            content: _this.getHtmlSettings(result),
                            events:
                            {
                                onPopupShow: function () {

                                },
                                onPopupClose: function () {
                                    _this.popup = false;
                                },
                                onPopupDestroy: function () {
                                    _this.popup = false;
                                }
                            },
                            buttons: [
                                new BX.PopupWindowButton(
                                    {
                                        text: 'Сохранить',
                                        className: "popup-window-button-accept",
                                        events: {
                                            click: function () {
                                                if (_this.formSettings.length) {
                                                    var data = _this.formSettings.serializeArray();
                                                    data.push({name: 'form_id', value: _this.formId});
                                                    data.push({name: 'field', value: cid});
                                                    data.push({name: 'sessid', value: BX.bitrix_sessid()});

                                                    $.post('/ajax/form/savefieldsettings/', data, function (result) {
                                                        //console.log(result);
                                                    });

                                                    _this.popup.close();
                                                }
                                            }
                                        }
                                    }
                                ),
                                new BX.PopupWindowButtonLink(
                                    {
                                        text: 'Отмена',
                                        className: "popup-window-button-link-cancel",
                                        events: {
                                            click: function () {
                                                _this.popup.close();
                                            }
                                        }
                                    }
                                )
                            ]
                        }
                    );
                    _this.popup.show();
                }
                else {

                }
            }
        });
    };

    $.Studiobit.Base.FormPerms.prototype.getHtmlSettings = function(result) {
        var _this = this;
        _this.formSettings = $('<form class="form-field-settings" method="post"></form>');
        var permsTable = $('<table border="0" width="100%">' +
            '<thead>' +
            '<tr> ' +
            '<th width="50%" align="left">Роль</th> ' +
            '<th width="16%" align="left">Чтение</th> ' +
            '<th width="16%" align="left">Редактирование</th> ' +
            '<th width="16%" align="left">Добавление</th> ' +
            '</tr> ' +
            '</thead>' +
            '<tbody></tbody>' +
            '</table>');

        $.each(result.roles, function(){
            var role = this;
            var tr = $('<tr><td>' + role.NAME + '</td><td class="perm-read"></td><td class="perm-write"></td><td class="perm-add"></td></tr>');

            var inputHiddenRead = $('<input type="hidden" name="perms[' + role.ID + '][read]" value="N" />');
            var inputRead = $('<input type="checkbox" name="perms[' + role.ID + '][read]" value="Y" />');
            if(result.perms.hasOwnProperty(role.ID)){
                if(result.perms[role.ID].indexOf('r') !== -1)
                    inputRead.prop('checked', true);
            }
            else{
                inputRead.prop('checked', true);
            }

            inputRead.on('click', function(){
                if(!$(this).is(':checked')){
                    inputWrite.prop('checked', false);
                }
            });

            $('.perm-read', tr).append(inputHiddenRead);
            $('.perm-read', tr).append(inputRead);

            var inputHiddenWrite = $('<input type="hidden" name="perms[' + role.ID + '][write]" value="N" />');
            var inputWrite = $('<input type="checkbox" name="perms[' + role.ID + '][write]" value="Y" />');
            if(result.perms.hasOwnProperty(role.ID)){
                if(result.perms[role.ID].indexOf('w') !== -1)
                    inputWrite.prop('checked', true);
            }
            else{
                inputWrite.prop('checked', true);
            }

            inputWrite.on('click', function(){
                if($(this).is(':checked')){
                    inputRead.prop('checked', true);
                }
            });

            $('.perm-write', tr).append(inputHiddenWrite);
            $('.perm-write', tr).append(inputWrite);

            var inputHiddenAdd = $('<input type="hidden" name="perms[' + role.ID + '][add]" value="N" />');
            var inputAdd = $('<input type="checkbox" name="perms[' + role.ID + '][add]" value="Y" />');
            if(result.perms.hasOwnProperty(role.ID)){
                if(result.perms[role.ID].indexOf('a') !== -1)
                    inputAdd.prop('checked', true);
            }
            else{
                inputAdd.prop('checked', true);
            }

            inputAdd.on('click', function(){
                if($(this).is(':checked')){
                    inputRead.prop('checked', true);
                }
            });

            $('.perm-add', tr).append(inputHiddenAdd);
            $('.perm-add', tr).append(inputAdd);

            $('tbody', permsTable).append(tr);
        });

        _this.formSettings.append(permsTable);

        return _this.formSettings.get(0);
    };

    $.Studiobit.Base.FormPerms.prototype.initFields = function() {
        var _this = this;
        if(!$.isEmptyObject(this.fields)) {
            $.each(this.fields, function(){
                var field = this;
                if(field.type == 'user' && !field.editable){
                    _this.disableUserField(field);
                }
            });
        }
    };

    $.Studiobit.Base.FormPerms.prototype.disableUserField = function(field) {
        var monitoring = function(){
            $('div[data-cid="' + field.name + '"] .crm-widget-employee-change').remove();
        };

        window.setInterval(function(){
            monitoring();
        }, 500);
    };
});