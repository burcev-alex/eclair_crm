<?php

namespace Studiobit\Base\Controller;
use Studiobit\Base as Base;
use Studiobit\Base\View;

/**
 * Контроллер для квартирного листа
 */

class Bizproc extends Prototype
{
    public function processAction()
    {
        $arParams = $this->getParam('arParams');

        if(!is_array($arParams)){
            $arParams = json_decode($arParams, true);
        }
        
        $this->view = new View\Json();
        $this->returnAsIs = true;

        unset($arParams['MODE']);
        
        $this->getComponent(
            'studiobit.base:bp.card',
            '',
            $arParams,
            $componentResult
        );

        return ['result' => 'ok'];
    }
}
?>