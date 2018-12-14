# Docker

## Requirements

To use this install docker-composer:

    apt intall docker.io docker-compose
    
If you are getting an error to do with the docker composer version, you may need to try another way
    
If you dont already have Docker, get it from the docker repo:
    
    https://docs.docker.com/install/linux/docker-ce/ubuntu/#install-using-the-repository
    
If you are on elementaryOS you need to replace ```$(lsb_release -cs) \``` in step 4 with ```xenial \```
    
Then to install docker-composer you need to use the instructions in the docker-compose repo
    
    https://github.com/docker/compose/releases
    
If you find that when you do docker-compose --version it says ```bash: /usr/bin/docker-compose: No such file or directory```
    but usr/bin is in your path you may just need to restart your session for it to refresh.

You will need to fix your local DNS to point arcv-service.test and arcv-store.test to point to localhost and then run:

## Quick start

    CURRENT_UID=$(id -u):$(id -g) docker-compose up --build # add -d to fork into the background

If you forked it into the background then you can see the logs with:

    docker-compose logs arc # The arc bit means show just the apache not the mysql

The docker mounts this folder into the conatiner at /var/www/html so edits on the host file system will be reflected in the container.

## Resetting

You can reset the system by stopping the containers and deleting the mysql volume and the .env file.  Assuming you are in the the directory as this file those commands will look something like this:

    docker-compose stop or ctrl-c if it's in the foreground
    docker-compose rm
    docker volume rm arcvservice_mysql
    rm .env
    CURRENT_UID=$(id -u):$(id -g) docker-compose up --build

## Environment variables.

At run time you can override environment variables. At run time by exporting the required environment variable.

    export APP_SEEDS=dev
    CURRENT_UID=$(id -u):$(id -g) docker-compose up --build

## Accessing the environment

You can use the ```exec``` command to run commands in the container, e.g. run the tests:

    docker-compose exec arc /var/www/html/vendor/bin/phpunit

Get a shell in the container:

    docker-compose exec arc bash

