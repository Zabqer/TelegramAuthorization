<?php

namespace MediaWiki\Extension\TelegramAuthorization;

class Setup {
	public static function onRegistration() {
		$GLOBALS["wgPluggableAuth_Config"] = [
			"TelegramAuth" => [
				"plugin" => "TelegramAuthorization",
				"buttonLabelMessage" => "Войти через телеграм",
			]
		];
	}
}
