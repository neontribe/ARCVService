# ARCVService
## About ARC Voucher Service/API
ARCV Service is the service portal and API for ARCV Market.

## Installation of Development instance

1. Clone the repo
2. Create a database and user (homestead, sqlite or mysql)
3. If not using [Homestead](https://laravel.com/docs/5.5/homestead) or Valet - you will need to configure permissions on `storage` and `bootstrap/cache`. See [Laravel 5.5 Installation](https://laravel.com/docs/5.5#installation) for more info.
4. Copy `.env.example` to `.env` and edit to local settings
5. `composer install`
6. `php artisan key:generate`
7. `php artisan migrate --seed`
8. `php artisan passport:install` to create keys and client
9. `chmod 600 ./storage/*.key` to set permissions correctly
10. Add the "password grant client" id and secret to your `.env`

We suggest that you use the TLD `.test` as others, like `.app` may now be in the public domain and you will experience difficulty with respect to browser behavior over HTTP/HTTPS.

## Setting up reporting

This project can run reports at set times using the Artisan scheduler. This requires some means of periodic triggering. Add to crontab the following:

`*/20 * * * * /usr/bin/php /var/www/{path_to_install}/artisan schedule:run >> /dev/null 2>&1`

We will also need a directory at `storage/app/enc` set to `chmod 770` permissions for {appropriate_user}:{webserver_group}

where

- {path_to_install} with the deploy location.
- {appropriate_user} with an appropriately qualified local user
- {webserver_group} with the webserver's group.

It also requires PHP's `zip` extension installed and enabled.

### To use the Reset data buttton on the dashboard:
 - chown `env` to the console user and web user group e.g. `chown neontribe:www-data .env`
 - And `chmod 775 .env`

 - Reseed with `php artisan migrate:refresh --seed`
 - Run tests with `phpunit`


### Styling
#### Service 
- Service styling is in `resources/assets/sass/app.scss`
- When amending the styles in development, run `npm run watch` to watch for changes
- Service is compiled from Sass with `npm run prod`
#### Store
- Store styling is in `public/store/css/main.css`

## Deployment

1. Travis will build and test with every push to the repo.
2. Travis will deploy to staging `https://arcvservice-prealpha.neontribe.org` with every merge to default branch. When default branch is updated, change value in `.travis.yml`.

## CI deploy with Travis set up notes

1. Install travis cli tool wih `gem install travis`
2. Log in to travis cli with `travis login` using git token or creds
3. Create a `.env.travis` that is in `local` env with user `travis` and no password for database.
4. Create `.travis.yml` as per one in this repo without the `env:global:secure:` vars and without the openssl encrypted info. If you are setting up a new config - we need to encrypt and add those values.
5. Use travis cli to encrypt vars and add them to .yml e.g. `travis encrypt STAGING_USER=mickeymouse --add` for `$STAGING_USER` and `$STAGING_IP`.
6. Create an ssh key and `ssh-copy-id -i staging_key.pub` to server. Encrypt the private half and add to the .yml with `travis encrypt-file staging_key --add`
7. delete the `staging_key` and `staging_key.pub` from your machine - don't need them anymore.


# Copyright
This project was developed by :

Neontribe Ltd (registered in England and Wales #06165574) 

Under contract for

Alexandra Rose Charity (registered in England and Wales #00279157) 

As such, unless otherwise specified in the appropriate component source, associated file or compiled asset, files in this project repository are Copyright &copy; (2019), Alexandra Rose Charity. All rights reserved.

If you wish to discuss copyright or licensing issues, please contact:

Alexandra Rose Charity

c/o Wise & Co, 
Wey Court West, 
Union Road, 
Farnham, 
Surrey, 
England,
GU9 7PT

# Licensing and use of Third Party Applications
These are the languages and packages used to create ARCV Service and where available the licences associated with them.

ARCV Service 1.7

Programming Language - PHP\
Framework - Laravel https://github.com/laravel/laravel \
Licence - The Laravel framework is open-sourced software licensed under the MIT license.

Third Party Packages
- https://github.com/barryvdh/laravel-cors MIT Licence https://github.com/barryvdh/laravel-cors/blob/master/LICENSE 
- https://github.com/barryvdh/laravel-dompdf MIT Licence https://opensource.org/licenses/MIT
- https://github.com/doctrine/dbal MIT Licence https://github.com/doctrine/dbal/blob/master/LICENSE
- https://github.com/Maatwebsite/Laravel-Excel MIT license https://laravel-excel.maatwebsite.nl/docs/3.0/getting-started/license
- https://github.com/moontoast/math Apache Licence 2.0 https://github.com/moontoast/math/blob/master/LICENSE
- https://github.com/esbenp/laravel-api-consumer None Stated
- https://github.com/ramsey/uuid MIT Licence https://github.com/ramsey/uuid/blob/master/LICENSE
- https://github.com/sebdesign/laravel-state-machine MIT Licence https://github.com/sebdesign/laravel-state-machine/blob/master/LICENSE.md
- https://github.com/spinen/laravel-mail-assertions MIT Licence https://opensource.org/licenses/MIT
- https://github.com/TomLingham/Laravel-Searchy MIT Licence https://github.com/TomLingham/Laravel-Searchy/blob/master/LICENSE
