$(function() {
    if(!$.Studiobit)
        $.Studiobit = {};

    $.Studiobit.loader = function(show, wrapper, sClass, bOverflow)
    {
        var loader = $('.studiobit-loader-container', wrapper);
        var overflow = $('.studiobit-loader-overflow', wrapper);
        sClass = sClass || '';
        bOverflow = bOverflow | true;

        if(!loader.length){
            wrapper.css({position: 'relative'});
            
            loader = $('<div class="studiobit-loader-container ' + sClass + '">' +
                '<svg class="studiobit-loader-circular" viewBox="25 25 50 50">' +
                '<circle class="studiobit-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10">' +
                '</circle>' +
                '</svg>' +
                '</div>');

            if(bOverflow){
                overflow = $('<div class="studiobit-loader-overflow"></div>');
                wrapper.append(overflow);
            }
            wrapper.append(loader);
        }

        if(show) {
            if(sClass != '') {
                loader.removeAttr('class');
                loader.addClass('studiobit-loader-container');
                loader.addClass(sClass);
            }
            overflow.show();
            loader.show();
        }
        else {
            loader.hide();
            overflow.hide();
        }
    }
});