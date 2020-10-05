$(function() {
    if(!$.Studiobit)
		$.Studiobit = {};

    if(!$.Studiobit.Project)
        $.Studiobit.Project = {};
	
	//в этом файле пишите только тот js код который используете на всех страницах
	//код оформляйте в виде объектов, которые содержат в себе методы
	//по названию объекта должно быть понятно какую функцию он выполняет
	//пример:
	/*
	$.Studiobit.Project.Dialog = function(params)
    {
		//параметры по умолчанию
		this.params = {
            id: 'dialog',
            type: 'alert'
        };

        $.extend(this.params, params);
	}
	
	$.Studiobit.Project.Dialog.prototype = {
		show: function(content){
			//показываем
		},
		hide: function(){
			//скрываем
		}
	};
	
	//где-то на странице или в этом или другом js-файле
	var dialog = new $.Studiobit.Project.Dialog({id: 'mydialog'});
	dialog.show('Ошибка!');
	*/
	
});