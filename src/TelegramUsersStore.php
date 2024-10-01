<?php
namespace MediaWiki\Extension\TelegramAuthorization;

use Wikimedia\Rdbms\ILoadBalancer;

class TelegramUsersStore {
	private $loadBalancer;

	public function __construct(
		ILoadBalancer $loadBalancer
	) {
		$this->loadBalancer = $loadBalancer;
	}

	public function saveExtraAttributes( int $id, int $tg_id ): void {
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->insert(
			'telegram_users',
			[
				'id' => $id,
				'tg_id' => $tg_id,
			],
			__METHOD__
		);
	}

	public function findUser( int $tg_id ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$row = $dbr->newSelectQueryBuilder()
			->select(
				[
					'user_id',
					'user_name'
				]
			)
			->from( 'user' )
			->join(
				'telegram_users',
				null,
				'user_id=id'
			)
			->where(
				[
					'tg_id' => $tg_id,
				]
			)
			->caller( __METHOD__ )->fetchRow();
		if ( $row === false ) {
			return [ null, null ];
		} else {
			return [ $row->user_id, $row->user_name ];
		}
	}
}
