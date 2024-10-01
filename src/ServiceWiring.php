<?php
namespace MediaWiki\Extension\TelegramAuthorization;

use MediaWiki\MediaWikiServices;

return [
	"TelegramUsersStore" =>
		static function ( MediaWikiServices $services ): TelegramUsersStore {
			return new TelegramUsersStore(
				$services->getDBLoadBalancer()
			);
		},
];
