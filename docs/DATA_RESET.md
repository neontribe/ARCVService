# Resetting the data via the button on the dashboard:

We use ansible to manipulate the remote servers. You'll need to install it, then you can re-seed the remote DB using:

See the [infra -> ansible](https://github.com/neontribe/ARCVInfra/blob/main/ansible/UTILS.md#reseed-staging).

## Old Manual method

Use this on local, or on staging if you're unfamiliar with Ansible.

 - chown `env` to the console user and web user group e.g. `chown neontribe:www-data .env`
 - And `chmod 775 .env`

 - Reseed with `php artisan migrate:refresh --seed`
 - Run tests with `phpunit`
