
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
5. Edit `value` in `TGAuthBotHash` and `TGAuthBotID` to your values in file `extension.json`
6. Enable plugin: add string bellow to `LocalSettings.php`
```
wfLoadExtension( 'TelegramAuthorization' );
```
