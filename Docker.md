# Docker

To use this install docker-composer:

    apt intall docker.io docker-compose

You will need to fix your local DNS to point arcv-service.test and arcv-store.test to point to localhost and then run:

    docker-compose up --build # add -d to fork into the background

If you forked it into the background then you can see the logs with:

    docker-compose logs web # The web bit means show just the apache not the mysql

If you want to dev against local files then un-comment the volume section of the web container.

        ports:
            - "8001:80"
            - "8000:8000"
        # If you un-comment this section you can work on local files that run in the dockers.
        # volumes:
        #    - .:/var/www/html
        depends_on: [ db ]

To

        ports:
            - "8001:80"
            - "8000:8000"
        # If you un-comment this section you can work on local files that run in the dockers.
        volumes:
            - .:/var/www/html
        depends_on: [ db ]

When using locally mounted files you can reset the system by stopping the containers and deleting the mysql volume and the .env file.  Assuming you are in the the directory as this file those commands will look something like this:

    docker-compose stop or ctrl-c if it's in the foreground
    docker-compose rm
    docker volume rm arcvservice_mysql
    rm .env
    docker-compose up --build

## Environment variables.

At run time you can override environment variables. At run time by exporting the required environment variable.

    export APP_SEEDS=dev
    docker-compose up --build
