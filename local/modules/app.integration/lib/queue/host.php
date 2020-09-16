<?php

namespace App\Integration\Queue;

use \Bitrix\Main;
use App\Integration as Union;

interface Host {
	public function init($request);
	public function command();
	public function result();
}
?>