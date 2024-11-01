# DOCKER

Docker stuff has been moved to the [infra repo](https://github.com/neontribe/ARCVInfra) for now.

## Running the whole stack in containers

This command will pull and build the service/store and start it. The files in this project are mounted in the container, changes on the host files system will be reflected in the container.

```bash
echo "127.0.0.1   arcv-service.test arcv-store.test sqldb" | sudo tee -a /etc/hosts
docker compose -f .docker/docker-compose.yml up --build --force-recreate
```

 * Arc service is now available at http://arcv-service.test:8000/login
 * Mysql is available at ``mysql -ulamp -plamp -h127.0.0.1 -P3336 lamp`
 * Phpmyadmin is available at [http://arcv-service.test:8880/login](http://arcv-service.test:8800/index.php?route=/database/structure&db=lamp)
 * Mail catcher is available at http://arcv-service.test:1080/

### Using artisan

To use `artisan` you need to execute the command in the container: e.g.

```bash
docker compose -f .docker/docker-compose.yml exec service /opt/project/artisan tinker
```

Or you can open an interactive shell:

```bash
docker compose -f .docker/docker-compose.yml exec service bash
```

## Docker for just the DB

If you have a native PHP you can use a docker to provide your mysql DB. This is a transient docker, content will not persist between container restarts.

```bash
cp .env.example .env # <-- Only if you're a new install
docker run --rm -d --name arcv-mysql \
    -e MYSQL_DATABASE=$DB_DATABASE \
    -e MYSQL_USER=$DB_USERNAME \
    -e MYSQL_PASSWORD=$DB_PASSWORD \
    -e MYSQL_ROOT_PASSWORD=changemeplease \
    -p $DB_PORT:3306 \
    mysql:8
```



