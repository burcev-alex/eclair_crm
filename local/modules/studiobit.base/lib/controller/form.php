<?php

namespace Studiobit\Base\Controller;
use Studiobit\Base as Base;
use Studiobit\Base\View;

/**
 * Контроллер для форм
 */

class Form extends Prototype
{
    public function getfieldsettingsAction()
    {
        $form_id = $this->getParam("form_id");
        $field = $this->getParam("field");

        $this->view = new View\Json();
        $this->returnAsIs = true;

        $arPerms = [];
        foreach(Base\Entity\FormFieldPermsTable::get(false, $form_id, $field) as $perm){
            $arPerms[$perm['ROLE_ID']] = $perm['PERM'];
        }

        $arRoles = [];
        if(\Bitrix\Main\Loader::includeModule('crm')) {
            $rsRoles = \CCrmRole::GetList();
            while ($arRole = $rsRoles->Fetch()) {
                $arRoles[$arRole['ID']] = $arRole;
            }
        }
        return ['result' => 'ok', 'perms' => $arPerms, 'roles' => $arRoles];
    }

    public function savefieldsettingsAction()
    {
        $form_id = $this->getParam("form_id");
        $field = $this->getParam("field");

        $this->view = new View\Json();
        $this->returnAsIs = true;

        if(check_bitrix_sessid())
        {
            foreach ($this->request->getPost('perms') as $roleID => $arPerm) {
                $perm = '';

                if ($arPerm['read'] == 'Y')
                    $perm .= 'r';

                if ($arPerm['write'] == 'Y')
                    $perm .= 'w';

                if ($arPerm['add'] == 'Y')
                    $perm .= 'a';

                Base\Entity\FormFieldPermsTable::set($roleID, $form_id, $field, $perm);
            }
        }

        return ['result' => 'ok'];
    }
}
?>