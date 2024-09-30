<?php
namespace MediaWiki\Extension\TelegramAuthorization;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\PluggableAuth\PluggableAuth;
use MediaWiki\Extension\PluggableAuth\PluggableAuthLogin;
use MediaWiki\User\UserIdentity;
use MediaWiki\Auth\AuthManager;
use MediaWiki\User\UserFactory;
use SpecialPage;
use Config;

class Auth extends PluggableAuth {
	const TGDATA = "tgdata";
	private Config $mainConfig;
	private $authManager;
	private $userFactory;
	protected $logger = null;
	public function __construct(Config $mainConfig, AuthManager $authManager, UserFactory $userFactory) {
		$this->mainConfig = $mainConfig;
		$this->authManager = $authManager;
		$this->setLogger(LoggerFactory::getInstance("TelegramAuthorization"));
		$this->getLogger()->debug("TelegramAuthorization::Auth created");
		$this->userFactory = $userFactory;
	}

	public function authenticate( ?int &$id, ?string &$username, ?string &$realname, ?string &$email, ?string &$errorMessage ): bool {
		$loginPage = SpecialPage::getTitleFor("Userlogin");
		$redirectUrl = $loginPage->getFullURL();
		$this->getLogger()->debug("TelegramAuthorization::Auth.authenticate");
		$extraLoginFields = $this->authManager->getAuthenticationSessionData(
			PluggableAuthLogin::EXTRALOGINFIELDS_SESSION_KEY
		);
		$tgdata = $extraLoginFields[static::TGDATA];
		if ($tgdata === "NOT_SET") {
			global $wgServer;
			$serverUrl = parse_url($wgServer);
			$serverOrigin = $serverUrl["host"];
			$this->getLogger()->debug($serverOrigin);
			$this->getLogger()->debug($redirectUrl);
			header( "Location: https://oauth.telegram.org/auth?bot_id=" . $this->mainConfig->get("TGAuthBotID") . "&origin=" . $serverOrigin. "&embed=1&request_access=write&return_to=" . $redirectUrl );
			exit();
			return false;
		}
		$tgdata = base64_decode($tgdata);
		$tgdata = json_decode($tgdata);
		if (!$this->validateTelegramData($tgdata)) {
			$errorMessage = "Cannot verify authenticity of telegram data!";
			return false;
		}
		$username = $tgdata->username;
		if ($username === "") {
			$errorMessage = "Cannot get telegram username";
			return false;
		}
		$username = $username;
		$user = $this->userFactory->newFromName($username);
		if ($user !== false && $user->getId() !== 0 ) {
			$id = $user->getId();
		}
		return true;
	}

	private function validateTelegramData($tgdata) {

		$datahash = $tgdata->hash;
		$tgdataRebuild = [];
		foreach ($tgdata as $field => $value) {
			if ($field !== "hash") {
				$tgdataRebuild[] = $field . "=" . $value;
			}
		}
		sort($tgdataRebuild);
		$tgdataCheckString = implode("\n", $tgdataRebuild);
		$shaToken = hex2bin($this->mainConfig->get("TGAuthBotHash"));
		$this->getLogger()->debug($tgdataCheckString);
		$calchash = hash_hmac("sha256", $tgdataCheckString, $shaToken);

		return strcmp($datahash, $calchash) === 0;

	}

	public function deauthenticate( UserIdentity &$user ): void {
	}
	public function saveExtraAttributes( int $id ): void {
	}
	public static function getExtraLoginFields(): array {
		return [
			static::TGDATA => [
				"type"	=> "hidden",
				"value" => "NOT_SET",
			],
		];
	}

}

