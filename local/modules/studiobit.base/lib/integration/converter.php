<?

namespace Studiobit\Base\Integration;

use Studiobit\Base;
use Studiobit\Base\IblockOrm;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock as Iblock;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Studiobit\Base\Integration;

Loc::loadMessages(__FILE__);

/**
 * Работа c классом конвертации файлов из одного формата в другой
 * Class Converter
 */
class Converter
{

	public function create($name)
	{
		if ($name == "cloud") {
			return new Integration\CloudConvert();
		} else {
			return new Integration\Unoconv();
		}
	}
}
