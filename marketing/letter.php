<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:sender.letter", ".default", array(
	'SEF_FOLDER' => '/marketing/letter/',
	'SEF_MODE' => 'Y',
	'PATH_TO_SEGMENT_ADD' => '/marketing/segment/edit/0/',
	'PATH_TO_SEGMENT_EDIT' => '/marketing/segment/edit/#id#/',
));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");