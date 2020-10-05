<?
namespace Studiobit\Base\UserField;

class Double
{
    public static function GetUserTypeDescription()
    {
        return array(
            "PROPERTY_TYPE"	=> "N",
            "USER_TYPE"	=> "Double",
            "DESCRIPTION" => "Дробное число",
            "GetSettingsHTML"	=>array(__CLASS__, "GetSettingsHTML"),
            "PrepareSettings"		=> array(__CLASS__, "PrepareSettings"),
            "ConvertToDB"		=> array(__CLASS__, "ConvertToDB"),
            "GetPublicViewHTML" => array(__CLASS__, "GetPublicViewHTML"),
            "GetPublicEditHTML" => array(__CLASS__, "GetPublicEditHTML"),
            "GetAdminListViewHTML" => array(__CLASS__, "GetAdminListViewHTML"),
            "GetPropertyFieldHtml" => array(__CLASS__, "GetPropertyFieldHtml"),
            "GetUIFilterProperty" => array(__CLASS__, "GetUIFilterProperty")
        );
    }

    public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
    {
        $precision = 0;
        if (isset($arProperty["USER_TYPE_SETTINGS"]["PRECISION"]))
            $precision = (int)$arProperty["USER_TYPE_SETTINGS"]["PRECISION"];
        if($precision <= 0)
            $precision = 0;

        return '<tr>
            <td>Точность (количество знаков после запятой):</td>
            <td><input type="text" value="'.$precision.'" size="5" name="'.$strHTMLControlName["NAME"].'[PRECISION]"></td>
        </tr>';
    }

    function PrepareSettings($arProperty)
    {
        $precision = intval($arProperty["USER_TYPE_SETTINGS"]["PRECISION"]);
        if($precision <= 0)
            $precision = 0;
        return array("PRECISION" => $precision);
    }

    function ConvertToDB($arProperty, $value)
    {
        $value["VALUE"] = round($value["VALUE"], $arProperty["USER_TYPE_SETTINGS"]["PRECISION"]);
        return $value;
    }

    public static function GetUIFilterProperty($property, $strHTMLControlName, &$fields)
    {
        $fields["type"] = "text";
        $fields["filterable"] = "";
    }

    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        if(strlen($value["VALUE"])>0)
        {
            return str_replace(" ", "&nbsp;", \htmlspecialcharsEx($value["VALUE"]));
        }
        else
            return '&nbsp;';
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        return  '<input type="text" name="'.$strHTMLControlName["VALUE"].'" value="'.$value["VALUE"].'" />';
    }

    function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
    {
        return self::GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName);
    }

    function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        if(strlen($value["VALUE"])>0)
        {
            return str_replace(" ", "&nbsp;", \htmlspecialcharsEx($value["VALUE"]));
        }
        else
            return '&nbsp;';
    }
}

?>