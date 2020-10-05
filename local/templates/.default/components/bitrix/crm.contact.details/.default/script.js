$(function() {
    if (!$.Studiobit)
        $.Studiobit = {};

    if (!$.Studiobit.Project)
        $.Studiobit.Project = {};

    $.Studiobit.Project.ContactCard = function (options) {
        this.options = options;
        this.init();
    };

    $.Studiobit.Project.ContactCard.prototype.init = function(){
        var _this = this;

        window.setInterval(function(){
            _this.monitoringFieldType();
        }, 500);

        this.containerToggle();
        // this.initButtonBooking();
        this.setFieldsRequired();
        this.initEvents();
    };

    $.Studiobit.Project.ContactCard.prototype.initEvents = function(){
        var _this = this;

        if(this.options.entityId === 0)
        {
            BX.addCustomEvent('BX.Crm.EntityEditor:onSave', BX.delegate(function (self, eventArgs) {
                console.log('BX.Crm.EntityEditor:onSave');
                var invalid = false;
                var obError = false;

                $('div[data-required="true"]').each(function () {
                    var wrap = $(this);
                    var cid = wrap.data('cid');
                    if (cid) {
                        if (!_this.checkRequiredField(cid)) {
                            invalid = true;
                            if(!obError){
                                obError = wrap;
                            }
                        }
                    }
                });

                if(invalid) {
                    eventArgs["cancel"] = 'ERROR';

                    //прокрутка к ошибке
                    $([document.documentElement, document.body]).animate({
                        scrollTop: obError.offset().top - 100
                    }, 500);
                }
            }, this));
        }
    };

    /*проверка заполненности обязательного поля*/
    $.Studiobit.Project.ContactCard.prototype.checkRequiredField = function(fieldName){
        var result = false;
        var errorContainer = BX.create(
            "div",
            {attrs: {className: "crm-entity-widget-content-error-text"}}
        );
        errorContainer.innerHTML = 'Поле обязательно к заполнению!';

        var _wrapper = $('.crm-entity-widget-content-block[data-cid="' + fieldName + '"]');

        var value = '';

        var input = _wrapper.find('input[type="hidden"]');
        if(input.length){
            value = input.val();
        }
        else{
            var option = _wrapper.find('option:selected');
            if(option.length){
                value = option.val();
            }
            else{
                input = _wrapper.find('input[type="text"]');
                if(input.length){
                    value = input.val();
                }
            }
        }

        if(parseInt(value.length) === 0) {
            if (_wrapper.find('.crm-entity-widget-content-error-text').length === 0) {
                _wrapper.addClass("crm-entity-widget-content-error");
                _wrapper.append(errorContainer);
            }
        }
        else{
            result = true;
            _wrapper.find('.crm-entity-widget-content-error-text').remove();
            _wrapper.removeClass('crm-entity-widget-content-error');
        }

        return result;
    };

    /*снятие/установка обязательности для поля*/
    $.Studiobit.Project.ContactCard.prototype.setFieldRequired = function(fieldName, bSet){
        var wrap = $('div[data-cid="' + fieldName + '"]');

        if(wrap.length){
            if(bSet){
                wrap.attr('data-required', 'true');
            }
            else{
                wrap.attr('data-required', 'false');
            }
        }
    };

    /*обязательные поляпо умолчанию*/
    $.Studiobit.Project.ContactCard.prototype.setFieldsRequired = function(){
        var _this = this;
        $.each(['PHONE', 'UF_CRM_CHANNEL', 'UF_CRM_SOURCE'], function(key, name){
            _this.setFieldRequired(name, true);
        });
    };

    /*изменение типа контакта*/
    $.Studiobit.Project.ContactCard.prototype.monitoringFieldType = function(){
        var wrapCompany = $('div[data-cid="COMPANY"]');
        var wrap = $('div[data-cid="TYPE_ID"]');
        var type = '';
        var input = $('input[name="TYPE_ID"]', wrap);
        if(input.length)
        {
            type = input.val();
        }
        else{
            type = $('.crm-entity-widget-content-block-inner-text', wrap).text();
        }

        if(type == 'Клиенты' || type == 'CLIENT'){
            wrapCompany.hide();
            this.setFieldRequired('COMPANY', false);
        }
        else
        {
            wrapCompany.show();
            this.setFieldRequired('COMPANY', true);
        }
    };

    /*сворачиваем всеблоки кроме главного*/
    $.Studiobit.Project.ContactCard.prototype.containerToggle = function(){
        var context = this;

        if(this.options.entityId === 0){
            // новый контакт
            var count = 0;
            $('#contact_0_details_tabs div[data-tab-id="main"] .crm-entity-widget-content').each(function(){
                var self = $(this);
                if(count > 0){
                    self.hide();
                    var buttonToggle = $('<span class="crm-entity-widget-toggle-btn">Показать</span>');

                    if($(this).closest('.crm-entity-card-widget').length > 0) {
                        $(this).closest('.crm-entity-card-widget').find('.crm-entity-card-widget-title .crm-entity-widget-actions-block').append(buttonToggle);
                    }
                    else if($(this).closest('.crm-entity-card-widget-edit').length > 0) {
                        $(this).closest('.crm-entity-card-widget-edit').find('.crm-entity-card-widget-title .crm-entity-widget-actions-block').append(buttonToggle);
                    }

                    buttonToggle.on('click', function(){
                        self.toggle();
                        buttonToggle.text(self.css('display')==='none'?'Показать':'Скрыть');
                    });
                }
                count++;
            });
        }
    };

    /*кнопка Забронировать*/
    $.Studiobit.Project.ContactCard.prototype.initButtonBooking = function(){
        var _this = this;
        $('#add-booking').on('click', function(){
            _this.addBookingHandler();
            
            return false;
        });

        this.dialogSearch = new $.Studiobit.Matrix.ObjectSearch(
            BX.delegate(this.dialogSearchHandler, this),
            {
                hideAfterSelect: false
            }
        );
    };

    /*обработчик клика по кнопке Забронировать*/
    $.Studiobit.Project.ContactCard.prototype.addBookingHandler = function(){
        var _this = this;

        var select = $('<select name="deal_categoty"></select>');
        
        $.each(this.options.dealCategories, function(id, name){
            var option = $('<option value="' + id + '">' + name + '</option>');
            select.append(option);
        });

        var dialogCategory = new $.Studiobit.Dialog(
            {
                id: 'deal-categoty',
                type: 'confirm',
                title: 'Направление сделки',
                message: select.get(0).outerHTML,
                apply_button: 'Ok',
                cancel_button: 'Отмена'
            },
            function()
            {
                _this.deal_categoty = $('select[name="deal_categoty"] option:selected').val();
                _this.dialogSearch.show();
            }
        );

        dialogCategory.show();
    };

    /*обработчик выбора помещения*/
    $.Studiobit.Project.ContactCard.prototype.dialogSearchHandler = function(object) {
        var _this = this;

        _this.dialogSearch.hide();

        var confirmCreate = new $.Studiobit.Dialog(
            {
                id: 'deal-create',
                type: 'confirm',
                title: 'Создание сделки',
                message: 'Вы уверены что хотите создать новую сделку и забронировать помещение "' + object.title + '"?',
                apply_button: 'Да',
                cancel_button: 'Отмена'
            },
            function()
            {
                $.get(
                    '/ajax/project/deal/create/',
                    {
                        sessid: BX.bitrix_sessid(),
                        contact_id: _this.options.entityId,
                        category_id: _this.deal_categoty,
                        product_id: object.id
                    },
                    function(result) {
                        if(result) {
                            if(result.success) {
                                if(result.data) {
                                    _this.open(result.data);
                                }
                            }
                            else{
                                _this.showError(result.message);
                            }
                        }
                    }
                );
            },
            function(){
                _this.dialogSearch.show();
            }
        );

        confirmCreate.show();
    };

    /*октрытие нового окна*/
    $.Studiobit.Project.ContactCard.prototype.open = function(url)
    {
        url = BX.util.add_url_param(url, {
            'IFRAME': 'Y',
            'IFRAME_TYPE': 'SIDE_SLIDER'
        });

        BX.SidePanel.Instance.open(
            url,
            {}
        );
    };

    /*сообщение об ошибке*/
    $.Studiobit.Project.ContactCard.prototype.showError = function(message){
        var errorDialog = new BX.PopupWindow(
            'contact-detail-error',
            null,
            {
                autoHide: true,
                draggable: false,
                bindOptions: {forceBindPosition: true},
                closeByEsc: true,
                zIndex: 0,
                content: message,
                lightShadow: true,
                buttons: [
                    new BX.PopupWindowButtonLink(
                        {
                            text: 'Закрыть',
                            events: {
                                click: function () {
                                    errorDialog.close();
                                    errorDialog.destroy();
                                    errorDialog = null;
                                }
                            }
                        }
                    )
                ]
            }
        );

        errorDialog.show();
    };
});