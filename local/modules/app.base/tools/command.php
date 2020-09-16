<?php

if($argc == 2){
    $id = (int)$argv[1];
    if($id) {

        $processName = 'crontab_command_'.$id;
        if(strlen($_SERVER['DOCUMENT_ROOT']) == 0) {
            cli_set_process_title($processName);
        }

        ignore_user_abort(true);
        set_time_limit(0);

        define("NO_KEEP_STATISTIC", true);
        define("NOT_CHECK_PERMISSIONS", true);

        $_SERVER['DOCUMENT_ROOT'] = str_replace(
            array(
                '/bitrix/modules/app.base/tools',
                '/local/modules/app.base/tools'
            ),
            '',
            dirname(__FILE__)
        );

        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

        while(ob_get_level()) {
            ob_get_clean();
        }

        \Bitrix\Main\Loader::includeModule('app.base');
        $GLOBALS['USER']->Authorize(1);

        $command = new \App\Base\Entity\CommandTable($id);
        $command->execute();
    }
}