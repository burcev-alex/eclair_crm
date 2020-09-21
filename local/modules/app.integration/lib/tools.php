<?php
namespace App\Integration;

use Bitrix\Main\Loader;
use App\Integration as Union;

class Tools
{
	public static function siteURL() {
		$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$domainName = $_SERVER['SERVER_NAME'];
		return $protocol.$domainName;
	}
}
