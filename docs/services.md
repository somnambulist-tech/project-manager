## Working with services

### Starting

`spm` provides several wrappers to help with starting / stopping and getting an overview of the
current projects services. All services commands are prefixed with `services:`. Once you have
added some services you can list them: `spm services:list` and then start one or more:
`spm services:start service1 service2 service3` or start all of the services:
`spm services:start all`. `services:start` is aliased to `start`

If you have defined dependencies and have not specified either `-d` to automatically start
dependencies, or `-D` to not start dependencies; you will be prompted if you wish to start
the dependencies first or not. If you opt to start services, then all dependencies will be
resolved and started first. You will not be prompted again after being asked the first time.

### Status Overview

To get an overview of the current project services: `spm services:status`. This will query
Docker to get assorted data about all containers that match the preset compose name from the
project config and display the information in a table. This information includes:

 * running container name
 * current status (up/down etc)
 * the host if it has been set e.g. for traefik or the IP for the external DB connection
 * external port(s)
 * any volumes connected to the container

The status output is in a CLI table by default, however it can also be generated as:

 * CSV
 * JSON
 * pipe separated, plain text

Add `--format=csv|json|plain` to get the desired output.

If [SyncIt](https://github.com/somnambulist-tech/sync-it) is installed and setup on a
service; the current status will be output with the `spm` status information. Note: this
adds an amount of overhead and can take a few seconds to display for many running containers.
To disable `syncit` checks, add `--no-syncit`.

### Stopping

To stop a service use: `spm services:stop service1 service2` or `spm services:stop all` to stop
all running services. Services that have dependencies will automatically cause dependent services
to be stopped as well. For example: if you have a main data service that provides databases, and
your apps depend on this; when you stop the service, then all the dependent services will be
stopped first. `services:stop` is aliased to `stop`.

If `syncit` is installed and there are active sync sessions, they will be stopped before the
container the service is stopped.

### Logs

The docker logs can be viewed by running: `spm services:log service` or use the alias `log`. Add
`-f` to follow the log and use Ctrl+C to stop. This is the same output that you can get from:
`docker-compose log <container-name>`.

### Rebuilding / cleaning the docker containers

If you encounter major issues with your containers or just want to reset to a completely clean
state, either use: `docker system prune --all --volumes` or `spm services:reset`. The `spm`
command calls the same options under-the-hood.

For less drastic rebuilds, the `services:start` command allows for:

 * `--rebuild / -b` - rebuild containers and then start
 * `--refresh / -r` - refresh and start

The difference between build and refresh is that refresh will force pull any new images as well
as rebuild the container images. Use this if the upstream image has been updated and you need
a new version instead of the cached version.

### Copy files to/from containers

You can easily copy files to/from your running containers by using: `spm services:copy` that is
aliased as `copy` and `cp`. Note: that one side of this command must be a service name specified
as `service_name:/path/in/container`.

The format is: `spm services:copy source target`, for example to copy a file from your downloads
into the users-app container, configured as `users`:

```shell script
spm services:copy ~/Downloads/file.txt users:/app/tmp/file.txt
```

Under-the-hood, `services:copy` uses `docker cp`.

__Note:__ the copy command uses the **service** name; _not_ the actual docker container name!

__Note:__ the copy command uses the current working directory and not the services path when it
is running. Run `spm services:copy -h` for help and the current working folder.

### Adding containers to the service

From v0.19.0, `spm` has basic support for templated docker-compose services (containers). These
can be added to a service by using the `services:docker` command. Several definitions are
included in `spm` and others can be created by adding YAML files to a `definitions` folder in
the main `.spm_projects.d` folder.

The bundled definitions include:

 * dnsmasq
 * mariadb (10.5)
 * nginx (configured for php-fpm)
 * php-fpm7 (PHP 7.4)
 * php-fpm8
 * postgres (12.X)
 * rabbitmq
 * redis
 * syslog
 * traefik (2.4)

__Note:__ only a small subset of the docker-compose file format is supported. If you have an
existing file that is heavily customised you should not use this feature. At the time of writing
`config` sections are not supported, nor are swarm or other variants.

To add container services run: `spm services:docker <service_name> <container> <container>`

Multiple container templates can be specified at once. If none are given the list of definitions
will be displayed instead. Similarly if the service is not specified, you will be asked to choose
which one to modify.

For each chosen definition, any parameter substitutions will be prompted for values. Note that
these do not ask for confirmation.

Before committing the updated docker-compose.yml file (.yaml is not supported), a set of checks
are made to ensure that volumes and networks match and there are no port collisions when port
forwarding is enabled.

__Note:__ containers must have unique names and attempts to set a duplicate name will stop the
command.

__Note:__ some definitions have supporting files; these will be copied immediately, but in the
case of an error will be removed, however the folders may be left behind.

### Adding or overriding definitions

To add new definitions, or override the built-ins: add a `definitions` folder to the main `spm`
config folder. Then add a YAML file that contains just the service definition from a compose
file.

For example if the `docker-compose.yml` contains something like:

```yaml
services:
    mariadb:
        image: 'mariadb:10.5'
        environment:
            MYSQL_ROOT_PASSWORD: 'pass'
            MYSQL_DATABASE: 'db'
            MYSQL_USER: 'user'
            MYSQL_PASSWORD: 'pass'
        volumes:
            - 'mariadb:/var/lib/mysql'
        networks:
            - backend
        healthcheck:
            test: ["CMD", "mysqladmin", "ping"]
```

The definition would be named `mariadb.yaml` and contain:

```yaml
mariadb:
    image: 'mariadb:10.5'
    environment:
        MYSQL_ROOT_PASSWORD: 'pass'
        MYSQL_DATABASE: 'db'
        MYSQL_USER: 'user'
        MYSQL_PASSWORD: 'pass'
    volumes:
        - 'mariadb:/var/lib/mysql'
    networks:
        - backend
    healthcheck:
        test: ["CMD", "mysqladmin", "ping"]
```

Definitions support parameter substitutions using the pattern `{SPM::THE_NAME_HERE}` where
`THE_NAME_HERE` is your all-caps, underscore, separated string. Several are used by default
and are mapped to more suitable questions. These are:

`{SPM::NETWORK_NAME}` the docker network name, taken from the project config
`{SPM::SERVICE_NAME}`  the name for the container in the docker compose file
`{SPM::EXTERNAL_PORT}` if set, the exposed port that will be made available on the host
`{SPM::PROJECT_NAME}`  the current project name, taken from the project config
`{SPM::SERVICE_APP_NAME}` for nginx / fastcgi: the name of the container to forward to e.g. php-fpm
`{SPM::SERVICE_APP_PORT}` for nginx / fastcgi: the port of the container to forward to e.g.: 9000
`{SPM::SERVICE_HOST}` the host name that the container will resolve to (for traefik / proxies)
`{SPM::SERVICE_PORT}` the internal port the container will run on e.g.: 8080, 3306, 5432

__Note:__ the network name and project name are automatically resolved using the current
configuration from the project file. If a mis-match is detected, you will be notified of the
issue.

 * [Getting Started](getting-started.md)
 * [Project Configuration](project-configuration.md)
 * [Managing Services](services.md)
 * [Templates](templates.md)
 * [Changing Config Options](changing-config.md)
 * [Setting up a dev env](init-dev-env.md)
