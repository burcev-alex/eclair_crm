<?php

namespace Studiobit\Base\Controller;
use Studiobit\Base as Base;
use Studiobit\Base\View;

/**
 * Контроллер для примера
 */

class Example extends Prototype
{
    // /ajax/example/remove/?ID=123
    public function removeAction()
    {
        $id = $this->getParam("ID");

        $this->view = new View\Json();
        $this->returnAsIs = true;

        return ['remove' => 'ok', 'id' => $id];
    }

    // /ajax/example/update/?ID=123
    public function updateAction() {
        $id = $this->getParam("ID");

        $this->view = new View\Json();
        $this->returnAsIs = true;

        return ['update' => 'ok', 'id' => $id];
    }

    // /ajax/example/hl/
    public function hlAction() {

        $this->view = new View\Json();
        $this->returnAsIs = true;

        $appeal = new Base\Entity\AppealTable();
        $rs = $appeal->getList([
            'filter' => ['DEAL.ID' => 1],
            'select' => ['*', 'TYPE_VALUE' => 'TYPE.VALUE']
        ]);

        $return = ['hl' => 'ok', 'hl_id' => $appeal->getEntityID()];
        if($ar = $rs->Fetch()){
            $return['data'] = $ar;
        }

        return $return;
    }

    // /ajax/example/component/
    public function componentAction()
    {
        $this->view = new View\Html();
        $this->returnAsIs = true;

        return $this->getComponent(
            "bitrix:main.feedback",
            "",
            Array(
                "EMAIL_TO" => "mail@gmail.com",
                "OK_TEXT" => "Ok",
                "REQUIRED_FIELDS" => array(),
                "USE_CAPTCHA" => "N"
            )
        );
    }
}
?>