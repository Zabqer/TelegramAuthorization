dir=`dirname "$0"`
echo $dir
for db in mysql postgres sqlite
do
	php $dir/../../../maintenance/generateSchemaSql.php --json ./TelegramAuthorization.json --sql $db/TelegramAuthorization.sql --type=$db
done
