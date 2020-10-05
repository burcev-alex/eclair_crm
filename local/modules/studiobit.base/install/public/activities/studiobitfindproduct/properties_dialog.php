<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_AGREE_PD_DEAL") ?>:</td>
    <td valign="top">
        <input type="text" name="deal" id="deal" value="<?= htmlspecialcharsbx($arCurrentValues["deal"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('deal', 'integer');" />
    </td>
</tr>
