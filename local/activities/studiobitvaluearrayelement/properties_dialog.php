<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_PROPERTY_NAME") ?>:</td>
    <td valign="top">
        <input type="text" name="property" id="property" value="<?= htmlspecialcharsbx($arCurrentValues["property"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('property', 'string');" />
    </td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_KEY_NAME") ?>:</td>
	<td valign="top">
		<input type="text" name="key" id="key" value="<?= htmlspecialcharsbx($arCurrentValues["key"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('key', 'string');" />
	</td>
</tr>