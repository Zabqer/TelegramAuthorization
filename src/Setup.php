<?php

namespace MediaWiki\Extension\TelegramAuthorization;

class Setup {
	public static function onRegistration() {
		# TODO: i18n or maybe remove this
		$GLOBALS["wgPluggableAuth_Config"] = [
			"TelegramAuth" => [
				"plugin" => "TelegramAuthorization",
				"buttonLabelMessage" => "Войти через телеграм",
			]
		];
	}
}
