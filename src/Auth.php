<?php
namespace MediaWiki\Extension\TelegramAuthorization;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\PluggableAuth\PluggableAuth;
use MediaWiki\Extension\PluggableAuth\PluggableAuthLogin;
use MediaWiki\User\UserIdentity;
use MediaWiki\Auth\AuthManager;
use MediaWiki\User\UserFactory;
use MediaWiki\Title\TitleFactory;
use SpecialPage;
use Config;

class Auth extends PluggableAuth {
	const TGDATA = "tgdata";
	private Config $mainConfig;
	private $telegramUsersStore;
	private $authManager;
	private $userFactory;
	private $titleFactory;
	protected $logger = null;
	const TELEGRAM_USER_ID_SESSION_KEY = "TelegramUserID";
	public function __construct(Config $mainConfig, TelegramUsersStore $telegramUsersStore, AuthManager $authManager, UserFactory $userFactory, TitleFactory $titleFactory) {
		$this->mainConfig = $mainConfig;
		$this->telegramUsersStore = $telegramUsersStore;
		$this->authManager = $authManager;
		$this->setLogger(LoggerFactory::getInstance("TelegramAuthorization"));
		$this->getLogger()->debug("TelegramAuthorization::Auth created");
		$this->userFactory = $userFactory;
		$this->titleFactory = $titleFactory;
	}

	public static function transliterate(string $str): string {
		$transliterationMap = [
			'А' => 'A',  'Б' => 'B',  'В' => 'V',  'Г' => 'G',  'Д' => 'D',
			'Е' => 'E',  'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z',  'И' => 'I',
			'Й' => 'Y',  'К' => 'K',  'Л' => 'L',  'М' => 'M',  'Н' => 'N',
			'О' => 'O',  'П' => 'P',  'Р' => 'R',  'С' => 'S',  'Т' => 'T',
			'У' => 'U',  'Ф' => 'F',  'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch',
			'Ш' => 'Sh', 'Щ' => 'Shch','Ы' => 'Y',  'Э' => 'E',  'Ю' => 'Yu',
			'Я' => 'Ya', 'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',
			'д' => 'd',  'е' => 'e',  'ё' => 'yo', 'ж' => 'zh', 'з' => 'z',
			'и' => 'i',  'й' => 'y',  'к' => 'k',  'л' => 'l',  'м' => 'm',
			'н' => 'n',  'о' => 'o',  'п' => 'p',  'р' => 'r',  'с' => 's',
			'т' => 't',  'у' => 'u',  'ф' => 'f',  'х' => 'kh', 'ц' => 'ts',
			'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch','ы' => 'y',  'э' => 'e',
			'ю' => 'yu', 'я' => 'ya'
		];
		return strtr($str, $transliterationMap);
	}

	public static function genUsername(): string {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$charactersLen = strlen($characters);
		$usernameLen = 8;

		$username = "";
		for ($i = 0; $i < $usernameLen; $i++) {
			$username .= $characters[rand(0, $charactersLen - 1)];
		}
		return $username;
	}

	public function authenticate( ?int &$id, ?string &$username, ?string &$realname, ?string &$email, ?string &$errorMessage ): bool {
		try {
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
			if ((time() - $tgdata->auth_date) > 86400) {
				$errorMessage = "Auth data is outdated!";
				return false;
			}
			[ $id, $username ] = $this->telegramUsersStore->findUser($tgdata->id);
			if ( $id !== null ) {
				return true;
			}

			if ($tgdata->username == "") {
				$errorMessage = "Cannot get telegram username!";
				return false;
			}


			$prefferedUsername = $tgdata->username;
			if ($prefferedUsername == "") {
				$prefferedUsername = trim($tgdata->first_name . " " . $tgdata->last_name);

				$prefferedUsername = self::transliterate($prefferedUsername);

				$prefferedUsername = preg_replace('/[^a-zA-Z0-9 ]/', '', $prefferedUsername);

				$prefferedUsername = $this->titleFactory->makeTitle( NS_USER, $prefferedUsername );

				$prefferedUsername = $prefferedUsername ? $prefferedUsername->getText() : "";

				if ($prefferedUsername == "") {
					$prefferedUsername = self::genUsername();
				}
			}

			$attempts = 0;

			for (; ; ) {
				if ($attempts > 5) {
					$errorMessage = "Failed to create username!";
					return false;
				}
				$user = $this->userFactory->newFromName($prefferedUsername);
				if ($user !== false && $user->getId() !== 0 ) {
					$prefferedUsername = $prefferedUsername . "_tg";
					$attempts = $attempts + 1;
				} else {
					break;
				}
			}

			$username = $prefferedUsername;

			$this->authManager->setAuthenticationSessionData( self::TELEGRAM_USER_ID_SESSION_KEY, $tgdata->id );

			return true;
		} catch (Exception $e) {
			wfDebugLog("Telegram Authorization", "exception" . $e->__toString() . PHP_EOL);
			$this->getLogger()->error("Exception during authentication: " . $e->__toString());
			$errorMessage = "Internal error!";
			return false;
		}
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
		$telegram_user_id = $this->authManager->getAuthenticationSessionData( self::TELEGRAM_USER_ID_SESSION_KEY );
		$this->telegramUsersStore->saveExtraAttributes($id, $telegram_user_id);
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

