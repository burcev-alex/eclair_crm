$(function(){
    if(!$.Studiobit)
        $.Studiobit = {};

    var defaultParams = {
        url: '/ajax/excel/export/',
        CLASS_NAME: 'Export'
    };
    
    $.Studiobit.Export = function(params){
        this.setParams(params);
    };

    $.Studiobit.Export.prototype.setParams = function(params){
        $.extend(defaultParams, params);

        this.params = defaultParams;
        this.url = defaultParams.url;
    };

    $.Studiobit.Export.prototype.run = function(type){
        jsUtils.Redirect([], this.url + '?' + $.param(this.params) + '&TYPE=' + type);
    };
});