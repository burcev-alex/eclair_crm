<?
namespace Studiobit\Base\UserField;

class SliderRange extends \CUserTypeString
{
	const USER_TYPE_ID = "slider_range";

	function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => static::USER_TYPE_ID,
			"CLASS_NAME" => __CLASS__,
			"DESCRIPTION" => 'Интервал значений (slider)',
			"BASE_TYPE" => \CUserTypeManager::BASE_TYPE_STRING,
			"EDIT_CALLBACK" => array(__CLASS__, 'GetPublicEditHTML'),
			"VIEW_CALLBACK" => array(__CLASS__, 'GetPublicViewHTML'),
		);
	}

	function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
	{
		if (!is_array($value["VALUE"]))
			$value = static::ConvertFromDB($arProperty, $value);
		$ar = $value["VALUE"];

		return $ar;
	}

	function GetAdminListViewHTML($arProperty, $arHtmlControl)
	{
		if($arHtmlControl["VALUE"])
			return $arHtmlControl["VALUE"];
		else
			return "&nbsp;";
	}

	function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		if (!is_array($arUserField["VALUE"])) {
			$value = $arUserField["VALUE"];
		}

		ob_start();

		echo '<input type="text" name="'.$arHtmlControl["NAME"].'" value="'.$value.'">';

		$s = ob_get_contents();
		ob_end_clean();
		return  $s;
	}

	function ConvertToDB($arProperty, $value)
	{
		global $DB;
		$return = false;

		$defaultValue = isset($value['DEFAULT_VALUE']) && $value['DEFAULT_VALUE'] === true;

		if(
			is_array($value)
			&& array_key_exists("VALUE", $value)
		)
		{
			$text = trim($value["VALUE"]["TEXT"]);
			$len = strlen($text);
			if ($len > 0 || $defaultValue)
			{
				if ($DB->type === "MYSQL")
					$limit = 63200;
				else
					$limit = 1950;

				if ($len > $limit)
					$value["VALUE"]["TEXT"] = substr($text, 0, $limit);

				$val = static::CheckArray($value["VALUE"], $defaultValue);
				if (is_array($val))
				{
					$return = array(
						"VALUE" => serialize($val),
					);
					if (trim($value["DESCRIPTION"]) != '')
						$return["DESCRIPTION"] = trim($value["DESCRIPTION"]);
				}
			}
		}

		return $return;
	}

	function ConvertFromDB($arProperty, $value)
	{
		$return = false;
		if (!is_array($value["VALUE"]))
		{
			$return = array(
				"VALUE" => unserialize($value["VALUE"]),
			);
			if ($return['VALUE'] === false && strlen($value['VALUE']) > 0)
			{
				$return = array(
					"VALUE" => array(
						'MIN' => $value["MIN"],
						'MAX' => 1000000000,
						'STEP' => 1
					)
				);
			}
			if($value["DESCRIPTION"])
				$return["DESCRIPTION"] = trim($value["DESCRIPTION"]);
		}
		return $return;
	}

	/**
	 * Check value.
	 *
	 * @param bool|array $arFields			Current value.
	 * @param bool $defaultValue			Is default value.
	 * @return array|bool
	 */
	function CheckArray($arFields = false, $defaultValue = false)
	{
		$defaultValue = ($defaultValue === true);
		if (!is_array($arFields))
		{
			$return = false;
			if (CheckSerializedData($arFields))
				$return = unserialize($arFields);
		}
		else
		{
			$return = $arFields;
		}

		if ($return)
		{
			$return = false;
		}
		return $return;
	}

	function GetLength($arProperty, $value)
	{
		if(is_array($value) && isset($value["VALUE"]["MIN"]))
			return strlen(trim($value["VALUE"]["MIN"]));
		else
			return 0;
	}

	function PrepareSettings($arProperty)
	{
		$min = 0;
		if (isset($arProperty["SETTINGS"]["MIN"]))
			$min = (int)$arProperty["SETTINGS"]["MIN"];

		$max = 0;
		if (isset($arProperty["SETTINGS"]["MAX"]))
			$max = (int)$arProperty["SETTINGS"]["MAX"];

		$step = 1000;
		if (isset($arProperty["SETTINGS"]["STEP"]))
			$step = (int)$arProperty["SETTINGS"]["STEP"];

		$scale = "N";
		if (isset($arProperty["SETTINGS"]["SCALE"]))
			$scale = $arProperty["SETTINGS"]["SCALE"];

		return array(
			"MIN" =>  $min,
			"MAX" =>  $max,
			"STEP" =>  $step,
			"SCALE" =>  $scale,
		);
	}

	function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT"),
		);

		$min = 0;
		$max = 1000000;
		$step = 1000;
		$scale = "N";

		if (IntVal($arProperty["SETTINGS"]["MIN"]) > 0)
			$min = (int)$arProperty["SETTINGS"]["MIN"];

		if (IntVal($arProperty["SETTINGS"]["MAX"]) > 0)
			$max = (int)$arProperty["SETTINGS"]["MAX"];

		if (IntVal($arProperty["SETTINGS"]["STEP"]) > 0)
			$step = (int)$arProperty["SETTINGS"]["STEP"];

		if (isset($arProperty["SETTINGS"]["SCALE"]))
			$scale = $arProperty["SETTINGS"]["SCALE"];

		return '
		<tr valign="top">
			<td>Нижняя граница:</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[MIN]" value="'.$min.'"></td>
		</tr>
		<tr valign="top">
			<td>Верхняя граница:</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[MAX]" value="'.$max.'"></td>
		</tr>
		<tr valign="top">
			<td>Шаг:</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[STEP]" value="'.$step.'"></td>
		</tr>
		<tr valign="top">
			<td>Шкала:</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[SCALE]" value="'.$scale.'"></td>
		</tr>
		';
	}
}

?>