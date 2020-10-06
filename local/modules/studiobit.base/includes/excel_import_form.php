<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<div id="studiobit-import">
    <form enctype="multipart/form-data" method="post">
        <table>
            <tr>
                <td class="import-file">
                    <input type="file" name="file">
                </td>
                <td class="import-template">
                    <a href="<?=$filename?>">Скачать файл с шаблоном</a>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="import-buttons">
                    <input type="submit" value="Загрузить" name="load">
                </td>
            </tr>
        </table>
    </form>
    <div id="studiobit-import-result" style="margin-top: 20px;">

    </div>
</div>
