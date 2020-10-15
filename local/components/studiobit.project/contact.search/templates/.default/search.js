$(function(){
    if(!$.Studiobit)
        $.Studiobit = {};

    if(!$.Studiobit.Project)
        $.Studiobit.Project = {};

    $.Studiobit.Project.searchContact = function(options){
        this.options = options;
        this.editor = BX.Crm.EntityEditor.getDefault();
        this.ajaxUrl = '/ajax/project/contact/search/?action=list';
        this.init();
    };

    $.Studiobit.Project.searchContact.prototype = Object.create($.Studiobit.Project.searchEntity.prototype);
    $.Studiobit.Project.searchContact.prototype.constructor = $.Studiobit.Project.searchEntity;


    $.Studiobit.Project.searchContact.prototype.initEvents = function(){
        var self = this;

        $.Studiobit.Project.searchEntity.prototype.initEvents.call(this);

        var input = $('input[type="text"]', this.getWrap());
        
        $('input[type="text"]').on('change keyup', function(){
            self.search();
        });

        //var listRU = $.masksSort($.masksLoad("/local/static/js/plugins/inputmask-multi/data/phones-ru.json"), ['#'], /[0-9]|#/, "mask");
        var listCountries = $.masksSort($.masksLoad("/local/static/js/plugins/inputmask-multi/data/phone-codes.json"), ['#'], /[0-9]|#/, "mask");
        
        var maskOpts = {
            inputmask: {
                definitions: {
                    '#': {
                        validator: "[0-9]",
                        cardinality: 1
                    }
                },
                showMaskOnHover: false,
                autoUnmask: true,
                clearMaskOnLostFocus: false
            },
            match: /[0-9]/,
            replace: '#',
            listKey: "mask",
            list: listCountries,
            onMaskChange: function(){

            }
        };

        $('input[type="radio"]', this.getWrap()).on('click', function(){
            if(this.value == 'PHONE'){
                input.attr('placeholder', '')
                input.inputmasks(maskOpts);
            }
            else{
                input.inputmasks("remove");
                input.attr('placeholder', 'Фамилия Имя Отчество')
            }

            self.search(true);
        });

        input.attr('placeholder', '')
        input.inputmasks(maskOpts);
    };

    $.Studiobit.Project.searchContact.prototype.drawTable = function(items){
        var self = this;

        var table = $(
        '<table>' +
            '<thead>' +
                '<tr>' +
                    '<th><span>ID</span></th>' +
                    '<th><span>ФИО</span></th>' +
                    '<th><span>Телефон</span></th>' +
                    '<th><span>Ответственный</span></th>' +
                    '<th></th>' +
                '</tr>' +
            '</thead>' +
            '<tbody></tbody>' +
        '</table>');

        var tbody = $('tbody', table);
        
        $.each(items, function(id, item){
            var tr = $('<tr></tr>');
            
            var td = $('<td>' + item.ID + '</td>');
            tr.append(td);

            td = $('<td>' + item.FULL_NAME + '</td>');
            tr.append(td);

            td = $('<td>' + item.PHONES + '</td>');
            tr.append(td);

            td = $('<td>' + item.RESPONSIBLE + '</td>');
            tr.append(td);

            td = $('<td></td>');
            var link = $('<a class="ui-btn">Перейти</a>');
            
            link.on('click', function(){
                if(item.CAN_VIEW){
                    self.open(item.URL);
                }
                else{
                    var popup = BX.PopupWindowManager.create("studiobit-serach-entity-access-message", null, {
                        content: 'Недостаточно прав, пожалуйста, обратитесь к администратору или к ответственному менеджеру',
                        darkMode: true,
                        autoHide: true
                    });

                    popup.show();
                }
            });
            
            td.append(link);
            tr.append(td);

            tbody.append(tr);
        });
        
        if(!$('tr', tbody).length)
        {
            var tr = $('<tr class="crm-entity-search-result-empty"><td colspan="4">Совпадений не найдено</td>></tr>');
            tbody.append(tr);

            this.updateButtons(true);
        }
        else{
            this.updateButtons(false);
        }
        
        $('#crm-entity-search-result', this.getWrap()).empty().append(table);
    };

    $.Studiobit.Project.searchContact.prototype.openMainTab = function(){
        var form = this.getForm();
        var type = $('input[name="TYPE"]:checked', form).val();
        var input = $('input[type="text"]', form);

        var query = '';
        if(type == 'PHONE'){
            query = input.inputmask('unmaskedvalue');

            var phone = $('div[data-cid="PHONE"] input[type="text"]:first');
            phone.val('+' + query).trigger('keyup');
            phone.prop('readonly', true);
            var field = phone.closest('.crm-entity-widget-content-block-field-container');
            if(field){
                $('.crm-entity-widget-content-remove-block', field).remove();
            }
        }
        else{
            var last_name = '', name = '', second_name = '';
            query = input.val();
            var parts = query.split(' ');

            if(parts[0]) {
                last_name = parts[0];
                last_name = last_name.trim();
            }

            if(parts[1]) {
                name = parts[1];
                name = name.trim();
            }

            if(parts[2]) {
                second_name = parts[2];
                second_name = second_name.trim();
            }

            $('div[data-cid="LAST_NAME"] input[type="text"]:first').val(last_name);
            $('div[data-cid="NAME"] input[type="text"]:first').val(name);
            $('div[data-cid="SECOND_NAME"] input[type="text"]:first').val(second_name);
        }
    };
});