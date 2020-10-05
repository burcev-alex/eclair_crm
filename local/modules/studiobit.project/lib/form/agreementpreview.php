<?
namespace Studiobit\Project\Form;

use Bitrix\Main\Localization\Loc;
use Studiobit\Base\Form\Prototype as Prototype;

Loc::loadMessages(__FILE__);

class AgreementPreview extends Prototype
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function validate($post)
    {

        return true;
    }

    public function getSettings()
    {
        return [
            'general' => [
                'NAME' => 'Параметры',
                'HELP' => '',
                'TYPE' => 'GROUP', //TABS, GROUP
                'SHOW_TITLE' => 'N',
                'FIELDS' => [
                    [
                        'TYPE' => 'GROUP',
                        'SHOW_TITLE' => 'N',
                        'FIELDS' => [
                            'UF_NUM',
                            'UF_DATE'
                        ]
                    ]
                ]
            ]
        ];
    }
}
