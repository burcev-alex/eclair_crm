<?php
//инициализация обработчиков событий
if(CModule::IncludeModule('app.base')) {
	\App\Base\Event::setupEventHandlers();
}