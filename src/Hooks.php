<?php

namespace MediaWiki\Extension\TelegramAuthorization;
use MediaWiki\Specials\SpecialPage;
use MediaWiki\Specials\SpecialUserLogin;

class Hooks {
	public static function onSpecialPageBeforeExecute( $special, $subPage ) {
		if (get_class($special) == "MediaWiki\Specials\SpecialUserLogin") {
			$out = $special->getOutput();
			$out->addModules("ext.telegramauthorization.special.userlogin.scripts");
		}
	}
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = __DIR__ . '/../sql/' . $updater->getDB()->getType() . '/';
		$updater->addExtensionTable( "telegram_users",  $dir . "TelegramAuthorization.sql" );
	}

}
