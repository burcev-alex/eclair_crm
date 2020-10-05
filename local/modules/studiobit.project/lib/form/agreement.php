<?
namespace Studiobit\Project\Form;

use Bitrix\Main\Localization\Loc;
use Studiobit\Base\Form\Prototype as Prototype;

Loc::loadMessages(__FILE__);

class Agreement extends Prototype
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function validate($post)
    {
        /*if(empty($post['UF_NUM'])){
            $this->message[] = 'Не указан номер договора';
            $this->errorFields[] = 'UF_NUM';
        }*/

        if(empty($post['UF_DATE'])){
            $this->message[] = 'Не указана дата договора';
            $this->errorFields[] = 'UF_DATE';
        }

        if(empty($post['UF_DEAL']) && $this->id == 0){
            $this->message[] = 'Не указана сделка';
            $this->errorFields[] = 'UF_DEAL';
        }

        if(empty($post['UF_PAY_TERMS'])){
            $this->message[] = 'Не указана условия оплаты ';
            $this->errorFields[] = 'UF_PAY_TERMS';
        }

        return count($this->errorFields) == 0;
    }

    public function getSettings()
    {
        return [
            'general' => [
                'NAME' => 'Параметры',
                'HELP' => '',
                'TYPE' => 'GROUP', //TABS, GROUP
                'SHOW_TITLE' => 'Y',
                'FIELDS' => [
                    [
                        'TYPE' => 'GROUP',
                        'ALIGMENT' => 'HORIZONTAL',
                        'FIELDS' => [
                            'UF_NUM',
                            'UF_DATE',
                            'UF_DEAL'
                        ]
                    ],
                    [
                        'TYPE' => 'GROUP',
                        'ALIGMENT' => 'HORIZONTAL',
                        'FIELDS' => [
                            'UF_PAY_TERMS',
                            'UF_COMMENT'
                        ]
                    ],
                    [
                        'TYPE' => 'GROUP',
                        'ALIGMENT' => 'HORIZONTAL',
                        'FIELDS' => [
                            'UF_SCAN',
                            'UF_FILES'
                        ]
                    ],
                ]
            ],
        ];
    }
}
