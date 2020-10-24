$(document).ready(function(){


    // var search,iblock_id, mode, type, admin, lang, site;
    //
    // $('#ajaxInput').on('keyup',function(){
    //     search = $('#ajaxInput').val();
    //     iblock_id = $('#ajaxInputIblock').val();
    //     mode = 'SEARCH';
    //     type = 'ELEMENT';
    //     admin = 'Y';
    //     lang = 'ru';
    //     site = 'ru';
    //
    //     $.ajax({
    //         url: "/bitrix/components/пространство_имен/main.lookup.input/templates/iblockedit/ajax.php",
    //         type: "GET",
    //         dataType: "json",
    //         data: {IBLOCK_ID:iblock_id, MODE:mode, TYPE:type, admin:admin, lang:lang, search:search, site:site},
    //     }).done(function(data){
    //         $('.mli-search-result-input').text('');
    //         $('.mli-search-result-input').addClass('active');
    //         var html = "";
    //         // Скрытие всплывающего окна, если кликнули мимо
    //         $(document).mouseup(function (e){
    //             var div = $(".mli-search-result-games"); // тут указываем ID элемента
    //             if (!div.is(e.target) // если клик был не по нашему блоку
    //                 && div.has(e.target).length === 0) { // и не по его дочерним элементам
    //                 div.removeClass('active'); // скрываем его
    //             }
    //         });
    //
    //         for (var i=0; i < data.length; i++){
    //             if(typeof data[i].NAME !== 'undefined'){
    //                 html += "<div data-inputID="+data[i].ID+" class='itemInput itemInput"+i+"'>"+[data[i].NAME].toString()+"</div>";
    //             }
    //         }
    //         $('.mli-search-result-input').append(html);
    //     });
    // });
    // $('.mli-search-result-input').on('click',".itemInput", function(){
    //     var input_id = $(this).attr("data-inputID");
    //     var input_name = $(this).text();
    //     $('#ajaxInputResult').val(input_id);
    //     $('.mli-search-result-input').removeClass('active');
    //     $('#ajaxInput').val(input_name);
    //     // Какая нибудь отправка данных, если это не обходимо, должна быть тут
    // });
    //
    // // Выбрать первый предложенный элемент при нажатии на ENTER
    // $('#ajaxInput').on('keyup',function(){
    //     $(this).keypress(function(event){
    //         var keycode = (event.keyCode ? event.keyCode : event.which);
    //         if(keycode == '13'){
    //             $('.itemInput').click();
    //             $('#ajaxInput').blur();
    //         }
    //     });
    // });

});
