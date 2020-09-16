<?php
namespace App\Base\Component;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Complex extends Single
{
    /** @var array */
    protected $defaultUrlTemplates = [];

    /** @var array */
    protected $componentVariables = [];

    /** @var array */
    protected $variables = [];

    /** @var array */
    protected $urlTemplates = [];

    /** @var array */
    protected $variableAliases = [];

    /** @var string */
    protected $componentPage = '';

    /** @var bool */
    protected $isSefMode = false;

    public function onPrepareComponentParams($params)
    {
        $params = parent::onPrepareComponentParams($params);
        return $params;
    }

    public function executeComponent()
    {
        $this->isSefMode = ($this->arParams['SEF_MODE'] === 'Y');

        $this->initDefaultUrlTemplates();
        $this->initComponentVariables();
        $this->initVariables();

        $this->makeUrlTemplates();
        $this->makeVariableAliases();

        $this->initComponentPage();
        $this->makeOutputResult();

        $this->includeComponentTemplate($this->componentPage);
    }

    protected function makeUrlTemplates()
    {
        $this->urlTemplates = \CComponentEngine::makeComponentUrlTemplates(
            $this->defaultUrlTemplates,
            $this->arParams['SEF_URL_TEMPLATES']
        );
    }

    protected function makeVariableAliases()
    {
        $this->variableAliases = \CComponentEngine::makeComponentVariableAliases(
            [],
            $this->arParams['VARIABLE_ALIASES']
        );
    }

    protected function initComponentPage()
    {
        if ($this->isSefMode)
        {
            $this->componentPage = $this->getComponentPage();

            \CComponentEngine::initComponentVariables(
                $this->componentPage,
                $this->componentVariables,
                $this->variableAliases,
                $this->variables
            );
        }
        else
        {
            \CComponentEngine::initComponentVariables(
                false,
                $this->componentVariables,
                $this->variableAliases,
                $this->variables
            );

            $this->componentPage = $this->getDefaultComponentPage();
        }

        $this->processComponentPage($this->componentPage);
    }

    /**
     * @return string
     */
    protected function getComponentPage()
    {
        $engine = new \CComponentEngine($this);
        return $engine->guessComponentPath(
            $this->arParams['SEF_FOLDER'],
            $this->urlTemplates,
            $this->variables
        );
    }

    protected function processComponentPage(&$componentPage)
    {
        if (empty($componentPage)) {
            $componentPage = $this->getDefaultComponentPage();
            $this->process404();
        }
    }

    protected function process404()
    {
        /** @global \CMain $APPLICATION */
        global $APPLICATION;

        $folder404 = str_replace("\\", '/', $this->arParams['SEF_FOLDER']);
        if ($folder404 !== '/') {
            $folder404 = '/' . trim($folder404, "/ \t\n\r\0\x0B") . '/';
        }
        if (substr($folder404, -1) === '/') {
            $folder404 .= 'index.php';
        }
        if ($folder404 != $APPLICATION->GetCurPage(true)) {
            \CHTTP::SetStatus('404 Not Found');
        }
    }

    protected function makeOutputResult()
    {
        $result =& $this->arResult;

        foreach ($this->urlTemplates as $url => $value) {
            $key = 'PATH_TO_' . strtoupper(str_replace('-', '_', $url));
            $result[$key] = $this->arParams['SEF_FOLDER'] . $value;
        }

        if ($this->isSefMode) {
            $result = array_merge(
                [
                    'SEF_FOLDER' => $this->arParams['SEF_FOLDER'],
                    'URL_TEMPLATES' => $this->urlTemplates,
                    'VARIABLES' => $this->variables,
                    'ALIASES' => $this->variableAliases,
                ],
                $result
            );
        } else {
            $result = [
                'VARIABLES' => $this->variables,
                'ALIASES' => $this->variableAliases,
            ];
        }
    }

    protected function initDefaultUrlTemplates()
    {
        $this->defaultUrlTemplates = $this->getDefaultUrlTemplates();
    }

    protected function initComponentVariables()
    {
        $this->componentVariables = $this->getComponentVariables();
    }

    protected function initVariables()
    {
        $this->variables = $this->getVariables();
    }

    /**
     * @return array
     */
    protected function getVariables()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getDefaultUrlTemplates()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getComponentVariables()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getDefaultComponentPage()
    {
        return 'index';
    }
}