# Setting up reporting

This project can run reports at set times using the Artisan scheduler. This requires some means of periodic triggering. Add to crontab the following:

`*/20 * * * * /usr/bin/php /var/www/{path_to_install}/artisan schedule:run >> /dev/null 2>&1`

We will also need a directory at `storage/app/enc` set to `chmod 770` permissions for {appropriate_user}:{webserver_group}

where

- {path_to_install} with the deploy location.
- {appropriate_user} with an appropriately qualified local user
- {webserver_group} with the webserver's group.

It also requires PHP's `zip` extension installed and enabled.