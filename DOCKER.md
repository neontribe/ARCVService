# Docker

If you know what you are doing then jump straight to [components](#components) or [configuration](#configuration) sections, otherwise these instruction should get you started.

1. [Install docker desktop](#install-docker-desktop)
2. [Fixing domain names](#fixing-domain-names)
3. [Start the container](#start-the-container)
5. [Building assets](#accessing-the-container)
4. [Accessing the container](#accessing-the-container)
6. [Native development](#native-development)
7. [Development using the container](#development-using-the-container)
8. [Resetting the database](#resetting-the-database)
9. [Components](#components)
10. [Configuration](#configuration)
11. [Troubleshooting](#troubleshooting)

## Install docker desktop

Docker containers are available for Windows, Mac and Linux. Install intructions are here: [docker-composer](https://docs.docker.com/compose/install/). This guide will asusme you have followed [Scenario one: Install Docker Desktop](https://docs.docker.com/compose/install/#scenario-one-install-docker-desktop).

## Fixing domain names

The ARC project uses virtual host switching to deviler it's service. To support this you will need to add the development host names to your system. you will need to add the following line to your hosts file:

    127.0.0.1   arcv-service.test arcv-store.test sqldb

On Mac or Linux you can run this command:

```bash
grep arcv-service.test /etc/hosts || sudo tee -a /etc/hosts docker compose up
```

Full instruction for Windows, Mac and Linux are [here](https://www.howtogeek.com/27350/beginner-geek-how-to-edit-your-hosts-file/)

## Env file

The container does not need the `.env` file. Any settings in the env file will override those in the container. If you have an existing .env file you will need to back it up and move it out of the way. See the [configuration](#configuration) section for instructions on how to change settings normally controlled by the env file.

```bash
mv .env .env.backup
```

And to restore it

```bash
mv .env.backup .env
```

## Start the container

Assuming you have a terminal open and have changed directory to the root of this repo:

```bash
docker compose up
```

The container will be ready to use when you see output something like this:

```
arcvservice-arc-1         | 
arcvservice-arc-1         |    INFO  Server running on [http://0.0.0.0:8000].  
arcvservice-arc-1         | 
arcvservice-arc-1         |   Press Ctrl+C to stop the server
arcvservice-arc-1         | 
arcvservice-arc-1         |    WARN  Xdebug: [Step Debug.  
arcvservice-arc-1         | 
arcvservice-arc-1         |    WARN  Xdebug: [Step Debug.  
arcvservice-arc-1         | 
arcvservice-arc-1         |    WARN  Xdebug: [Step Debug.  
arcvservice-arc-1         | 
arcvservice-arc-1         |   2023-05-26 11:01:49 ................................................... ~ 0s
```

If you are using Mac, Linux or WSL you can set the server to run as your user, this means that the file written by Laravel will be owned by you.

```bash
CURRENT_UI=$(id -u) docker compose up
```

Logs will appear in the terminal standard out.

## Building assets

This project uses `yarn` to build and deploy the static assets. The assets are (re)built when the `arc` container is restarted. If you need to rebuild the assets without restarting the container you can run `yarn` either natively or in the container. 

### Watching assets

Natively:

    yarn watch # or prod to build them once and exit

In the container:

    docker compose exec arc .docker/yarn.sh watch # or prod to build them once and exit

## Accessing the container

You can use the ```exec``` command to run commands in the container, e.g. run the tests:

    docker compose exec arc /var/www/html/vendor/bin/phpunit

Get a shell in the container:

    docker compose exec arc bash

To run `artisan`:

    docker compose exec arc php artisan

To run create and run a new migration:

    docker compose exec arc php artisan make:migration
    docker compose exec arc php artisan migrate

## Native development

The full docker stack runs Apache, Mysql, PhpMyadmin and a Mail catcher. You can choose which of these service you start. If you have installed PHP and Apache locally you can still use the containers to provide other services. You will still need to [set up your hosts file](#fixing-domain-names).

```bash
docker compose up [container_name, container_name, ....]
```

To start the MySql database, the mail catcher and PHPMyAdmin:

```bash
docker compose up sqldb mailer phpmyadmin
```

And to use those services you will need to update these settings in your `.env` file:

```yaml
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3336
DB_DATABASE=lamp
DB_USERNAME=lamp
DB_PASSWORD=lamp

MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

## Development using the container

The repository folder is mounted in the container (at `/opt/project`). You can just [start the container](#start-the-container) and point your IDE at this folder.

### PHPStorm

If you are using PHP Storm then [here](.docker/DEBUGGING.md) is a detailed guide of how to attach the PHP Storm debugger to the thread in the running container.

## Components

### arc

The `arc` container is built at run time. It creates a docker image called `neontribe/arc:dev` and exposes itself at http://localhost:8000 using `artisan serve`. 

### sqldb

The `sqldb` container is the official mysql version 5.7 container. At start up it creates a databse called `lamp` and user with all privileges on the database identified by user `lamp` and password `lamp`. This database is exposed on 127.0.0.1 at port 3336. The higher port is used so that it does not clash with a native mysql if that is running.

### mailer

The `mailer` container is a build of mailcatcher. It stands up a SMTP server available on 127.0.0.1 at port 1025. This can receive email to/from any address. The mails it recieves and swallows can be viewed at http://localhost:1080.

### phpmyadmin

The `phpmyadmin` container is the official build of PHPMyAdmin which automatically connects to the database provided by the `sqldb` container. It can be accessed http://localhost:8800

## Configuration

The arc container ships with default settings that will run the service "out of the box".

When the service is started the default settings from the [Dockerfile](Dockerfile) are read. Then any settings specified in the environment section of the arc container in the [docker-compose.yml](docker-compose.yml) will override those in the Docker file. And finally any settings in the `.env` file will override those in the `docker-compose.yml` file. 

e.g. Lets look at the `APP_LOG_LEVEL`. 

1. It defaults to `debug` in the Dockerfile
2. If we add it to the environment section of the docker-compose.yml file we can set that to `info`
```yaml
    ....
    environment:
        APP_LOG_LEVEL: info
        CURRENT_UID: ${CURRENT_UID:-33}
        DB_USERNAME: lamp
        DB_PASSWORD: lamp
    ....
```
3. If we create a `.env` file and put the value in there, then the container will read that value.
```
APP_LOG_LEVEL=warning
```

A full list of what can be overridden can be found in the [Dockerfile](Dockerfile).

## Troubleshooting

### Container won't start

If you see an error like `Bind for 0.0.0.0:8000 failed: port is already allocated.` that means another service is running on a clashing port. The default ports are:

 * arc:8000
 * mysql:3336
 * phpmyadmin:8800
 * smtp:1025
 * smtp admin:1080

These can be overridden by setting the value either in the .env file or passing them in on the command line.

e.g.

```
echo MAILER_SMTP_PORT=12345 >> .env
docker compose up
```

or

MAILER_SMTP_PORT=12345 docker compose up

### I don't have any styling

You need to (re)build the assets.

```bash
docker compose up --build
```

or

```bash
docker compose exec arc .docker/yarn.sh prod
```

### Pretty much any other error

Pretty much any other error can be fixed by resetting the containers. **This will not effect any of your files or work**.

```bash
docker compose stop
docker compose rm # answer yes
docker system prune # answer yes (no to keep your DB)
docker compose up --build
```





