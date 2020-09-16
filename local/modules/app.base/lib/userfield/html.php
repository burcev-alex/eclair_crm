<?
namespace App\Base\UserField;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Html extends \CUserTypeString
{
    const USER_TYPE_ID = "app_html";

    function GetUserTypeDescription()
    {
        return array(
            "USER_TYPE_ID" => static::USER_TYPE_ID,
            "CLASS_NAME" => __CLASS__,
            "DESCRIPTION" => 'Html',
            "BASE_TYPE" => \CUserTypeManager::BASE_TYPE_STRING,
            "EDIT_CALLBACK" => array(__CLASS__, 'GetPublicEdit'),
            "VIEW_CALLBACK" => array(__CLASS__, 'GetPublicView'),
        );
    }

    public static function GetPublicView($arUserField, $arAdditionalParameters = array())
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent(
            'bitrix:system.field.view',
            $arUserField['USER_TYPE_ID'],
            $arAdditionalParameters,
            false,
            ['HIDE_ICONS' => 'Y']
        );
        return ob_get_clean();
    }

    function OnBeforeSave($arUserField, $value)
    {
        if(is_array($value)){
            return serialize($value);
        }
        else{
            $arValue = unserialize($value);
            if (is_array($arValue)) {
                if(!empty($arValue['TEXT']) || !empty($arValue['FILES']))
                    return $value;
                else
                    return '';
            }
        }

        $text = $value;

        $value = [
            'TEXT' => '',
            'FILES' => []
        ];

        if(!empty($text)){

            $value['TEXT'] = preg_replace_callback(
                "/\\[(".'IMG ID'.")\\s*=\\s*([a-z0-9]+)([^\\]]*)\\]/is".BX_UTF_PCRE_MODIFIER,
                function($matches){
                    $fileId = (int)$matches[2];
                    if($fileId){
                        $arFile = \CFile::GetFileArray($fileId);
                        return '[IMG]' . $arFile['SRC'] .'[/IMG]';
                    }

                    return '';
                },
                $text
            );
        }

        $files = $_POST[$arUserField['FIELD_NAME'].'_FILES'];

        if(!is_array($files)) {
            $files = [];
        }

        foreach($files as $key => $id){
            $arFile = [];
            $fileId = (int)$id;
            if($fileId){
                $arFile = \CFile::GetFileArray($fileId);
            }

            if(empty($arFile)){
                unset($files[$key]);
            }
        }

        $value['FILES'] = $files;

        if(!empty($value['TEXT']) || !empty($value['FILES'])){
            $value['TEXT'] = base64_encode($value['TEXT']);

            return serialize($value);
        }

        return '';
    }

    public static function convertFromDB($value)
    {
        if(is_array($value))
            $value = reset($value);
        $value = unserialize($value);

        if(is_array($value) && isset($value['TEXT'])){
            $value['TEXT'] = base64_decode($value['TEXT']);
            return $value;
        }

        return [
            'TEXT' => '',
            'FILES' => []
        ];
    }

    /**
     * @return \blogTextParser|\CTextParser|\forumTextParser|\logTextParser
     * @throws \Bitrix\Main\LoaderException
     */
    private static function getParser()
    {
        static $return;

        if(empty($return)) {
            if (Loader::includeModule('blog')) {
                $return = new \blogTextParser(LANGUAGE_ID);
            }
            if (empty($return) && Loader::includeModule('forum')) {
                $return = new \forumTextParser(LANGUAGE_ID);
            }
            if (empty($return) && Loader::includeModule('socialnetwork')) {
                $return = new \logTextParser(LANGUAGE_ID);
            }
        }

        if(empty($return)) {
            $return = new \CTextParser();
        }

        $return->arUserfields = [];

        return $return;
    }

    /**
     * @param array $value
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public static function convertToHtml(array $value)
    {
        $return = [];

        $parser = self::getParser();

        $rules = array(
            "HTML" => "Y",
            "ALIGN" => "Y",
            "ANCHOR" => "Y", "BIU" => "Y",
            "IMG" => "Y", "QUOTE" => "Y",
            "CODE" => "Y", "FONT" => "Y",
            "LIST" => "Y", "SMILES" => "Y",
            "NL2BR" => "Y", "MULTIPLE_BR" => "N",
            "VIDEO" => "Y", "LOG_VIDEO" => "N",
            "SHORT_ANCHOR" => "Y"
        );

        if ($parser instanceof \blogTextParser)
        {
            $return['TEXT'] = $parser::killAllTags($value['TEXT']);
            $return['HTML'] = $parser->convert(
                $value['TEXT'],
                array(),
                $rules
            );
        }
        elseif ($parser instanceof \forumTextParser)
        {
            $return['TEXT'] = strip_tags($value['TEXT']);
            $return['HTML'] = $parser->convert(
                $value['TEXT'],
                $rules,
                "html",
                $value['FILES']
            );
        }
        elseif ($parser instanceof \logTextParser)
        {
            $return['TEXT'] = $parser::clearAllTags($value['TEXT']);
            $return['HTML'] = $parser->convert(
                $value['TEXT'],
                $value['FILES'],
                $rules
            );
        }
        elseif (!empty($parser))
        {
            $return['TEXT'] = $parser::clearAllTags($value['TEXT']);
            $return['HTML'] = $parser->convertText($value['TEXT']);
        }

        $return['HTML'] = preg_replace('/\[[^\]]+\]/', '', $return['HTML']);

        return $return;
    }

    /**
     * @param $value
     * @return false|string|null
     */
    public static function getFileBlock($value)
    {
        if (empty($value['FILES']))
            return null;

        $fileFields = null;
        if (ModuleManager::isModuleInstalled('disk'))
            $fileFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::UF_FIELD_NAME, $id);

        $html = "";

        if ($fileFields && !empty($fileFields[self::UF_COMMENT_FILE_NAME]['VALUE']))
        {
            $rules["USERFIELDS"] = $fileFields[self::UF_COMMENT_FILE_NAME];

            if ($fileFields)
            {
                ob_start();
                $GLOBALS['APPLICATION']->IncludeComponent(
                    'bitrix:system.field.view',
                    $fileFields[self::UF_COMMENT_FILE_NAME]["USER_TYPE"]["USER_TYPE_ID"],
                    array(
                        "PUBLIC_MODE" => false,
                        "ENABLE_AUTO_BINDING_VIEWER" => true,
                        "LAZYLOAD" => 'Y',
                        'arUserField' => $fileFields[self::UF_COMMENT_FILE_NAME]
                    ),
                    null,
                    array("HIDE_ICONS" => "Y")
                );

                $html = ob_get_clean();
            }
        }

        return $html;
    }
}
?>