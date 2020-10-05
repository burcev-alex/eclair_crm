$(function() {
    if(!$.Studiobit)
		$.Studiobit = {};

    if(!$.Studiobit.Project)
        $.Studiobit.Project = {};
	
	
	$.Studiobit.Project.Favorite = function(params)
    {
		//параметры по умолчанию
		this.params = {};

        if(params)
            $.extend(this.params, params);

        this.init();
	};
	
	$.Studiobit.Project.Favorite.prototype = {
        init: function(){
            var _this = this;

            this.prepareEntity();

            if(_this.entityId){
                $.get(
                    '/ajax/project/favorite/status/',
                    {
                        sessid: BX.bitrix_sessid(),
                        entity_id: _this.entityId,
                        entity_type: _this.entityType
                    },
                    function(result) {
                        if (result) {
                            if (result.success) {
                                if (result.data) {
                                    if(result.data == 'favorite'){
                                        _this.addButton(true);
                                    }
                                    else{
                                        _this.addButton(false);
                                    }
                                }

                                _this.addLink(false);
                            }
                        }
                    }
                );
            }
		},

        addButton: function(bEntityInFavorite) {
            var _this = this;
            var btn = $('<span id="add-to-favorite" class="ui-btn ui-btn-xs ui-btn-round ui-btn-secondary"></span>');

            if(bEntityInFavorite){
                btn.text('Удалить из избранного');
                btn.addClass('ui-btn-icon-remove').removeClass('ui-btn-icon-add');
            }
            else{
                btn.text('Добавить в избранное');
                btn.addClass('ui-btn-icon-add').removeClass('ui-btn-icon-remove');
            }

            btn.on('click', function(){
                btn.addClass('ui-btn-clock');

                var url = '/ajax/project/favorite/add/';

                if(bEntityInFavorite){
                    url = '/ajax/project/favorite/remove/';
                }

                $.get(
                    url,
                    {
                        sessid: BX.bitrix_sessid(),
                        entity_id: _this.entityId,
                        entity_type: _this.entityType
                    },
                    function(result){
                        btn.removeClass('ui-btn-clock');

                        if(result) {
                            if(result.success) {
                                if(result.data) {
                                    top.BX.UI.Notification.Center.notify({
                                        autoHideDelay: 5000,
                                        content: result.data
                                    });
                                }

                                bEntityInFavorite = !bEntityInFavorite;

                                if(bEntityInFavorite){
                                    btn.text('Удалить из избранного');
                                    btn.addClass('ui-btn-icon-remove').removeClass('ui-btn-icon-add');
                                }
                                else{
                                    btn.text('Добавить в избранное');
                                    btn.addClass('ui-btn-icon-add').removeClass('ui-btn-icon-remove');
                                }
                            }
                        }
                    }
                );

                return false;
            });

            $('.pagetitle-below').append(btn);
            $('#pagetitle_sub').append(btn);
        },

        addLink: function() {
            var _this = this;
            var btn = $('<a href="/crm/favorite/" id="link-favorite" class="ui-btn ui-btn-xs ui-btn-round ui-btn-light-border ui-btn-icon-list">Избранное</a>');

            $('.pagetitle-below').append(btn);
            $('#pagetitle_sub').append(btn);
        },

        prepareEntity: function() {
            this.entityId = 0;
            this.entityType = '';
            if (matches = window.location.href.match(/\/crm\/contact\/details\/([\d]+)\//i)) {
                this.entityId = parseInt(matches[1]);
                this.entityType = 'CONTACT';
            }

            if (matches = window.location.href.match(/\/crm\/deal\/details\/([\d]+)\//i)) {
                this.entityId = parseInt(matches[1]);
                this.entityType = 'DEAL';
            }

            if (matches = window.location.href.match(/\/crm\/company\/details\/([\d]+)\//i)) {
                this.entityId = parseInt(matches[1]);
                this.entityType = 'COMPANY';
            }

            if (matches = window.location.href.match(/\/crm\/lead\/details\/([\d]+)\//i)) {
                this.entityId = parseInt(matches[1]);
                this.entityType = 'LEAD';
            }
        }
	};

    $.Studiobit.Project.favorite = new $.Studiobit.Project.Favorite();
	
});