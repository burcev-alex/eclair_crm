<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_ERROR") ?>:</td>
	<td valign="top">
		<input type="text" name="error" id="error" value="<?= htmlspecialcharsbx($arCurrentValues["error"]) ?>" />
		<input type="button" value="..." onclick="BPAShowSelector('error', 'string');" />
	</td>
</tr>