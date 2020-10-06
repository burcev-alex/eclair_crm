<?
namespace Studiobit\Project\Form;

use Bitrix\Main\Localization\Loc;
use Studiobit\Project;
use Studiobit\Base\Form\Prototype as Prototype;

Loc::loadMessages(__FILE__);

class PaymentSchedule extends Prototype
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function validate($post)
    {
	    if(isset($post['UF_SUM']) && empty($post['UF_SUM'])){
		    $this->message[] = 'Не заполнена сумма';
		    $this->errorFields[] = 'UF_SUM';
	    }

	    if(isset($post['UF_DATE']) && empty($post['UF_DATE'])){
		    $this->message[] = 'Не заполнена дата';
		    $this->errorFields[] = 'UF_DATE';
	    }

        if(isset($post['UF_TYPE']) && empty($post['UF_TYPE'])){
            $this->message[] = 'Не заполнен тип платежа';
            $this->errorFields[] = 'UF_TYPE';
        }

        return count($this->errorFields) == 0;
    }

    public function getSettings()
    {
        return [
            'general' => [
                'NAME' => 'Платеж',
                'HELP' => '',
                'TYPE' => 'GROUP', //TABS, GROUP
                'FIELDS' => [
                    [
                        'TYPE' => 'GROUP',
                        'FIELDS' => [
                            'UF_DATE',
                            'UF_SUM',
                            'UF_TYPE'
                        ]
                    ],
                ]
            ],
        ];
    }
}
