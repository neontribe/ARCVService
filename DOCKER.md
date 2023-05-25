# Docker

## Requirements

  * [docker-composer](https://docs.docker.com/compose/install/)

You need to add the testing domains to your hosts file:

    echo "127.0.0.1   arcv-service.test arcv-store.test db" | sudo tee -a /etc/hosts

## Quick start the local DB for native development

Start a local DB in a container with a persistent database. Exposed on port 3336 (to avoid clashes with other mysql).

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

**Move or remove your .env file**. The docker will set its env variable at run time. Edit `docker-compose.yml` to set them, and then run `docker-compose up --force-recreate`

    CURRENT_UI=$(id -u) docker-compose up --build # add -d to fork into the background

If you have run the stack this way it will take care of the env settings, you do not need to update as you did in the previous section. You can develop against this container, the file in this folder are mounted in /opt/project

    docker-compose exec arc bash

If you forked it into the background then you can see the logs with:

    docker-compose logs arc # The arc bit means show just the apache not the mysql

## Resetting

You can reset the system by stopping the containers and deleting the mysql volume and the .env file.  Assuming you are in the directory as this file those commands will look something like this:

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

