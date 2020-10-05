<?
namespace Studiobit\Project\Handlers;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;

Loc::loadMessages(__FILE__);

class Base
{
    static function onRegisterNamespaceForRouter()
	{
		return array(
			'ROUTE' => 'project',
			'NAMESPACE' => 'Studiobit\Project\Controller'
		);
	}
}
