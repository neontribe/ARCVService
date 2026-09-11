# ARCV Service

## About ARC Voucher Service/API
ARCV Service is the service portal and API for the ARCV Market trader app.

## Getting started the DXW way

Set up your local environment:

switch to 
- php version 8.2
- node lts/jod
  - there's an `.nvmrc` if you use nvm

with Docker installed, enabled and running: 

```sh
./script/setup
```

This will pull and setup containers as well as the app-specific fiddly bits, like keys and databases, with seeded staging data.

and run the local server:

```sh
./script/server
```

The backend behaves differently based on request domain.
You'll need to append your hosts 127.0.0.1 entry with  

- arcv-service.test
- arcv-store.test

### Provided services
Once the server has started, the following containers will be running:

* `web` (Nginx): http://localhost:8080
* `app` (PHP): application backend
* `mailcatcher` (MailCatcher):
  * Web interface: http://localhost:1081
  * SMTP server: localhost:1026
* `db` (MariaDB): localhost:33060 (username/password: `laravel`/`secret`)
  * a main seeded staging DB ("laravel")
  * a test DB ("laravel_testing") for phpunit to use for complex queries

Now you should be able to view 
- the admin portal at: http://arcv-service.test:8080/
- the voucher distribution store at: http://arcv-store.test:8080/

### Testing in containers

- use `./script/console` to log in to the container, then `./vendor/bin/phpunit` to run tests.

## Manual Installation of a Development instance

1. Clone the repo
2. Create a database and user (homestead, sqlite or mysql)
3. If not using [Homestead](https://laravel.com/docs/11.x/homestead) or Valet - you will need to configure permissions on `storage` and `bootstrap/cache`. See [Laravel 12.x Installation](https://laravel.com/docs/12.x) for more info.
4. Copy `.env.example` to `.env` and edit to local settings
5. `composer install`
6. `php artisan key:generate`
7. `php artisan migrate --seed`
8. `php artisan passport:keys` to create keys
9. `chmod 600 ./storage/*.key` to set permissions correctly
10. `php artisan passport:client --password --name="Rose Vouchers Password Grant Client" --provider=users` to set the client in the DB
11. Add the "password grant" client id and secret to your `.env`
12. Install packages for builds: `npm install`
13. Run `npm run watch` in the background during development to automatically compile assets when modifying code or changing commit

We suggest that you use the TLD `.test` as others, like `.app` may now be in the public domain and you will experience difficulty with respect to browser behavior over HTTP/HTTPS.

## More detailed information

 * [Homestead](HOMESTEAD.md) the laravel vagrant vm manager
 * [Resetting/reseeding](DATA_RESET.md) the database with fixtures
 * [Database schema](DATABASE_SCHEMA.md) (diagram)
 * [Voucher state transitions](VOUCHER_STATE_TRANSITIONS.md) (diagram)
 * [Development cycle](DEVELOPMENT_CYCLE.md), sprints, hotfixes, tagging and releases
 * [Current infrastructure](DEPLOYMENT.md), live and staging droplet details
 * [Upgrading javascript](JS_UPGRADE.md)
 * [MVL exports](MVL-EXPORT.md), monthly/yearly voucher export
 * [Creating test vouchers](TEST_VOUCHERS.md) in bulk
 * [Setting up reporting](REPORTING.md)
 * [Styling](STYLING.md)
