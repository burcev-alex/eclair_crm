<?php
namespace Studiobit\Base\Entity;

use Bitrix\Main\Type\DateTime;
use Studiobit\Base as Base;

\Studiobit\Base\Entity\HighloadBlockTable::compileBaseClass('Command');

/**
 сущность для храненения и выполнения комманд по расписанию
 * 
 * примеры использования
 * $command = \Studiobit\Base\CommandManager::add();
 *
 * Повторяющаяся команда в модуле studiobit.bigroup, выполнять каждый час в 20 и 40 минут
$command->module('studiobit.bigroup')
 ->func('\Studiobit\BiGroup\Command\Example::run()')->minuts([20, 40])->regular(true)->save();
 *
 * Команда в модуле studiobit.bigroup, выполнить один раз сейчас
$command->module('studiobit.bigroup')
->func('\Studiobit\BiGroup\Command\Example::run()')->save();
 *
 * Повторяющаяся команда в модуле studiobit.bigroup, выполнять 1 числа каждого месяца
$command->module('studiobit.bigroup')->func('\Studiobit\BiGroup\Command\Example::run()')
 ->days(1)->regular(true)->save();
 *
 *  Команда в модуле studiobit.bigroup, выполнить 01.01.2018 15:30:00
$command->module('studiobit.bigroup')->func('\Studiobit\BiGroup\Command\Example::run()')
 ->date('01.01.2018 15:30:00')->save();
 *
 * @method int getEntityID()
 * @mixin \Bitrix\Highloadblock\DataManager
 */

class CommandTable extends \CommandBaseTable
{
    protected $id = 0;
    protected $command = '';
    protected $seconds = [];
    protected $minuts = [];
    protected $hours = '';
    protected $days = '';
    protected $mounth = '';
    protected $years = '';
    protected $bRegular = false;
    protected $ss = null;
    protected $mm = null;
    protected $hh = null;
    protected $d = null;
    protected $m = null;
    protected $y = null;
    protected $moduleId = '';
    protected $status = '';
    protected $date_execute;

    /**
     * CommandTable constructor.
     * @param int $id - id команды
     */
    public function __construct($id = 0)
    {
        if($id > 0){
            $this->load($id);
        }
    }

    public static function add($fields)
    {
        //ищем команду с такими же параметрами
        $filter = $fields;
        unset($filter['UF_DATE_EXECUTE']);
        $rs = self::getList([
            'filter' => $filter,
            'select' => ['ID']
        ]);

        //если нашли, то обновляем, а не создаем новую
        if($ar = $rs->fetch()){
            return self::update($ar['ID'], $fields);
        }

        return parent::add($fields);
    }

    public static function update($id, $fields)
    {
        return parent::update($id, $fields);
    }

    /** Чтение комманды из БД
     * @param $id
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function load($id)
    {
        $rs = self::getList([
            'filter' => ['=ID' => $id]
        ]);

        if($ar = $rs->fetch()){
            $this->id = $ar['ID'];

            $this->seconds = array_filter(explode(',', $ar['UF_SECONDS']), [$this, 'clearArray']);

            if(empty($this->seconds))
                $this->seconds = [0];

            $this->minuts = array_filter(explode(',', $ar['UF_MINUTS']), [$this, 'clearArray']);
            $this->hours = array_filter(explode(',', $ar['UF_HOURS']), [$this, 'clearArray']);
            $this->days = array_filter(explode(',', $ar['UF_DAYS']), [$this, 'clearArray']);
            $this->mounth = array_filter(explode(',', $ar['UF_MOUNTH']), [$this, 'clearArray']);
            $this->years = array_filter(explode(',', $ar['UF_YEAR']), [$this, 'clearArray']);
            $this->command = $ar['UF_COMMAND'];
            $this->moduleId = $ar['UF_MODULE_ID'];
            $this->status = $ar['UF_STATUS'];
            $this->bRegular = $ar['UF_REGULAR'];

            if($ar['UF_DATE_EXECUTE'] instanceof DateTime)
            {
                $datetime = $ar['UF_DATE_EXECUTE'];
                $this->date_execute = $datetime;
                $this->ss = $datetime->format('s');
                $this->mm = $datetime->format('i');
                $this->hh = $datetime->format('G');
                $this->d = $datetime->format('j');
                $this->m = $datetime->format('n');
                $this->y = $datetime->format('Y');
            }
        }
    }

    /** Сохрание в БД
     * @return $this
     */
    public function save()
    {
        $date = $this->getDateExecute();
        if(!empty($this->command) && $date !== false) {
            $fields = [
                'UF_SECONDS' => implode(',', $this->seconds),
                'UF_MINUTS' => implode(',', $this->minuts),
                'UF_HOURS' => implode(',', $this->hours),
                'UF_DAYS' => implode(',', $this->days),
                'UF_MOUNTH' => implode(',', $this->mounth),
                'UF_YEAR' => implode(',', $this->years),
                'UF_REGULAR' => (int)$this->bRegular,
                'UF_COMMAND' => $this->command,
                'UF_STATUS' => '',
                'UF_MODULE_ID' => $this->moduleId
            ];

            if($this->date_execute instanceof DateTime){
                $fields['UF_DATE_EXECUTE'] = $this->date_execute;
            }
            else{
                $fields['UF_DATE_EXECUTE'] = DateTime::createFromPhp($date);
            }

            if($this->id)
                self::update($this->id, $fields);
            else
                self::add($fields);
        }
        elseif($this->id) {
            self::delete($this->id);
        }

        return $this;
    }

    private function clearArray($var)
    {
        if($var == '')
            return false;
        return true;
    }

    /** Секунды
     * @param string $seconds
     * @return $this|array
     */
    public function seconds($seconds = ''){
        if($seconds !== '') {
            $this->seconds = $this->clear($seconds);
            return $this;
        }
        return $this->seconds;
    }

    /** Минуты
     * @param string $minuts
     * @return $this|array
     */
    public function minuts($minuts = ''){
        if($minuts !== '') {
            $this->minuts = $this->clear($minuts);
            return $this;
        }
        return $this->minuts;
    }

    /** Часы
     * @param string $hours
     * @return $this|string
     */
    public function hours($hours = ''){
        if($hours !== '') {
            $this->hours = $this->clear($hours);
            return $this;
        }
        return $this->hours;
    }

    /** День запуска
     * @param string $days - день, пустое значение (каждый день) или массив дней
     * @return $this|string
     */
    public function days($days = ''){
        if($days !== '') {
            $this->days = $this->clear($days);
            return $this;
        }
        return $this->days;
    }

    /** Месяц запуска
     * @param string $mounth - месяц, пустое значение или массив месяцев
     * @return $this|string
     */
    public function mounth($mounth = ''){
        if($mounth !== '') {
            $this->mounth = $this->clear($mounth);
            return $this;
        }
        return $this->mounth;
    }

    /** Год запуска
     * @param string $years - год, пустое значение или массив лет
     * @return $this|string
     */
    public function years($years = ''){
        if($years !== '') {
            $this->years = $this->clear($years);
            return $this;
        }
        return $this->years;
    }

    private function clear($value){
        if(!is_array($value)){
            if($value == '*')
                $value = [];
            else{
                $ar = explode(',', $value);
                if(!empty($ar))
                    $value = $ar;
                else
                    $value = [];
            }
        }

        array_unique($value);

        return $value;
    }

    /** Повторяющаяся команда
     * @param bool $flag
     * @return $this
     */
    public function regular($flag = true){
        $this->bRegular = $flag;
        return $this;
    }

    /**
     * Точная дата запуска команды
     * @param $datetime
     * @return $this
     * @throws \Exception
     */
    public function date($datetime)
    {
        if(!($datetime instanceof \DateTime)){
            $timestamp = \MakeTimeStamp($datetime);
            $datetime = new \DateTime();
            $datetime->setTimestamp($timestamp);
        }

        $this->bRegular = false;
        $this->seconds($datetime->format('s'));
        $this->minuts($datetime->format('i'));
        $this->hours($datetime->format('G'));
        $this->days($datetime->format('j'));
        $this->mounth($datetime->format('n'));
        $this->years($datetime->format('Y'));

        return $this;
    }

    public function module($moduleId)
    {
        $this->moduleId = $moduleId;
        return $this;
    }

    public function func($func)
    {
        $this->command = $func;
        return $this;
    }

    /**
     * Поиск ближайшего времени запуска
     * @return bool|\DateTime
     * @throws \Exception
     */
    public function getDateExecute(){
        $return = new \DateTime();
        $bStop = false;
        $date = false;

        if(is_null($this->y))
            $this->nextDate();

        //ищем дату
        while(!$bStop)
        {
            $return->setDate($this->y, $this->m, $this->d)->setTime(23, 59, 59);

            if($date !== false){
                if($date >= $return) {
                    //если не удалось подобрать подходяещую дату, завершаем поиск
                    return false;
                }
            }

            if($return->getTimestamp() >= time()){
                //подобранная дата больше текущего времени, завершаем поиск даты
                $bStop = true;
            }

            $date = new \DateTime();
            $date->setTimestamp($return->getTimestamp());

            $this->nextDate();
        }

        $bStop = false;
        $date = false;

        //ищем время
        while(!$bStop)
        {
            $this->nextTime();
            $return->setTime($this->hh, $this->mm, $this->ss);

            if($date !== false){
                if($date >= $return) {
                    //если не удалось подобрать подходяещее время для даты, то снова подбираем дату
                    return $this->getDateExecute();
                }
            }

            if($return->getTimestamp() >= time()){
                //подобранная дата больше текущего времени, завершаем поиск
                $bStop = true;
            }

            $date = new \DateTime();
            $date->setTimestamp($return->getTimestamp());
        }

        return $return;
    }

    //следующее время с учетом заданных параметров
    private function nextTime()
    {
        if(is_null($this->ss)){
            if(is_array($this->seconds))
            {
                if(!empty($this->seconds))
                    $this->ss = (int)$this->seconds[0];
            }

            $this->incMinuts();
        }
        else{
            if(is_array($this->seconds) && !empty($this->seconds))
            {
                $pos = array_search($this->ss, $this->seconds);
                if($pos == count($this->seconds) - 1){
                    $this->incMinuts();
                    $this->ss = (int)$this->seconds[0];
                }
                else{
                    $this->ss = (int)$this->seconds[$pos + 1];
                }
            }
            else
            {
                $this->ss++;
                if($this->ss == 60){
                    $this->incMinuts();
                    $this->ss = 0;
                }
            }
        }

        if(empty($this->ss))
            $this->ss = 0;
    }

    //вычисление минут
    private function incMinuts()
    {
        if(is_null($this->mm) ){
            if(is_array($this->minuts)){
                if(!empty($this->minuts))
                    $this->mm = (int)$this->minuts[0];
            }

            if(empty($this->mm))
                $this->mm = 0;

            $this->incHours();
        }
        else{
            if(is_array($this->minuts) && !empty($this->minuts))
            {
                $pos = array_search($this->mm, $this->minuts);

                if ($pos == count($this->minuts) - 1) {
                    $this->incHours();
                    $this->mm = (int)$this->minuts[0];
                }
                else{
                    $this->mm = (int)$this->minuts[$pos + 1];
                }
            }
            else
            {
                $this->mm++;
                if($this->mm == 60){
                    $this->incHours();
                    $this->mm = 0;
                }
            }
        }
    }

    //вычисление часа
    private function incHours(){
        if(is_null($this->hh)){
            if(is_array($this->hours)){
                if(!empty($this->hours))
                    $this->hh = (int)$this->hours[0];
            }
        }
        else{
            if(is_array($this->hours) && !empty($this->hours)){
                $pos = array_search($this->hh, $this->hours);
                if ($pos == count($this->hours) - 1) {
                    $this->hh = (int)$this->hours[0];
                }
                else{
                    $this->hh = (int)$this->hours[$pos + 1];
                }
            }
            else{
                $this->hh++;
                if($this->hh == 24){
                    $this->mm = 0;
                }
            }
        }

        if(empty($this->hh))
            $this->hh = 0;
    }

    //следующая дата с учетом заданных параметров
    private function nextDate(){
        if(is_null($this->d)){
            if(is_array($this->days)){
                if(!empty($this->days))
                    $this->d = (int)$this->days[0];
            }

            $this->incMounth();
        }
        else{
            if(is_array($this->days) && !empty($this->days)){
                $pos = array_search($this->d, $this->days);
                if ($pos == count($this->days) - 1) {
                    $this->incMounth();
                    $this->d = (int)$this->days[0];
                }
                else{
                    $this->d = (int)$this->days[$pos + 1];
                }
            }
            else{
                $this->d++;
                if($this->d == date('t', strtotime($this->y.'-'.$this->d))){
                    $this->incMounth();
                    $this->d = 0;
                }
            }
        }

        if(empty($this->d))
            $this->d = 0;
    }

    //вычисление месяца
    private function incMounth(){
        if(is_null($this->m)){
            if(is_array($this->mounth)){
                if(!empty($this->mounth))
                    $this->m = (int)$this->mounth[0];
            }

            if(empty($this->m))
                $this->m = (int)date('n');

            $this->incYear();
        }
        else{
            if(is_array($this->mounth) && !empty($this->mounth)){
                $pos = array_search($this->m, $this->mounth);
                if ($pos == count($this->mounth) - 1) {
                    $this->incYear();
                    $this->m = (int)$this->mounth[0];
                }
                else{
                    $this->m = (int)$this->mounth[$pos + 1];
                }
            }
            else{
                $this->m++;
                if($this->m == 12){
                    $this->incYear();
                    $this->m = 0;
                }
            }
        }
    }

    //вычисление года
    private function incYear()
    {
        if(is_null($this->y)){
            if(is_array($this->years)){
                if(!empty($this->years))
                    $this->y = (int)$this->years[0];
            }

        }
        else{
            if(is_array($this->years) && !empty($this->years)){
                $pos = array_search($this->y, $this->years);

                if ($pos == count($this->years) - 1) {
                    $this->y = (int)$this->years[0];
                }
                else{
                    $this->y = (int)$this->years[$pos + 1];
                }
            }
            else{
                $this->y++;
            }
        }

        if(empty($this->y))
            $this->y = (int)date('Y');
    }

    /** Выполнение команды
     * @return bool
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectException
     */
    public function execute()
    {
        if(empty($this->command) && $this->id)
            return false;

        //подключаем модуль, если необходимо
        if(strlen($this->moduleId)>0 && $this->moduleId != 'main') {
            if (!\Bitrix\Main\Loader::includeModule($this->moduleId))
                return false;
        }

        $eval_result = '';
        try
        {
            $fields = ['UF_STATUS' => 'RUN'];

            if($this->bRegular) {
                $date = $this->getDateExecute();

                if ($date !== false) {
                    $fields['UF_DATE_EXECUTE'] = DateTime::createFromPhp($date);
                }
            }

            self::update($this->id, $fields);

            @eval("\$eval_result=".$this->command.";");

            \p2log(
                \ConvertTimeStamp(false, 'FULL') . ': crontab_command_' . $this->id . ' Успешно',
                'command_' . \ConvertTimeStamp(false, 'SHORT')
            );
        }
        catch (\Exception $e)
        {
            \p2log(
                \ConvertTimeStamp(false, 'FULL') . ': crontab_command_' . $this->id . ' Ошибка: '.$e->getMessage(),
                'command_' . \ConvertTimeStamp(false, 'SHORT')
            );
        }

        self::update($this->id, ['UF_STATUS' => '']);

        echo 'command #'.$this->id.' completed';

        if(!$this->bRegular)
        {
            //если команда не повторяющаяся, то удаляем ее
            static::delete($this->id);
        }
        elseif($eval_result === false)
        {
            //если функция вернуля false, то удаляем команду
            static::delete($this->id);
        }
        else
        {
            //сохраняем (время следующего запуска вычислиться согласно расписанию команды)
            //$this->save();
        }
    }
}
?>