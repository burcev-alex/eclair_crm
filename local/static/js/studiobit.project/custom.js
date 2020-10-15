$(function() {
    if(!$.Studiobit)
		$.Studiobit = {};

    if(!$.Studiobit.Project)
        $.Studiobit.Project = {};

    $.Studiobit.Project.Custom = function(){
        var context = this;

        // запрещаяем изменять стадию сделки в списке
        context.dealsListStageBarDisable();

        $('.crm-entity-section-status-step .crm-entity-section-status-step-item').on('click', function(event){
            var item = $(this);
            var parent = item.parent();
            var stage = parent.data('id');

            if(typeof stage == 'undefined')
                return false;

            if(item.data('bCanChangeStage') !== true && stage.indexOf('WON') == -1)
            {
                var step = item.parent();
                var stageId = step.data('id');
                var dealId = context.getDealId();

                if (dealId && stageId) {
                    item.data('bCanChangeStage', false);

                    event.preventDefault();
                    event.stopPropagation();

                    $.get('/ajax/project/deal/checkCanChangeStage/', {id: dealId, stage: stageId}, function (xml) {
                        if (xml) {
                            if (xml.success) {
                                item.data('bCanChangeStage', true);
                                item.trigger('click');
                            }
                            else {
                                item.data('bCanChangeStage', false);
                                context.showError(xml.message);
                            }
                        }
                    });

                    return false;
                }
            }
            else
            {
                item.data('bCanChangeStage', false);
            }
        });
    };

    $.Studiobit.Project.Custom.prototype.getDealId = function(url){
        url = url || window.location.href;
        if (matches = url.match(/\/crm\/deal\/details\/([\d]+)\//i)) {
            return parseInt(matches[1]);
        }

        return 0;
    };

    $.Studiobit.Project.Custom.prototype.dealsListStageBarDisable = function(){
        $('.crm-list-stage-bar-block, .crm-list-stage-bar-block > .crm-list-stage-bar-btn').on('click', function(event){
            event.preventDefault();
            event.stopPropagation();
            return false;
        });

        BX.addCustomEvent('BX.Main.grid:paramsUpdated', function(){
            $('.crm-list-stage-bar-block, .crm-list-stage-bar-block > .crm-list-stage-bar-btn').on('click', function(event){
                event.preventDefault();
                event.stopPropagation();
                return false;
            });
        });
    };

    $.Studiobit.Project.Custom.prototype.open = function(url){
        url = BX.util.add_url_param(url, {
            'IFRAME': 'Y',
            'IFRAME_TYPE': 'SIDE_SLIDER'
        });

        window.top.BX.Bitrix24.Slider.destroy(url);
        window.top.BX.Bitrix24.Slider.open(
            url,
            {}
        );
    };

    $.Studiobit.Project.Custom.prototype.showError = function(message){
        var errorDialog = new BX.PopupWindow(
            'message-error',
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

    new $.Studiobit.Project.Custom();
});