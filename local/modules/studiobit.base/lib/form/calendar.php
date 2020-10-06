<?
namespace Studiobit\Base\Form;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Calendar extends Prototype
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function validate($post)
    {
	    if ( !(int)$post['UF_DATE_TYPE'] ) {
		    $this->message[] = 'Не указан тип события';
		    $this->errorFields[] = 'UF_DATE_TYPE';
	    }

        return count($this->errorFields) == 0;
    }

    public function getSettings()
    {
        return [
            'general' => [
                'NAME' => 'Событие календаря',
                'HELP' => '',
                'TYPE' => 'GROUP',
                'FIELDS' => [
                    [
                        'TYPE' => 'GROUP',
                        'FIELDS' => [
                        	'UF_NAME',
                            'UF_YEAR',
                            'UF_DATE',
	                        'UF_DATE_TYPE',
	                        'UF_START_HOUR',
	                        'UF_END_HOUR',
                        ]
                    ]
                ]
            ],
        ];
    }
}
