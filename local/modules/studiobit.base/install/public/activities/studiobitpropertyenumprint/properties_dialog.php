<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)?>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_PROPERTY_CODE") ?>:</td>
    <td valign="top">
        <input type="text" name="property_code" id="property_code" value="<?= htmlspecialcharsbx($arCurrentValues["property_code"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('property_code', 'string');" />
    </td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_VALUE") ?>:</td>
	<td valign="top">
		<input type="text" name="value_id" id="value_id" value="<?= htmlspecialcharsbx($arCurrentValues["value_id"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('value_id', 'string');" />
	</td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_PROPERTY") ?>:</td>
	<td valign="top">
		<input type="text" name="property_process" id="property_process" value="<?= htmlspecialcharsbx($arCurrentValues["property_process"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('property_process', 'string');" />
	</td>
</tr>