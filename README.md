# ARCVService
## About ARC Voucher Service/API
ARCV Service is the service portal and API for ARCV Market.

[![Build Status](https://travis-ci.org/neontribe/ARCVMarket.svg?branch=0.2/release)](https://travis-ci.org/neontribe/ARCVMarket.svg?branch=master)

## Installation of Development instance

1. Clone the repo
2. Create a database and user (homestead, sqlite or mysql)
3. If not using [Homestead](https://https://laravel.com/docs/5.4/homestead) or Valet - you will need to cofigure permissions on `storage` and `bootstrap/cache`. See [Laravel 5.4 Installation](https://laravel.com/docs/5.4#installation) for more info.
4. Copy `.env.example` to `.env` and edit to local settings
5. `composer install`
6. `php artisan key:generate`
7. `php artisan migrate --seed`
8. `php artisan passport:install` to create keys and client
9. Add the "password grant client" id and secret to your `.env`

### To use the Reset data buttton on the dashboard:
 - chown `env` to the console user and web user group e.g. `chown neontribe:www-data .env`
 - And `chmod 775 .env`

 - Reseed with `php artisan migrate:refresh --seed`
 - Run tests with `phpunit`


## Deployment

1. Travis will build and test with every push to the repo.
2. Travis will deploy to staging `https://arcvservice-prealpha.neontribe.org` with every merge to default branch. When default branch is updated, change value in `.travis.yml`.

## CI deploy with Travis set up notes

1. Install travis cli tool wih `gem install travis`
2. Log in to travis cli with `travis login` using git token or creds
3. Create a `.env.travis` that is in `local` env with user `travis` and no password for database.
4. Create `.travis.yml` as per one in this repo without the `env:global:secure:` vars and without the openssl encrypted info. If you are setting up a new config - we need to encrypt and add those values.
5. Use travis cli to encrypt vars and add them to .yml e.g. `travis encrypt DEPLOY_USER=mickeymouse --add` for `$DEPLOY_USER`, `$DEPLOY_IP`, `$DEPLOY_DIR`.
6. Create an ssh key and `ssh-copy-id -i deploy_key.pub` to server. Encrypt the private half and add to the .yml with `travis encrypt-file deploy_key --add`
7. delete the `deploy_key` and `deploy_key.pub` from your machine - don't need them anymore.


# Copyright
This project was developed by :

Neontribe Ltd (registered in England and Wales #06165574) 

Under contract for

Alexander Rose Charity (registered in England and Wales #00279157) 

As such, unless otherwise specified in the appropriate component source, associated file or compiled asset, files in this project repository are Copyright &copy; (2017), Alexander Rose Charity. All rights reserved.

If you wish to discuss copyright or licensing issues, please contact:

Alexander Rose Charity

c/o Wise & Co, 
Wey Court West, 
Union Road, 
Farnham, 
Surrey, 
England,
GU9 7PT
