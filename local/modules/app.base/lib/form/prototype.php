<?
namespace App\Base\Form;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class Prototype
{
    protected $entity;
    protected $message = [];
    protected $errorFields = [];
    
    public function getEntity()
    {
        return $this->entity;
    }
    
    public function getSettings()
    {
        return [];
    }

    public function validate($post)
    {
        return true;
    }

    public function getMessage()
    {
        return implode('<br />', $this->message);
    }

    public function getErrorFields()
    {
        return $this->errorFields;
    }
}
