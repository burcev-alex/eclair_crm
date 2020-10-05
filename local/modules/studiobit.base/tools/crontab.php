<?php
$processName = 'crontab_manager';

ignore_user_abort(true);
set_time_limit(0);

$search = (int)exec("ps -ef | grep -c '[c]rontab_manager'", $pid);

if($search == 0){
    if(strlen($_SERVER['DOCUMENT_ROOT']) == 0) {
        cli_set_process_title($processName);
    }

    define("NO_KEEP_STATISTIC", true);
    define("NOT_CHECK_PERMISSIONS", true);

    $_SERVER['DOCUMENT_ROOT'] = str_replace(
        array(
            '/bitrix/modules/studiobit.base/tools',
            '/local/modules/studiobit.base/tools'
        ),
        '',
        dirname(__FILE__)
    );

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

    while(ob_get_level()) {
        ob_get_clean();
    }

    \Bitrix\Main\Loader::includeModule('studiobit.base');

    $GLOBALS['USER']->Authorize(1);

    echo 'start';

    \p2log(
        \ConvertTimeStamp(false, 'FULL').': crontab_manager start',
        'command_'.\ConvertTimeStamp(false, 'SHORT')
    );

    while(1)
    {
        //обновление файла
        try {
            \Studiobit\Base\CommandManager::execute();
        }
        catch (\Exception $e){

        }

        sleep(5);
    }
}
elseif($search == 1){
    echo 'Program yet started!';
}
else{
    exec("pkill -f $processName");
    echo 'Script kill';
}