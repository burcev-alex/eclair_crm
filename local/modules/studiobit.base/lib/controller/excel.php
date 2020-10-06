<?php

namespace Studiobit\Base\Controller;
use Studiobit\Base as Base;
use Studiobit\Base\View;

/**
 * Контроллер для импорта\экспорта в\из экселя
 */

class Excel extends Prototype
{
    // /ajax/excel/import/
    public function importAction()
    {
        $result = [];

        $this->view = new View\Json();
        $this->returnAsIs = true;
		
		$class = $this->getParam('CLASS_NAME');
		$run = $this->getParam('RUN') == 'Y';

		if(!strlen($class)){
			$result = [
				'IMPORT' => 'ERROR',
				'MESSAGE' => 'Не указан класс для импорта'
			];
		}
		else 
        {
            $params = $this->getParam('IMPORT_PARAMS');

            if (empty($params) || !is_array($params))
                $params = [];

            $class = 'Base\\Excel\\Import\\' . $class;
            $import = new $class($params);

            $arSettings = $import->getSettings();

            $filename = $arSettings['PROPERTY_FILE_VALUE']['SRC'];

            if ($run)
            {
                $result = $this->execute($import);
            }
            else
            {
                $this->view = new View\Html();
                $result = $this->getIncludeArea(Base\BASE_DIR.'includes/excel_import_form.php', ['filename' => $filename]);
            }
        }

        return $result;
    }

    // /ajax/excel/export/
    public function exportAction() {
        $this->view = new View\Html();
        $this->returnAsIs = true;
		
		$class = $this->getParam('CLASS_NAME');

		if(!strlen($class)){
			$result = 'Не указан класс для экспорта';
		}
		else
		{
			$params = $this->getParam('EXPORT_PARAMS');
			
			if(empty($params) || !is_array($params))
				$params = [];

			$class = 'Base\\Excel\\Export\\'.$class;
			$export = new $class($params);
            $result = $this->execute($export);
		}

        return $result;
    }
	
	protected function execute($class)
	{
		if($class instanceof Base\Excel\Import\Prototype)
		{
			$success = $class->Run();

			if ($success) {
				$log = $class->getLog();
				ob_start();
				foreach ($log as $item) {
					?>
					<div class="info-message-wrap <? if ($item['TYPE'] == 'ERROR'):?>info-message-red<?
					else:?>info-message-green<?endif; ?>">
						<div class="info-message">
							<div class="info-message-title"><b><?= $item['TITLE'] ?></b></div>
							<?= $item['MESSAGE'] ?>
							<div class="info-message-icon"></div>
						</div>
					</div>
					<?
				}

				$htmlLog = ob_get_clean();
				$result = [
					'IMPORT' => 'OK',
					'LOG' => $htmlLog,
					'LOAD_COUNT' => $class->getCount('LOAD'),
					'COUNT' => $class->getCount(),
					'PROGRESS' => $class->getProgress(),
					'STATE' => $class->getState(),
					'STATE_MESSAGE' => $class->getStateMessage()
				];

				ob_start();
				?>
				<div id="studiobit-import-progress">
					<div class="progress-state">
						<p><b><?= $result['STATE_MESSAGE'] ?></b></p>
						<? if ($result['STATE'] == 'IMPORT'):?>
							<p>Загружено <?= $result['LOAD_COUNT'] ?> из <?= $result['COUNT'] ?></p>
						<?endif; ?>
					</div>
					<div class="progress-bar">
						<div style="width:<?= $result['PROGRESS'] ?>%">
							<div><?= $result['PROGRESS'] ?>%</div>
						</div>
					</div>
				</div>
				<?
				$result['PROGRESS_HTML'] = ob_get_clean();
			}
            else
			{
				ob_start();
				?>
				<div class="info-message-wrap info-message-red">
					<div class="info-message">
						<div class="info-message-title"><b>Не удалось импортировать</b></div>
						<? echo implode('<br />', $class->getErrors()); ?>
						<div class="info-message-icon"></div>
					</div>
				</div>
				<?
				$htmlLog = ob_get_clean();

				$result = [
					'IMPORT' => 'ERROR',
					'MESSAGE' => $htmlLog,
				];
			}
		}
		else
		{
            $result = '';
			$success = $class->Run();
			if ($success)
            {
				if($class->save($this->getParam('TYPE') == 'pdf' ? Base\Excel\Export\Prototype::PDF : Base\Excel\Export\Prototype::XLSX))
				{
					
				}
				else
				{
					$result = 'ERROR: '.implode('<br />', $class->getErrors());
				}
			}
			else
            {
				$result = 'ERROR: '.implode('<br />', $class->getErrors());
			}
		}
		
		return $result;
	}
}
?>