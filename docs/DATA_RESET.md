# Resetting the data

    ** Don't do this on live! **

Staging and local can have their data reset in two ways

## Admin portal method

**This is less reliable on staging**

- being an admin (arc+admin@neontribe.co.uk - password in seeds)
  - log in to the admin portal
  - there will be a reset button on the bottom left of the nav panel. 

## Manual method

Use this on staging if you're performing manual resets for training.
- become a user who can run artisan (`sudo -i` on staging)
- find your laravel root, eg. `cd /var/www/ARCVService/current_release`

### Rolling back and refreshing the DB 
- Reseed with `php artisan migrate:refresh --seed`

### Fixing the web app logins
Reseeding flattens the tables that hold the laravel [Passport](https://laravel.com/framework/docs/12.x/passport) tokens the app needs to work.
You won't be able to log in to the web app until that's sorted.

- get a new key put in the DB 
  - `php artisan passport:client --password --name="Rose Vouchers Password Grant Client" --provider=users`
- transcribe the ID and key to `.env`
  - PASSWORD_CLIENT
  - PASSWORD_CLIENT_SECRET



