# DOCKER

Docker stuff has been moved to the [infra repo](https://github.com/neontribe/ARCVInfra) for now.

## Docker for the DB

Transient docker, content will not persist between container restarts.

```bash
cp .env.example .env # <-- Only if you're a new install
docker run --rm -d --name arcv-mysql \
    -e MYSQL_DATABASE=arcv \
    -e MYSQL_USER=arcvuser \
    -e MYSQL_PASSWORD=arcvpassword \
    -e MYSQL_ROOT_PASSWORD=changemeplease \
    -p 3306:3306 \
    mysql:8
```
