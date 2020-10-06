<?
namespace Studiobit\Project\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Studiobit\Base;
use Bitrix\Crm;
use Studiobit\Project;

Loc::loadMessages(__FILE__);

class Agreement
{
    public static function onBeforeAdd(Entity\Event $event)
    {
        $result = new Entity\EventResult;
        $data = $event->getParameter("fields");

        $arFieldsModify = array();

        // автор по умолчанию
        /*if(empty($data["UF_RESPONSIBLE"])){
            $arFieldsModify["UF_RESPONSIBLE"] = $USER->GetID();
        }

        $arFieldsModify["UF_NOTE_SEND"] = 0;

        if(empty($data["UF_DATE_CREATE"])){
            $arFieldsModify["UF_DATE_CREATE"] = new DateTime();
        }*/

        if(!empty($arFieldsModify))
            $result->modifyFields($arFieldsModify);

        return $result;
    }

    public static function onBeforeUpdate(Entity\Event $event)
    {
        $data = $event->getParameter("fields");

        $result = new Entity\EventResult;

        //UF_CLIENT - искуственное поле
        $result->unsetField('UF_CLIENT');
        unset($data['UF_CLIENT']);

        //сохранение в историю
        $id = $event->getParameter("id");

        if(is_array($id))
            $id = $id['ID'];

        $data['ID'] = $id;

        Base\History\HLBlockHistory::getInstance()->before($data, '\Studiobit\Project\Entity\AgreementTable');

        return $result;
    }

	/**
	 * Событие после создания договора
	 *
	 * @param Entity\Event $event
	 *
	 * @return Entity\EventResult
	 */
	public static function onAfterAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$id = $event->getParameter("id");

		// добавить права доступа к сущности
		//Project\Permission\booking::updatePermission($id, $GLOBALS['USER']->GetID());

		return $result;
	}

    public static function onAfterUpdate(Entity\Event $event)
    {
        $id = $event->getParameter("id");
        if(is_array($id))
            $id = $id['ID'];

        //сохранение в историю
        Base\History\HLBlockHistory::getInstance()->after('\Studiobit\Project\Entity\AgreementTable');
    }
}