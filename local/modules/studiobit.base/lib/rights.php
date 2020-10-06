<?
namespace Studiobit\Base;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Права доступа
 * Class Perms
 */
class Rights
{
    const CACHE_TIME = 3600000;

    protected $module_id;
    protected $file;

    public function __construct($module_id)
    {
        $this->module_id = $module_id;
        $this->file = $_SERVER['DOCUMENT_ROOT'].'/local/modules/'.$this->module_id.'/admin/operation_description.php';
    }

    /**
     * загрузка уровней доступа из бд
     * @return array
     * @throws Main\ArgumentException
     */
    protected function loadFromDb()
    {
        $arRights = [];

        $operation = new \Bitrix\Main\OperationTable();

        $rsOperations = $operation->getList([
            'filter' => ['MODULE_ID' => $this->module_id],
            'select' => ['ID', 'NAME']
        ]);

        while($arOp = $rsOperations->fetch())
        {
            $arRights[$arOp['NAME']] = $arOp;
        }

        return $arRights;
    }

    /**
     * загрузка уровней доступа из файла
     * @return array
     */
    protected function loadFromFile()
    {
        $return = [];

        if(file_exists($this->file)){
            $arRights = include($this->file);

            foreach($arRights as $name => $desc){
                $return[strtolower($name)] = ['NAME' => strtolower($name)];
            }
        }

        return $return;
    }

    protected function getCacheId()
    {
        return [
            $this->module_id,
            $this->file,
            filemtime($this->file)
        ];
    }

    /**
     * инициализация уровней доступа для модуля,
     * если файл с правами был изменен, то его данные считываются и если есть новые уровни доступа,
     * то они добавляются в бд
     * @throws \Exception
     */
    public function Init()
    {
        $cache = new Base\Cache($this->getCacheId(), __CLASS__, self::CACHE_TIME);
        if ($cache->start())
        {
            $operation = new \Bitrix\Main\OperationTable();

            $arNewRights = $this->loadFromFile();

            if(!empty($arNewRights))
            {
                $arRights = $this->loadFromDb();

                foreach($arNewRights as $name => $arNewRight){
                    if(isset($arRights[$name]))
                    {
                        unset($arRights[$name]);
                    }
                    else{
                        $arNewRight['MODULE_ID'] = $this->module_id;
                        $arNewRight['BINDING'] = 'module';
                        $operation->add($arNewRight);
                    }
                }

                //удаление прав которых нет в файле
                /*foreach($arRights as $arRight){
                    if($arRight['ID'])
                        $operation->delete($arRight['ID']);
                }*/
            }

            $cache->end(\ConvertTimeStamp(false, 'FULL'));
        }
    }
}