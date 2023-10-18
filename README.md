# ARCV Service

## About ARC Voucher Service/API
ARCV Service is the service portal and API for ARCV Market.

## Installation of Development instance

1. Clone the repo
2. Create a database and user (homestead, sqlite or mysql)
3. If not using [Homestead](https://laravel.com/docs/6.x/homestead) or Valet - you will need to configure permissions on `storage` and `bootstrap/cache`. See [Laravel 6.x Installation](https://laravel.com/docs/6.x) for more info.
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

## Versioning, branching and tags

Use [semver](https://semver.org/) for versioning. Each sprint is considered a minor release until the customer asks for a major, non backwardly compatible re-write.

### Sprint development

 * Find the trello card and grab the URL, e.g. `https://trello.com/c/P5aKkOWJ/2056-market-roll-up-repo`
 * Create a branch off develop that uses the ticket number and title, e.g. `dev/2056-market-roll-up-repo`
 * Work on the ticket
 * Raise a PR into develop
 * Add a link to the PR into the Trello card
 * Wait for at least one approval on the PR
 * Merge and delete branch

### Create a Release candidate

 * Tag develop with the incremented release number, e.g. `git checkout develop && git tag v1.16.0-rc.1`
 * Push the tag, `git push --tags`
 * Release to staging

### Creating a Release

 * Merge `develop` into `main`
 * Tag main with a release, e.g. `git checkout main && git tag v1.16.0`
 * Push the tag, `git push --tags`
 * Release to live

### Hotfix

 * Find the trello card and grab the URL, e.g. `https://trello.com/c/P5aKkOWJ/2099-HOTFIX-something-is-broken`
 * Create a branch off **main** that uses the ticket number and title, e.g. `hotfix/2099-HOTFIX-something-is-broken`
 * Work on the ticket
 * Raise a PR merging into **main**
 * Add a link to the PR into the Trello card
 * Wait for at least one approval on the PR
 * Merge and delete branch
 * Once testing is passed create a release with an incremented patch number e.g. `git checkout develop && git tag v1.16.1`
 * Release main to staging *This will change when we are containerised*
 * Test on staging *This will change when we are containerised*
 * Cherry-pick the hotfix commits back into develop
 * Release to live


## Setting up reporting

This project can run reports at set times using the Artisan scheduler. This requires some means of periodic triggering. Add to crontab the following:

`*/20 * * * * /usr/bin/php /var/www/{path_to_install}/artisan schedule:run >> /dev/null 2>&1`

We will also need a directory at `storage/app/enc` set to `chmod 770` permissions for {appropriate_user}:{webserver_group}

where

- {path_to_install} with the deploy location.
- {appropriate_user} with an appropriately qualified local user
- {webserver_group} with the webserver's group.

It also requires PHP's `zip` extension installed and enabled.

### To use the Reset data button on the dashboard:
 - chown `env` to the console user and web user group e.g. `chown neontribe:www-data .env`
 - And `chmod 775 .env`

 - Reseed with `php artisan migrate:refresh --seed`
 - Run tests with `phpunit`

### Styling

#### Service

- Service styling is in `resources/assets/sass/app.scss`
- When amending the styles in development, switching to a new branch or pulling code, run `yarn watch` to watch for changes
- Service is compiled from Sass with `yarn prod`
- 
#### Store

- Store styling is in `public/store/css/main.css`
- Run `yarn dev` to make sure packages Store shares with Service have been included.

## Deployment

1. checkout the release tag, `git checkout v<x.y.z>`
2. `./makedeploy.sh ARCVService_v<x.y.z>(-[beta|RC]X)`
3. copy the tgz file up to the server
4. login and move to the correct folder
5. `./deploy-service ARCVService_v<x.y.z>(-[beta|RC]X).tgz service_v<x.y.z>(-[beta|RC]X)`
6. update the `.env` file

# Copyright
This project was developed by :

Neontribe Ltd (registered in England and Wales #06165574)

Under contract for Alexandra Rose Charity (registered in England and Wales #00279157)

As such, unless otherwise specified in the appropriate component source, associated file or compiled asset, files in this project repository are Copyright &copy; (2023), Alexandra Rose Charity. All rights reserved.

If you wish to discuss copyright or licensing issues, please contact:

Alexandra Rose Charity

c/o Wise & Co,\
Wey Court West,\
Union Road,\
Farnham,\
Surrey,\
England,\
GU9 7PT

# Licensing and use of Third Party Applications
These are the languages and packages used to create ARCV Service and where available the licences associated with them.

ARCV Service 1.15

Programming Language - PHP\
Framework - Laravel https://github.com/laravel/laravel \
Licence - The Laravel framework is open-sourced software licensed under the MIT license.

Third Party Packages
- https://github.com/barryvdh/laravel-cors MIT Licence https://github.com/barryvdh/laravel-cors/blob/master/LICENSE
- https://github.com/barryvdh/laravel-dompdf MIT Licence https://opensource.org/licenses/MIT
- https://github.com/doctrine/dbal MIT Licence https://github.com/doctrine/dbal/blob/master/LICENSE
- https://github.com/moontoast/math Apache Licence 2.0 https://github.com/moontoast/math/blob/master/LICENSE
- https://github.com/esbenp/laravel-api-consumer None Stated
- https://github.com/ramsey/uuid MIT Licence https://github.com/ramsey/uuid/blob/master/LICENSE
- https://github.com/sebdesign/laravel-state-machine MIT Licence https://github.com/sebdesign/laravel-state-machine/blob/master/LICENSE.md
- https://github.com/spinen/laravel-mail-assertions MIT Licence https://opensource.org/licenses/MIT
