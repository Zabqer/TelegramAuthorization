
### Installation:

1. Enter mediawiki directory
```
cd /path/to/mediawiki/root
```
2. Enter `extensions` directory
```
cd extensions
```
3. Clone repository
```
git clone https://github.com/Zabqer/TelegramAuthorization
```
4. Create telegram bot and set domain
5. Add to `LocalSettings.php`
```
$wgTelegramAuthorization_BotHash = "afa4b..."; # Hmac sha256
$wgTelegramAuthorization_BotID = 0; # Bot id
```
6. Enable plugin: add to `LocalSettings.php`
```
wfLoadExtension( 'TelegramAuthorization' );
```
7. Run mediawiki update command to create plugin tables
```
php maintenance/run.php update
```
