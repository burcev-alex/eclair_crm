<?php

namespace App\Base;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use App\Base\Entity\CommandTable;

class CommandManager
{
    static $processes = [];

    public static function get($id = 0){
        return new Entity\CommandTable($id);
    }

    public static function add(){
        return new Entity\CommandTable();
    }

    public static function have($filter){
        $rsCommand = Entity\CommandTable::getList([
            'filter' => $filter,
            'select' => ['ID']
        ]);

        if($rsCommand->fetch()){
            return true;
        }

        return false;
    }

    /**
     * Выполнение всех комманд
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function execute(){
        $rsCommands = Entity\CommandTable::getList([
            'filter' => [
                '<=UF_DATE_EXECUTE' => new DateTime()
            ],
            //'select' => ['ID']
        ]);

        while($arCommand = $rsCommands->fetch())
        {
            if(!static::isRun($arCommand)) {
                $process = static::getProcesses();
                if(!isset($process[$arCommand['ID']])) {
                    static::$processes[$arCommand['ID']] = time();
                    static::run($arCommand);
                }
            }
        }
    }

    /**
     * @return array
     */
    public static function getProcesses()
    {
        foreach (static::$processes as $id => $time){
            if(time() - $time > 60){
                unset(static::$processes[$id]);
            }
        }

        return static::$processes;
    }

    public static function isRun(array $command = [])
    {
        if(!empty($command)) {
            $processName = '[c]rontab_command_' . $command['ID'];
        }
        else{
            $processName = '[c]rontab_manager';
        }

        $search = (int)exec("ps -ef | grep -c '$processName'", $pid);

        return $search > 0;
    }

    public static function kill()
    {
        exec("pkill -f crontab_manager");
    }

    /**
     * Запуск комманды в отдельном процессе
     * @param array $command
     * @return string
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function run(array $command)
    {
        $result = '';
        //путь к php
        $php_path = Option::get(MODULE_ID, 'php_path', '/usr/bin/php');

        //запуск
        $cmd = $php_path.' -f '.BASE_DIR.'/tools/command.php '.$command['ID'];
        @exec($cmd . ' > /dev/null &', $result);

        return $result;
    }

    public static function monitoringCommands()
    {
        //проверям что процесс работает
        if(!self::isRun()){
            //отправляем письмо
            $to = Option::get(MODULE_ID, 'mail_admin', '');
            if(!empty($to)) {
                bxmail($to, SITE_SERVER_NAME.': Комманды не выполняются', 'Процесс crontab_manager не выполняется');
            }
        }
        //если какие-то комманды не выполняются более 5 минут, то убиваем процесс менеджера
        $bKill = false;
        $date = new DateTime();
        $date->add('-5M');

        $rsCommands = Entity\CommandTable::getList([
            'filter' => [
                '<=UF_DATE_EXECUTE' => $date
            ],
            'select' => []
        ]);

        ob_start();
        while($arCommand = $rsCommands->fetch())
        {
            if(!static::isRun($arCommand)) {
                $bKill = true;
            }
        }
        $text = ob_get_clean();

        if($bKill){
            self::kill();
            //отправляем письмо
            $to = Option::get(MODULE_ID, 'mail_admin', '');
            if(!empty($to)) {
                bxmail($to, SITE_SERVER_NAME.': Комманды не выполняются более 5 минут, процесс остановлен', $text);
            }
        }

        return '\App\Base\CommandManager::monitoringCommands();';
    }
}