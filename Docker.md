# Docker

## Requirements

To use this install docker-composer:

    apt intall docker.io docker-compose

If you are getting an error to do with the docker composer version, you may need to try another way

If you dont already have Docker, get it from the docker repo:

    https://docs.docker.com/install/linux/docker-ce/ubuntu/#install-using-the-repository

If you are on elementaryOS you need to replace ```$(lsb_release -cs) \``` in step 4 with ```xenial \```

(https://elementaryos.stackexchange.com/questions/11844/installing-docker-on-elementary-os-loki)

Then to install docker-composer you need to use the instructions in the docker-compose repo

    https://github.com/docker/compose/releases

If you find that when you do docker-compose --version it says ```bash: /usr/bin/docker-compose: No such file or directory```
    but usr/bin is in your path you may just need to restart your session for it to refresh.

You will need to fix your local DNS to point arcv-service.test and arcv-store.test to point to localhost and then run:

## Quick start the local DB

Start a local DB in a container with a persistent databse. Exposed on port 3336 (to avoid clashes with other mysql).

    docker-compose up -d arcdb

Connections to that service can be made:

    mysql -ulamp -plamp -h127.0.0.1 -P3336 lamp

And to run against that DB you need set these values in your `.env`:

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3336
    DB_DATABASE=lamp
    DB_USERNAME=lamp
    DB_PASSWORD=lamp

    # MySQL server for testing when SQLite won't cut it
    DB_TESTING_MYSQL_CONNECTION=mysql
    DB_TESTING_MYSQL_HOST=127.0.0.1
    DB_TESTING_MYSQL_PORT=3336
    DB_TESTING_MYSQL_DATABASE=lamp
    DB_TESTING_MYSQL_USERNAME=lamp
    DB_TESTING_MYSQL_PASSWORD=lamp


## Full application in a docker

**This will overwrite your .env file** Back it up now.

    CURRENT_UI=$(id -u) docker-compose up --build # add -d to fork into the background

If you have run the stack this way it will take care of the env settings, you do not need to update as you did in the previous section. You can develop against this container, the file in this folder are mounted in /opt/project

    docker-compose exec arc bash

If you forked it into the background then you can see the logs with:

    docker-compose logs arc # The arc bit means show just the apache not the mysql

## Resetting

You can reset the system by stopping the containers and deleting the mysql volume and the .env file.  Assuming you are in the the directory as this file those commands will look something like this:

    docker-compose stop or ctrl-c if it's in the foreground
    docker-compose rm
    docker volume rm arcvservice_mysql
    rm .env
    docker-compose up --build

## Accessing the environment

You can use the ```exec``` command to run commands in the container, e.g. run the tests:

    docker-compose exec arc /var/www/html/vendor/bin/phpunit

Get a shell in the container:

    docker-compose exec arc bash

