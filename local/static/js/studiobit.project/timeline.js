$(function() {
    if(!$.Studiobit)
		$.Studiobit = {};

    if(!$.Studiobit.Project)
        $.Studiobit.Project = {};


	$.Studiobit.Project.Timeline = function(params)
    {
		//параметры по умолчанию
		this.params = {};

        if(params)
            $.extend(this.params, params);

        this.init();
	}

	$.Studiobit.Project.Timeline.prototype = {
		init: function(content){
            if(BX.CrmTimelineManager) {
                this.customizePannedActivity();
                //this.customizeCommentActivity();
            }
		},

        //кастомизация комментариев в ленте
        customizeCommentActivity: function()
        {
            if(!$.Studiobit.Project.Define.bAdmin) {
                //запрещаем редактирование комментариев для не админов
                if (BX.CrmHistoryItemComment) {
                    BX.CrmHistoryItemComment.prototype.switchToEditMode = function () {
                    }
                }

                $.each(BX.CrmTimelineManager.instances, function (key, instance) {
                    $.each(instance.getHistory()._items, function (key, item) {
                        if (item instanceof BX.CrmHistoryItemComment) {
                            item.refreshLayout();
                        }
                    });

                    $.each(instance._fixedHistory._items, function (key, item) {
                        if (item instanceof BX.CrmHistoryItemComment) {
                            item.refreshLayout();
                        }
                    });
                });

                //перехватывает показ контекстного меню и выпиливаем кнопки Изменить и Удалить
                BX.addCustomEvent(window, 'onPopupShow', function (popup) {
                    var id = 0;
                    if (matches = popup.uniquePopupId.match(/menu-popup-([\d]+)/i)) {
                        id = parseInt(matches[1]);
                    }

                    if (id) {
                        $.each(BX.CrmTimelineManager.instances, function (key, instance) {
                            $.each(instance.getHistory()._items, function (key, item) {
                                if (item.getId() == id) {
                                    if (item instanceof BX.CrmHistoryItemComment) {
                                        var container = $(popup.contentContainer);
                                        $('span.menu-popup-item-text:contains("Изменить")', container).closest('.menu-popup-item').remove();
                                        $('span.menu-popup-item-text:contains("Удалить")', container).closest('.menu-popup-item').remove();
                                        return false;
                                    }
                                }
                            });
                        });
                    }
                });
            }
        },

        //кастомизация активных дел в ленте
		customizePannedActivity: function()
        {
            if(BX.CrmScheduleItemActivity){
                BX.CrmScheduleItemActivity.prototype.prepareContentOriginal = BX.CrmScheduleItemActivity.prototype.prepareContent;

                BX.CrmScheduleItemActivity.prototype.prepareContent = function(options)
                {
                    var _this = this;
                    var wrap = this.prepareContentOriginal(options);

                    var additionals = $(
                        '<div class="studiobit-timeline-item-additionals">' +
                            '<div class="studiobit-timeline-item-additionals-important">' +
                            '</div>' +
                            '<div class="studiobit-timeline-item-additionals-remind"></div>' +
                            '<div class="studiobit-timeline-item-additionals-button">' +
                                '<a class="ui-btn ui-btn-xs ui-btn-round ui-btn-success">Сохранить</a>' +
                            '</div>' +
                        '</div>');

                    var inputImportant = $('<input type="checkbox" name="important" value="Y">') ;
                    var labelImportant = $('<label></label>');
                    labelImportant.append(inputImportant);
                    labelImportant.append('важное');

                    $('.studiobit-timeline-item-additionals-important', additionals).append(labelImportant);

                    var inputRemind = $('<input type="checkbox" name="remind" value="Y">') ;
                    var labelRemind = $('<label></label>');
                    labelRemind.append(inputRemind);
                    labelRemind.append('напомнить');

                    var inputDate = $('<input type="text" class="ui-field" name="remind_date" placeholder="дд.мм.гггг" />');
                    var resultDate = new BX.MaskedInput({
                        mask: '99.99.9999', // устанавливаем маску
                        input: inputDate[0],
                        placeholder: '_',
                    });

                    var calendar = BX.calendar.get();
                    var now = new Date();

                    inputDate.on('click', function(){
                        calendar.Show({node: $(this).parent().get(0), field: this, bTime: false});
                    });

                    //обновляем значение даты в календаре, при редактирировании текстового поля
                    inputDate.on('keyup', function(){
                        var value = inputDate.val();
                        value = value.replace(/_/g, '');
                        var parts = value.split('.');

                        if(parts[0] > 0 && parts[0] <= 31){
                            calendar.SetDay(parts[0]);
                        }
                        else{
                            calendar.SetDay(now.getDay());
                        }

                        if(parts[1] > 0 && parts[1] <= 12){
                            calendar.SetMonth(parts[1] - 1);
                        }
                        else{
                            calendar.SetMonth(now.getMonth());
                        }

                        if(parts[2]){
                            calendar.SetYear(parts[2]);
                        }
                        else{
                            calendar.SetYear(now.getFullYear());
                        }
                    });

                    var inputTime = $('<input type="text" class="ui-field" name="remind_time" placeholder="чч:мм" />');
                    new BX.MaskedInput({
                        mask: '99:99', // устанавливаем маску
                        input: inputTime[0],
                        placeholder: '_'
                    });

                    $('.studiobit-timeline-item-additionals-remind', additionals).append(labelRemind);
                    $('.studiobit-timeline-item-additionals-remind', additionals).append(inputDate);
                    $('.studiobit-timeline-item-additionals-remind', additionals).append(inputTime);

                    var checkRemind = function(){
                        if(inputRemind.prop('checked')){
                            inputDate.show();
                            inputTime.show();
                        }
                        else{
                            inputDate.hide();
                            inputTime.hide();
                        }
                    };

                    inputRemind.on('click', function(){
                        checkRemind();
                    });

                    checkRemind();

                    $('.ui-btn', additionals).on('click', function(){
                        var bSend = true;
                        var btn = $(this);

                        $('.studiobit-timeline-item-additionals-field-error', additionals).removeClass(
                            'studiobit-timeline-item-additionals-field-error'
                        );

                        var important = inputImportant.prop('checked') ? 'Y' : 'N';
                        var remind = inputRemind.prop('checked') ? 'Y' : 'N';
                        var date = inputDate.val();
                        var time = inputTime.val();

                        if(remind == 'Y'){
                            if(date == ''){
                                inputDate.addClass('studiobit-timeline-item-additionals-field-error');
                                bSend = false;
                            }

                            if(time == ''){
                                inputTime.addClass('studiobit-timeline-item-additionals-field-error');
                                bSend = false;
                            }
                        }

                        if(bSend){
                            btn.addClass('ui-btn-clock');

                            $.get(
                                '/ajax/project/activity/timeline/',
                                {
                                    sessid: BX.bitrix_sessid(),
                                    id: _this.getSourceId(),
                                    important: important,
                                    remind: remind,
                                    date: date,
                                    time: time
                                },
                                function(result){
                                    btn.removeClass('ui-btn-clock');

                                    if(result) {
                                        if(result.success) {
                                            if(result.data) {
                                                $.each(result.data, function(key, message){
                                                    top.BX.UI.Notification.Center.notify({
                                                        autoHideDelay: 5000,
                                                        content: message
                                                    });
                                                });
                                            }
                                        }
                                    }
                                }
                            );
                        }

                        return false;
                    });

                    $(wrap).find('.crm-entity-stream-content-detail').append(additionals);

                    return wrap;
                };

                $.each(BX.CrmTimelineManager.instances, function(key, instance){
                    $.each(instance._schedule.getItems(), function(id, item){
                        item.refreshLayout();
                    });

                    if($.Studiobit.Project.Define.BusinessCardText !== '') {
                        var itemContainer = $('<a data-item-id="sms" data-item-title="Визитка" class="crm-entity-stream-section-new-action" href="#">Визитка</a>');
                        var smsContainer = $(instance._menuBar._container).find('a[data-item-id="sms"]');

                        itemContainer.insertAfter(smsContainer);

                        var item = BX.CrmTimelineMenuBarItem.create(
                            itemContainer.data("item-id"),
                            {
                                container: itemContainer[0],
                                owner: instance._menuBar
                            }
                        );
                        instance._menuBar._items.push(item);

                        var prevSmsText = '';
                        var smsTextarea = $('textarea.crm-entity-stream-content-new-sms-textarea');
                        itemContainer.on('click', function () {
                            prevSmsText = smsTextarea.val();
                            smsTextarea.val($.Studiobit.Project.Define.BusinessCardText);
                        });

                        smsContainer.on('click', function () {
                            smsTextarea.val(prevSmsText);
                        });
                    }
                });
            }
		}
	};

    $.Studiobit.Project.timeline = new $.Studiobit.Project.Timeline();

});
