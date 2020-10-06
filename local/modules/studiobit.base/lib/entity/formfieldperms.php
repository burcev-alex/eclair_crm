<?
namespace Studiobit\Base\Entity;

class FormFieldPermsTable extends \Bitrix\Main\Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_studiobit_form_field_perms';
    }

    public static function getMap()
    {
        return [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true
            ],
            'ROLE_ID' => [
                'data_type' => 'integer',
                'required' => true
            ],
            'FORM_ID' => [
                'data_type' => 'string',
                'required' => true
            ],
            'FIELD' => [
                'data_type' => 'string',
                'required' => true
            ],
            'PERM' => [
                'data_type' => 'string'
            ]
        ];
    }

    public static function getRoles($user_id = false){
        if($user_id === false){
            global $USER;
            $user_id = $USER->GetID();
        }

        global $DB;

        $obRes = $DB->Query(
            "SELECT * FROM b_crm_role_relation RR INNER JOIN b_user_access UA ON UA.ACCESS_CODE = RR.RELATION AND UA.USER_ID = $user_id",
            false,
            'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
        );

        $arResult = [];
        while ($arRow = $obRes->Fetch())
        {
            $arResult[$arRow['ROLE_ID']] = $arRow['ROLE_ID'];
        }

        return $arResult;
    }

    public static function set($ROLE_ID, $FORM_ID, $FIELD, $PERM){
        $arValues = [
            'ROLE_ID' => $ROLE_ID,
            'FORM_ID' => $FORM_ID,
            'FIELD' => $FIELD,
            'PERM' => $PERM
        ];

        $rsPerm = self::getList(
            array(
                'select' => ['ID'],
                'filter' => ['ROLE_ID' => $ROLE_ID, 'FORM_ID' => $FORM_ID, 'FIELD' => $FIELD]
            )
        );

        if($arPerm = $rsPerm->fetch())
        {
            self::update($arPerm['ID'], $arValues);
        }
        else
        {
            self::add($arValues);
        }
    }

    public static function get($ROLE_ID = false, $FORM_ID, $FIELD = false){
        $filter = [
            'FORM_ID' => $FORM_ID
        ];

        if($ROLE_ID !== false)
            $filter['ROLE_ID'] = $ROLE_ID;

        if($FIELD !== false)
            $filter['FIELD'] = $FIELD;

        $rsPerm = self::getList(
            [
                'filter' => $filter
            ]
        );

        $arResult = [];
        while($arPerm = $rsPerm->fetch())
        {
            $arResult[] = $arPerm;
        }

        return $arResult;
    }

    public static function getFormFieldsPerms($FORM_ID, $user_id = false){
        $arRoles = self::getRoles($user_id);

        $currentPerms = array();

        foreach($arRoles as $roleID){
            $arPerms = self::get($roleID, $FORM_ID);

            foreach($arPerms as $arPerm){
                if(!isset($currentPerms[$arPerm['FIELD']]))
                    $currentPerms[$arPerm['FIELD']] = array();

                if(!isset($currentPerms[$arPerm['FIELD']]['read']))
                    $currentPerms[$arPerm['FIELD']]['read'] = strpos($arPerm['PERM'], 'r') !== false ? 'Y' : 'N';
                elseif( strpos($arPerm['PERM'], 'r') !== false)
                    $currentPerms[$arPerm['FIELD']]['read'] = 'Y';

                if(!isset($currentPerms[$arPerm['FIELD']]['write']))
                    $currentPerms[$arPerm['FIELD']]['write'] = strpos($arPerm['PERM'], 'w') !== false ? 'Y' : 'N';
                elseif(strpos($arPerm['PERM'], 'w') !== false)
                    $currentPerms[$arPerm['FIELD']]['write'] = 'Y';

                if(!isset($currentPerms[$arPerm['FIELD']]['add']))
                    $currentPerms[$arPerm['FIELD']]['add'] = strpos($arPerm['PERM'], 'a') !== false ? 'Y' : 'N';
                elseif(strpos($arPerm['PERM'], 'a') !== false)
                    $currentPerms[$arPerm['FIELD']]['add'] = 'Y';
            }
        }

        return $currentPerms;
    }

    public static function prepareCrmFields($FORM_ID, &$arFields, $bNew = false)
    {
        if($GLOBALS['USER']->IsAdmin())
            return;

        $currentPerms = self::getFormFieldsPerms($FORM_ID);
        foreach($arFields as &$arField)
        {
            $name = $arField['name'];
            if(isset($currentPerms[$name])){
                $perm = $currentPerms[$name];

                if($bNew && $perm['add'] == 'N'){
                    $arField['editable'] = false;
                }
                elseif($perm['write'] == 'N'){
                    $arField['editable'] = false;
                }
            }
        }
    }

    public static function getCrmStyle($FORM_ID, &$arFields, $bNew = false)
    {
        if($GLOBALS['USER']->IsAdmin())
            return '';

        $selectors = [];

        $currentPerms = self::getFormFieldsPerms($FORM_ID);

        foreach($arFields as &$arField)
        {
            $name = $arField['name'];
            if(isset($currentPerms[$name])){
                $perm = $currentPerms[$name];

                if($bNew && $perm['add'] == 'N'){
                    $selectors[] = 'div[data-cid="' . $arField['name'] . '"]';
                }
                elseif($perm['read'] == 'N'){
                    $selectors[] = 'div[data-cid="' . $arField['name'] . '"]';
                }
            }
        }

        return implode(', ', $selectors).'{display:none !important;}';
    }
}
?>