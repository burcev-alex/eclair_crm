$(function(){
    if(!$.Studiobit)
        $.Studiobit = {};

    var defaultParams = {
        width: 900,
        height: 475,
        url: '/ajax/excel/import/',
        CLASS_NAME: 'Import'
    };
    
    $.Studiobit.Import = function(params){
        this.setParams(params);
        
        this.dialog = false;
        this.bImport = this.bStop = false;
    };

    $.Studiobit.Import.prototype.open = function(){
        if(this.create()){
            this.dialog.Show();
        }
    };

    $.Studiobit.Import.prototype.setParams = function(params){
        $.extend(defaultParams, params);

        this.params = defaultParams;
        this.url = defaultParams.url;
    };

    $.Studiobit.Import.prototype.run = function(){
        var context = this;
        var wrapper = $(context.dialog.DIV);
        var content = $('.bx-core-adm-dialog-content', wrapper);
        var form = $('form', wrapper);
        var result = $('#studiobit-import-result', wrapper);

        result.html('');

        var formData = new FormData(form[0]);

        form.hide();
        context.bStop = false;;

        $.ajax({
            url        : context.url + '?' + $.param(context.params) + '&RUN=Y',
            data       : formData,
            type       : 'POST',
            contentType: false,
            processData: false,
            success    : function (res) {
                var json = $.parseJSON(res);

                if(json.IMPORT == 'ERROR'){
                    result.html(json.MESSAGE);
                    form.show();
                }
                else
                {
                    var load = function(){
                        $.ajax({
                            url: context.url + '?' + $.param(context.params) + '&RUN=Y',
                            type: 'POST',
                            success: function (res) {
                                var json = $.parseJSON(res);

                                if(json.IMPORT == 'ERROR')
                                {
                                    result.html(json.MESSAGE);
                                }
                                else
                                {
                                    var new_progress = $(json.PROGRESS_HTML);
                                    var progress = $('#studiobit-import-progress', wrapper);
                                    if(!progress.length){
                                        progress = new_progress;
                                        result.append(progress);
                                    }
                                    else{
                                        progress.replaceWith(new_progress);
                                    }

                                    var log = $('.log', result);

                                    if(!log.length){
                                        log = $('<div class="log"></div>');
                                        result.append(log);
                                    }

                                    log.hide();

                                    var log_items = $(json.LOG);
                                    log.append(log_items);

                                    if(json.STATE == 'FINISH'){
                                        //progress.remove();
                                        log.show();
                                        form.show();
                                        BX.onCustomEvent(context, 'onImportFinish', []);
                                    }
                                    else if(!context.bStop){
                                        load();
                                    }
                                }
                            }
                        });
                    };

                    load();
                    context.bImport = true;
                }
            }
        });
    };

    $.Studiobit.Import.prototype.close = function(){
        if(context.dialog){
            context.dialog.Show();
        }
    };

    $.Studiobit.Import.prototype.create = function(){
        var context = this;
        if(context.dialog == false){
            context.dialog = new BX.CDialog({
                content_url: this.url + '?' + $.param(this.params),
                width: '900',
                height: '475',
                resizable: true

            });

            BX.addCustomEvent(context.dialog, 'onWindowRegister', function (eParams) {
                BX.onCustomEvent(context, 'onWindowRegister', eParams);
                $('input[name=load]').on('click', function (e) {
                    e.preventDefault();
                    context.run();
                });
            });

            BX.addCustomEvent(context.dialog, 'onWindowClose', function(eParams){
                context.bStop = true;
                BX.onCustomEvent(context, 'onWindowClose', [context.bImport, eParams]);
                context.dialog = false;
            });
        }
        
        return true;
    };
});