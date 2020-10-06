<?php

namespace Studiobit\Project\Controller;

use Studiobit\Base\View;

/**
 * ajax-контроллер для компании
 */

class Company extends Prototype
{
	public function searchAction()
    {
        $this->view = new View\Html();
        $this->returnAsIs = true;

        return $this->getComponent(
            'studiobit.project:company.search',
            '',
            [],
            $componentResult
        );
    }
}
?>