<?php
//инициализация обработчиков событий
if(CModule::IncludeModule('app.base')) {
	\App\Base\Event::setupEventHandlers();
}

if(CModule::IncludeModule('studiobit.base')) {
	\Studiobit\Base\Event::setupEventHandlers();
}
