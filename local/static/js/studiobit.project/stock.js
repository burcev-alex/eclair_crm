$(function() {
    if(!$.Studiobit)
		$.Studiobit = {};

    if(!$.Studiobit.Project)
        $.Studiobit.Project = {};

    $.Studiobit.Project.Stock = function(){
        var _this = this;

        this.enabled = true;
        this.items = [];
        
        if(!this.inIframe()) {
            BX.addCustomEvent(
                window,
                "onPullEvent-studiobit.project",
                function (eventName, params) {
                    if (eventName == 'stock_update') {
                        _this.items = params.ITEMS;
                        _this.enabled = params.ENABLED;
                        if(_this.enabled)
                            _this.showWindow(!$.isEmptyObject(_this.items));
                    }
                }
            );

            this.load();
        }
    };
    
    $.Studiobit.Project.Stock.prototype.getWindowContent = function(showEsc){
        var _this = this;

        var content = $(
            '<div id="stock-message">' +
            '</div>'
        );

        var scrollWrap = $(
            '<div class="stock-message-items">' +
            '</div>'
        );

        var table = $(
            '<table>' +
                '<thead>' +
                    '<tr><td>Номер</td><td>Контакт</td><td>Дата и время</td><td>Действия</td></tr>' +
                '</thead>' +
                '<tbody></tbody>' +
            '</table>'
        );

        var tbody = $('tbody', table);

        if(!$.isEmptyObject(this.items)) {
            $.each(this.items, function () {
                var row = $(
                    '<tr>' +
                    '<td data-type="number">' + this.ID + '</td>' +
                    '<td data-type="title"><a href="' + this.URL + '">' + this.TITLE + '</a></td>' +
                    '<td data-type="date">' + this.DATE + '</td>' +
                    '<td data-type="actions"><a href="' + this.URL + '" class="ui-btn ui-btn-xs ui-btn-round ui-btn-success" data-id="' + this.ID + '">Принять</a></td>' +
                    '</tr>'
                );

                tbody.append(row);
            });
        }
        else
        {
            var row = $(
                '<tr>' +
                    '<td colspan="4" data-type="empty">Список контактов пуст</td>' +
                '</tr>'
            );

            tbody.append(row);
        }

        scrollWrap.append(table);
        content.append(scrollWrap);

        var btnOff = $('<a class="ui-btn ui-btn-primary">Не беспокоить</a>');

        btnOff.on('click', function () {
            if (_this.dialog) {
                _this.dialog.close();
                _this.dialog.destroy();
                _this.dialog = false;
                _this.bShowWindow = false;
            }

            _this.off();

            return false;
        });

        $('a', content).not('.ui-btn').on('click', function () {
            _this.slide($(this).prop('href'));
            return false;
        });

        $('a.ui-btn', content).on('click', function () {
            _this.apply($(this).data('id'));
            return false;
        });

        content.append(btnOff);

        return content;
    };

    $.Studiobit.Project.Stock.prototype.slide = function(url)
    {
        url = BX.util.add_url_param(url, {
            "IFRAME": "Y",
            "IFRAME_TYPE": "SIDE_SLIDER"
        });
        window.top.BX.Bitrix24.Slider.destroy(url);
        window.top.BX.Bitrix24.Slider.open(
            url
        );
    };

    $.Studiobit.Project.Stock.prototype.showWindow = function(bShow){
        var _this = this;
        var content = this.getWindowContent();
        
        if(!this.bShowWindow){
            if(content) {
                this.bShowWindow = true;
                this.dialog = new BX.PopupWindow(
                    'stock-dialog',
                    window,
                    {
                        content: content[0],
                        titleBar: 'Не обработанные контакты на сегодня',
                        autoHide: false,
                        zIndex: 1099,
                        overlay: 0.4,
                        closeIcon: true,
                        closeByEsc: true,
                        events: {
                            onClose: function () {
                                _this.bShowWindow = false;
                            }
                        }
                    }
                );

                if(bShow)
                    this.dialog.show();
            }
        }
        else if(this.dialog)
        {
            if(content){
                $('#stock-message').replaceWith(content);

                if(bShow)
                    this.dialog.show();
            }
        }
    };

    $.Studiobit.Project.Stock.prototype.showButton = function(){
        var _this = this;
        
        var container = $(
            '<div id="studiobit-stock-button">' +
                '<button class="ui-btn ui-btn-primary">Необработанные контакты</button>' +
            '</div>');
        
        $('button', container).on('click', function(){
            _this.showWindow(true);
            return false;
        });
        
        $('body').append(container);
    };

    $.Studiobit.Project.Stock.prototype.load = function()
    {
        var _this = this;

        $.get('/ajax/project/contact/getStock/', function(xml){
            if(xml)
            {
                if(xml.success)
                {
                    _this.items = xml.data.ITEMS;
                    _this.enabled = xml.data.ENABLED;

                    if(_this.enabled)
                        _this.showWindow(!$.isEmptyObject(_this.items));
                    
                    _this.showButton();
                }
            }
        });
    };

    $.Studiobit.Project.Stock.prototype.off = function()
    {
        $.get('/ajax/project/contact/offStock/');
    };

    $.Studiobit.Project.Stock.prototype.apply = function(id)
    {
        var _this = this;

        var item = this.items[id];
        
        if(item) {
            $.get('/ajax/project/contact/applyStock/', {id: id}, function (params) {
                
                _this.slide(item.URL);
            });
        }
    };

    $.Studiobit.Project.Stock.prototype.inIframe = function() {
        try {
            return !Object.is(window.self, window.top);
        } catch (e) {
            return true;
        }
    };

    $.Studiobit.Project.StockManager = new $.Studiobit.Project.Stock();
});