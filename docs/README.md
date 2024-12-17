# ARCV Service

## About ARC Voucher Service/API
ARCV Service is the service portal and API for the ARCV Market trader app.

## Docker and containers

The service, market and store can be deployed, run locally for training/testing or development without any additional dependencies. Docker instructions are in the [infrastructure repo](https://github.com/neontribe/ARCVInfra/tree/main/docker/README.md) 

## Installation of Development instance

1. Clone the repo
2. Create a database and user (homestead, sqlite or mysql)
3. If not using [Homestead](https://laravel.com/docs/9.x/homestead) or Valet - you will need to configure permissions on `storage` and `bootstrap/cache`. See [Laravel 9.x Installation](https://laravel.com/docs/9.x) for more info.
4. Copy `.env.example` to `.env` and edit to local settings
5. `composer install`
6. `php artisan key:generate`
7. `php artisan migrate --seed`
8. `php artisan passport:install` to create keys and client
9. `chmod 600 ./storage/*.key` to set permissions correctly
10. Add the "password grant client" id and secret to your `.env`
11. Install npm packages for webpack (JS and Sass) builds: `yarn install`
12. Run `yarn watch` in the background during development to automatically compile assets when modifying code or changing commit

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
