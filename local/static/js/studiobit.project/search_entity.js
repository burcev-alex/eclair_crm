$(function(){
    if(!$.Studiobit)
        $.Studiobit = {};

    if(!$.Studiobit.Project)
        $.Studiobit.Project = {};

    $.Studiobit.Project.searchEntity = function(options){
        this.options = options;
        this.editor = BX.Crm.EntityEditor.getDefault();
        this.ajaxUrl = '';
        this.init();
    };

    $.Studiobit.Project.searchEntity.prototype.init = function(){
        this.initEvents();
        this.addButtons();
        this.emptyTable();
        this.updateButtons(false);
    };

    $.Studiobit.Project.searchEntity.prototype.getWrap = function(){
        return $('#crm-entity-search');
    };

    $.Studiobit.Project.searchEntity.prototype.getForm = function(){
        return $('form', this.getWrap());
    };

    $.Studiobit.Project.searchEntity.prototype.initEvents = function(){
        var self = this;

        $('.crm-entity-section-tab-link').each(function () {
            BX.unbindAll(this);
        });
    };

    $.Studiobit.Project.searchEntity.prototype.addButtons = function(){
        var self = this;

        var control = $('.ui-entity-section-control');
        if (control) {

            $('.ui-btn-success', control).hide();
            self.btnNext = $('<a class="ui-btn ui-btn-primary">Продолжить</a>');
            control.prepend(self.btnNext);

            self.btnNext.on('click', function () {
                if(!self.btnNext.hasClass('ui-btn-disabled')) {
                    $('.ui-btn-success', control).show();
                    self.btnNext.hide();

                    self.openMainTab();

                    var manager = BX.Crm.EntityDetailManager.get(self.options.guid);
                    manager.onTabOpenRequest('main');
                }

                return false;
            });

			$('.ui-entity-section-control').parents('.ui-entity-wrap').addClass('crm-section-control-active');

        }
    };

    $.Studiobit.Project.searchEntity.prototype.search = function(reset){
        var self = this;

        var form = this.getForm();

        var input = $('input[type="text"]', form);
        var query = input.val();
        query = query.trim();

        if(this.timerId) {
            clearTimeout(this.timerId);
            this.timerId = 0;
        }

        if(query.length >= 2)
        {
            this.timerId = setTimeout(function() {
                if(query !== self.lastQuery || reset) {
                    self.lastQuery = query;

                    var resultWrap = $('#crm-entity-search-result', self.getWrap());

                    $.Studiobit.loader(true, resultWrap, '', true);

                    $.ajax({
                        type: 'GET',
                        url: self.ajaxUrl,
                        data: form.serialize(),
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function (data) {
                            if (data) {
                                if (data.result == 'success') {
                                    $.Studiobit.loader(false, resultWrap, '', true);
                                    self.drawTable(data.items);
                                }
                            }
                        }
                    });
                }
            }, 1000);
        }
        else{
            self.emptyTable();
        }
    };

    $.Studiobit.Project.searchEntity.prototype.drawTable = function(items){
    };

    $.Studiobit.Project.searchEntity.prototype.emptyTable = function(){
        $('#crm-entity-search-result', this.getWrap()).html('<span class="crm-entity-search-result-empty">введите текст запроса для поиска</span>');
    };

    $.Studiobit.Project.searchEntity.prototype.updateButtons = function(empty){
        if(empty){
            this.btnNext.removeClass('ui-btn-disabled');
        }
        else{
            this.btnNext.addClass('ui-btn-disabled');
        }
    };

    $.Studiobit.Project.searchEntity.prototype.open = function(url)
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

    $.Studiobit.Project.searchEntity.prototype.openMainTab = function(){
    };
});
