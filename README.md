## About Remote Database Backup
[![Total Downloads](https://img.shields.io/packagist/dt/rupadana/remote-database-backup.svg?style=flat-square)](https://packagist.org/packages/rupadana/remote-database-backup)

[RDB]() is a web application built using [FilamentPHP v3](https://filamentphp.com/).

This tool offers a seamless and secure way to manage and back up your databases remotely.
With an intuitive user interface, it allows you to schedule backups, and monitor backup with ease.

Designed for efficiency and reliability, [RDB]() is the perfect solution for businesses and developers looking to safeguard their critical data effortlessly.


## Prerequisites
You must install `mysql-client` and `postgresql-client` since this project uses `mysqldump` and `pg_dump`.

## Installation

Clone the repository

```bash
composer create-project rupadana/remote-database-backup
```

Update your local environment

```bash
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mysql_backup
DB_USERNAME=postgres
DB_PASSWORD=
```
if you use `mysql` as your database and get this error `mysqldump: command not found` or not save file, you must update your `.env` file with this configuration for example os x m1

```bash
MYSQL_DUMP_BINARY_PATH="/opt/homebrew/bin/"
```


Run artisan `app:install`

```bash
php artisan app:install
```

Default user

email : rupadana@codecrafters.id

password : 12345678

## Supervisor
This project must use `supervisorctl` to manage database queues. Make sure you configure it itself.

## Database Backup Runner

Currently, we support two runners to back up `mysql` and `postgresql` databases.

You can easly adding your custom runner by extends class `AbstractBackupRunner` and register it on `AppServiceProvider`

```php
\App\Filament\Resources\DatabaseResource\Services\Backup\BackupRunner::register(CustomBackupRunner::class);
```


## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within this project, please send an e-mail to Rupadana via [rupadana@codecrafters.id](mailto:rupadana@codecrafters.id). All security vulnerabilities will be promptly addressed.

## License

The Remote Database Backup is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
