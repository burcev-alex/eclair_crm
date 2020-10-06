<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_DESCRIPTION") ?>:</td>
	<td valign="top">
		<textarea name="description" id="description" cols="50" rows="4"><?= htmlspecialcharsbx($arCurrentValues["description"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('description', 'string');" />
	</td>
</tr>