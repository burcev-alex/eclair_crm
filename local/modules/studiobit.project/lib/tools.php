<?
namespace Studiobit\Project;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Tools
{
	/**
	 * Контролеры указанных подразделений
	 *
	 * @param $arDepartments
	 *
	 * @return array
	 */
	public static function getDepartmentController($arDepartments){
		\CModule::IncludeModule("intranet");

		$result = [];

		$rsUsers = \CUser::GetList($by="ID", $order="ASC", ['ACTIVE'=>'Y'], ['SELECT' => ['UF_CONTROLLER', 'UF_DEPARTMENT'], 'FIELDS' => ['ID' , 'LAST_NAME', 'NAME']]);
		while($arUser = $rsUsers->Fetch()){
			if(count($arUser['UF_CONTROLLER']) > 0){
				foreach($arDepartments as $id){
					if(in_array($id, $arUser['UF_CONTROLLER'])){
						$result[rand(100, 200)] = $arUser;
					}
				}
			}
		}

		return $result;
	}
    
}
