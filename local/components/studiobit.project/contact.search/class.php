<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/*Поле Потенциальный клиент*/

// use \Studiobit\Base;
use \App\Base;
use \Bitrix\Main;
use \Bitrix\Main\Loader;
use Bitrix\Crm;

Main\Localization\Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("studiobit.project:entity.search");

class CStudiobitContactSearchComponent extends CStudiobitEntitySearchComponent
{
    /**
    * Преодопределение параметров
    * @param $params
    * @return array
    */
    public function onPrepareComponentParams($params)
    {
        $params = parent::onPrepareComponentParams($params);

        return $params;
    }


    public function getItems()
    {
        $result = [];
		
		

        $type = $this->request->get('TYPE');
        $query = $this->request->get('QUERY');

        $query = trim($query);

		
        if(strlen($query))
        {
			
            if ($type == 'PHONE')
            {
				
                $sql = \App\Base\Entity\Crm\ContactTable::getSqlForSearchByPhone($query);
				
            }
            else
            {
                $sql = \App\Base\Entity\Crm\ContactTable::query();
                $sql->whereLike('FULL_NAME', '%' . $query.'%');
            }
			
            $rs = $sql->setSelect([
                'ID',
                'NAME',
                'SECOND_NAME',
                'LAST_NAME',
                'FULL_NAME',
                'ASSIGNED_ID'          => 'ASSIGNED_BY.ID',
                'ASSIGNED_NAME'        => 'ASSIGNED_BY.NAME',
                'ASSIGNED_SECOND_NAME' => 'ASSIGNED_BY.SECOND_NAME',
                'ASSIGNED_LAST_NAME'   => 'ASSIGNED_BY.LAST_NAME',
                'ASSIGNED_LOGIN'       => 'ASSIGNED_BY.LOGIN'
            ])->setLimit(50)->exec();

            $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_contact_show');
            $crmPerms = CCrmPerms::GetCurrentUserPermissions();

            while ($arContact = $rs->fetch()) {
                $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['contact_id' => $arContact['ID']]);

                $responsibleName = \CUser::FormatName(\CSite::GetDefaultNameFormat(), [
                    'NAME'        => $arContact['ASSIGNED_NAME'],
                    'SECOND_NAME' => $arContact['ASSIGNED_SECOND_NAME'],
                    'LAST_NAME'   => $arContact['ASSIGNED_LAST_NAME'],
                    'LOGIN'       => $arContact['ASSIGNED_LOGIN']
                ], true);

                $responsible = '<a href="/company/personal/user/' . $arContact['ASSIGNED_ID'] . '/" target="_blank">' . $responsibleName . '</a>';

                $phones = $this->getPhones($arContact['ID']);

                $item = [
                    'ID'          => $arContact['ID'],
                    'FULL_NAME'   => $arContact['FULL_NAME'],
                    'RESPONSIBLE' => $responsible,
                    'PHONES'      => implode('<br />', $phones),
                    'URL'         => $url,
                    'CAN_VIEW' => \CCrmContact::CheckReadPermission($arContact['ID'], $crmPerms)
                ];

                $result[$arContact['ID']] = $item;
            }
        }
        
        return $result;
    }

    protected function getPhones($contactId)
    {
        $result = [];

        $rs = \Bitrix\Crm\FieldMultiTable::getList([
            'select' => ['VALUE', 'ELEMENT_ID'],
            'filter' => [
                'ELEMENT_ID' => $contactId,
                'ENTITY_ID' => CCrmOwnerType::ContactName,
                'TYPE_ID' => 'PHONE'
            ]
        ]);

        while ($el = $rs->fetch()) {
            $result[] = $el['VALUE'];
        }

        return $result;
    }
}