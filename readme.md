# Somnambulist Project Manager

[![GitHub Actions release Build Status](https://github.com/somnambulist-tech/project-manager/workflows/release/badge.svg)](https://github.com/somnambulist-tech/project-manager/actions?query=workflow%3Arelease)

Project Manager is designed to help organise micro-services based PHP projects. It
incorporates commands for creating new services, libraries, and managing them.
Project configuration is stored as YAML files that you commit to a git repo and
share with your team. If you want to stop using it, delete the phar and the files
and continue on. There is no special configuration needed in any project.

## Features

 * groups separate micro services projects together
 * gives a docker overview of running processes
 * supports dependencies between services
 * supports making new services and libraries (configurable source repositories)
 * supports multiple projects on a single machine
 * all configuration is by yaml files

## Setup

Grab the phar archive and copy it to `/usr/local/bin` or add it to your path.
Symlink the phar to `spm` or a.n.other name. Be sure to verify the SHA checksum with
the ones in the release checksums text file.

Or:

    $ brew install somnambulist-tech/somnambulist/spm

Run: `spm init` to create the standard configuration (see later).

### Removing Project Manager

If installed via brew: `brew remove somnambulist-tech/somnambulist/spm`

Remove any symlinks you have and delete the phar file. Delete the configuration folder
from `~/.config/spm_projects.d/` or `~/.spm_projects.d/`

```bash
unlink /usr/local/bin/spm && rm -v /usr/local/bin/somnambulist-project-manager.phar
```

## Getting Started

Project Manager (spm from here), works through a set of config files that are stored in the
primary spm config folder. By default this is in `~/.config/spm_projects.d`. When you first
run spm it will prompt to run: `spm init` if this folder does not exist.

Note: spm will use `XDG_CONFIG_HOME` if defined, otherwise will default to `~/.config` as per
the XDG Base Directory spec. You can update existing configurations by moving the folder to
`~/.config/spm_projects.d`. The older `~/.spm_projects.d` is still supported.

The base folder can be changed by defining the env var: `SOMNAMBULIST_PROJECT_MANAGER_DIR`.
Please note that spm expects all config to still be located within your home folder. Once
running `SOMNAMBULIST_PROJECTS_CONFIG_DIR` is used as the fully qualified path to the spm
configuration folder.

By default projects are expected to be organised in `~/Projects/<project_name>`. The projects
default folder can be changed by editing the `project_manager.yaml` file and changing the
`projects_dir`.

Please note that this folder should exist within your _home_ folder. spm does not support
folders that are not located within your current users home as it relies heavily on the
`$_ENV['HOME']` variable to determine paths.

The default config file looks like:

```yaml
somnambulist:
    cache_dir: '${SOMNAMBULIST_PROJECTS_CONFIG_DIR}/_cache'
    projects_dir: 'Projects'

    templates:
        library:
            bundle: ~
            client: ~
            library: ~

        service:
            data: 'git:git@github.com:somnambulist-tech/data-service-skeleton.git'
            logging: 'git:git@github.com:somnambulist-tech/logging-service-skeleton.git'
            api: 'git:git@github.com:somnambulist-tech/web-api-skeleton.git'
            app: 'git:git@github.com:somnambulist-tech/web-app-skeleton.git'
            web: 'composer:symfony/skeleton'
            symfony: 'composer:symfony/skeleton'
```

You can check the current `spm` setup by using `spm check`. This will show you all the
current configuration locations and if the configuration was initialised properly. Add
`--debug` or `-d` to output the config and env file contents if they exist.

### Terminal and IDE integration

spm includes two helpers to make navigating a project easier: `open` and `goto`. `open` will
open an IDE (default PhpStorm) with the specified library and `goto` will start a new terminal
session at the library. If no library (library here being a library or a service) is specified,
a list of all options for the project will be presented.

In both cases the script / IDE can be configured by setting the following ENV vars in your
`.bashrc` or `.zshrc` or shell init file:

 * SOMNAMBULIST_EDITOR=XXXX - to override the PHP IDE. This should support a CLI command: e.g. atom
 * SOMNAMBULIST_TERMINAL_SCRIPT - a script that can open a new terminal at a path

By default, `goto` expects to work on macOS and uses osascript to launch a new terminal session.

### Project Configuration

spm works with projects. A project is a collection of libraries and services. The project
configuration defines what libraries and services make up the project and the repositories
where they can be located.

A library is a package / bundle that is used by multiple services. Typically libraries are
things like API clients for the services but can include project skeletons or other repos.

A service is an application that will be run within docker. Typically this will be a micro
service, but it could be the data services, a web site etc. Essentially anything that will
run in Docker.

In the case of services; spm can provide an overview of what is currently running and what
ports / domains have been exposed. This requires Docker CLI be installed. Provided your Docker
environment is correctly configured, spm will work with remote docker hosts.

To create a new project run: `spm project:create` or `spm create`. You will be prompted for
the name (basically the folder the config will be stored in) and then if you have a remote
Git repository already. If you do, provide it and it will be checked out immediately;
otherwise leave it blank and then provide the global docker compose project name. This is
important as it will be the prefix used to determine the container names of the services.
It should be relatively unique but not overly long e.g.: the company name, or project name.

For example:

```
 bin/console create
 Q  What is your projects name? Use lowercase and underscores: example
 ▲  You provided "example", is this correct? [y/n] y
 Q  What is your remote git repo to load config data from? Leave blank to skip:   
 ▲  You provided "", is this correct? [y/n] y
 Q  What will be your Docker Compose name? Use lowercase and hyphens: example
 ▲  You provided "example", is this correct? [y/n] y
 ▲  created configuration directory at /Users/dave/.spm_projects.d/example
 ▲  created configuration file at /Users/dave/.spm_projects.d/example/project.yaml
 ▲  creating git repository at /Users/dave/.spm_projects.d/example
 ✔  created git repository at /Users/dave/.spm_projects.d/example
 ✔  project created successfully, enable the project by running: use example
```

#### Standard Project Config

The generated config file has the following default structure:

```yaml
somnambulist:
    project:
        name: 'example'
        repository: ~
        working_dir: '${HOME}/Projects/example'
        libraries_dirname: ~
        services_dirname: ~

    docker:
        compose_project_name: 'example'

    libraries:

    services:
    
    templates:
        libraries:
        
        services:
```

The config file will expand any configured env args using `${ENV_NAME}` notation. Note that these
may be committed back so be careful. The home path will automatically be replaced with `${HOME}`
provided it is available.

#### Project

The project section contains the top level information about the project itself. The repository
is the remote repo of the project data and should be set if you wish to share the project
configuration.

Libraries and Services by default are stored together within the project working directory.
If you have many libraries and/or services you may wish to separate them logically into sub-folders.
Specify the folder name you wish to use, and then any service/library will be added to that
folder if installed or created.

#### Docker

The docker config contains various settings for docker. Currently this is only the project name
however any other key: value pairs can be provided.

__Note:__ in a future version this configuration may be used when creating services.

#### Libraries

Lists the configured libraries that are part of this project. A library config has:

 * name - the name within the project (must be unique)
 * repository - the remote git repository (can be null)
 * dirname - the local checkout folder name

For example:

```yaml
somnambulist:
    libraries:
        api_client:
            repository: 'some git repo'
            dirname: 'api-client'
```

#### Services

Services is the set of docker applications that belong to this project. A service has:

 * name - the name within the project (must be unique)
 * repository - the remote git repository (can be null)
 * dirname - the local checkout folder name
 * app_container - the name of the primary application container (should be relatively unique)
 * dependencies - an array of service names this service depends on

For example:

```yaml
somnambulist:
    services:
        cms-service:
            repository: 'some git repo'
            dirname: 'cms-service'
            app_container: 'cms-app'
            dependencies: ['data']
```

#### Templates

Templates allow for rapidly scaffolding new libraries / services. By default the following
services templates are pre-configured globally:

 * accounts - [Accounts Service](https://github.com/somnambulist-tech/accounts-service-skeleton)
 * data - [Data Service](https://github.com/somnambulist-tech/data-service-skeleton)
 * events - [Events Service](https://github.com/somnambulist-tech/events-service-skeleton)
 * logging - [Logging Service](https://github.com/somnambulist-tech/logging-service-skeleton)
 * api - [Web API Skeleton](https://github.com/somnambulist-tech/web-api-skeleton)
 * app - [Web App Skeleton](https://github.com/somnambulist-tech/web-app-skeleton)
 * symfony - [Symfony Skeleton](https://github.com/symfony/skeleton)
 * laravel - [Laravel Skeleton](https://github.com/laravel/laravel)

Templates are grouped by type: `library` and `service`. Only library types are displayed when
making new libraries, and the same goes for services.

These can be overridden globally (in the main `~/.spm_projects.d/project_manager.yaml`) or on a
per-project basis in the templates section.

The source can be one of:

 * composer:
 * git:
 * <folder_name> in the project config `templates` folder
 * empty

##### Composer Templates

`composer:` will use `composer create-project` and requires that the source be a valid installation
project either registered with packagist.org or with private packagist.com.

To use a custom repository like with Private Packagist add `?repository=https://repo/source`.

To specify a specific version to use add `&version=XXX`. To use the latest version set the version
to `dev-master`.

The full template source would then look like:
`composer:namespace/project-name?repository=https://some.repo/somewhere&version=2.0.2`.

##### Git Templates

`git:` will clone and remove the `.git` folder, essentially using the git repo as a template.

##### Static Templates

`<folder_name>` will copy all files in that folder to the new source. Additionally, if the template
folder contains a `post_copy.php` file, this will be run after the files have been copied. This
script can perform any actions needed for setup. Further: if a `post_copy_args.php` exists, then
for each argument, a question will be asked for input that is then provided to the script. The 
format of the args is to return an array of key => values that. For example:

```php
<?php
return [
    'name' => 'What is your name? ',
    'dob'  => 'What is your date of birth (CCYY-mm-dd)? ',
];
```

The `post_copy.php` file will be executed in a separate process in the context of the folder that
was created. If the return type of the args is not an array nothing will be asked.

The `post_copy.php` file should check the arg inputs before running.

##### Generic Template (fallback)

If the template is left empty then a very basic folder is created with some general defaults including:

 * .gitignore
 * readme.md
 * src/tests folder
 * composer.json
 * phpunit.xml.dist

A blank service template includes a few more files for docker settings.

### Adding libraries / services

First ensure you have created a project, then switch to that project using `spm use <project>`.

Now you can run `spm services:create` or `spm libraries:create` to create one of those types.
Once completed the library will be automatically registered in the project config and it will be
updated.

By default a new git repository is initialised in the library folder and all files committed to
the master branch.

If you have existing libraries, manually configure them in the `project.yaml` file or you can
try the auto-importer: `spm project:import`. This will attempt to allocate folders to either
a library or service and will try to match an active container name for the app. The rules
for container names are:

 * name contains `-app`
 * the labels contain `traefik.frontend.rule` and the `traefik.port` is `8080`
 
The import does not try to determine dependencies and will use the folder name as the project
and dirname values.

### Working with services

#### Starting

`spm` provides several wrappers to help with starting / stopping and getting an overview of the 
current projects services. All services commands are prefixed with `services:`. Once you have
added some services you can list them: `spm services:list` and then start one or more:
`spm services:start service1 service2 service3` or start all of the services:
`spm services:start all`. `services:start` is aliased to `start`

If you have defined dependencies and have not specified either `-d` to automatically start
dependencies, or `-D` to not start dependencies; you will be prompted if you wish to start
the dependencies first or not. If you opt to start services, then all dependencies will be
resolved and started first. You will not be prompted again after being asked the first time.

#### Status Overview

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

#### Stopping

To stop a service use: `spm services:stop service1 service2` or `spm services:stop all` to stop
all running services. Services that have dependencies will automatically cause dependent services
to be stopped as well. For example: if you have a main data service that provides databases, and
your apps depend on this; when you stop the service, then all the dependent services will be
stopped first. `services:stop` is aliased to `stop`.

If `syncit` is installed and there are active sync sessions, they will be stopped before the
container the service is stopped.

#### Logs

The docker logs can be viewed by running: `spm services:log service` or use the alias `log`. Add
`-f` to follow the log and use Ctrl+C to stop. This is the same output that you can get from:
`docker-compose log <container-name>`.

#### Rebuilding / cleaning the docker containers

If you encounter major issues with your containers or just want to reset to a completely clean
state, either use: `docker system prune --all --volumes` or `spm services:reset`. The `spm`
command calls the same options under-the-hood.

For less drastic rebuilds, the `services:start` command allows for:

 * `--rebuild / -b` - rebuild containers and then start
 * `--refresh / -r` - refresh and start
 
The difference between build and refresh is that refresh will force pull any new images as well
as rebuild the container images. Use this if the upstream image has been updated and you need
a new version instead of the cached version.

#### Copy files to/from containers

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

#### Adding containers to the service

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

#### Adding or overriding definitions

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

### Setting Remote Repositories

By default when using `project:create`, `libraries:create` or `services:create` a git repository
is started, but no remote is set. You can set this after the fact by using:

```shell script
spm config git:remote <project_name> <repo>
```

Several other config options can be changed using the `config` command:

 * docker:name
 * docker:network
 * git:remote
 * git:branch
 * service:container:name
 * service:dependency:add
 * service:dependency:remove
 * template:add
 * template:remove

If the option is not provided it will be prompted for; similarly if the project name is not
specified, the current list of projects will be presented.

__Note:__ when changing the remote repository, only a remote named `origin` will be modified.
If you used a different name, you must manually change the remote and update the project config
file yourself.

If the `branch` configuration directive is set in the project config, this will be used as the
remote branch name to track.

### Customising the development environment

spm includes a `setup:env` command that will read a YAML file with various setup instructions
intended to get a new user up and running very quickly with the project. This file can include
any number of shell commands to set various aspects on the machine. The file must be named:
`init_[mac|linux|windows].yaml`. This command is aliased to: `init:mac`, `init:linux` and `init:windows`.

For example: you can create steps that install brew, and additional packages; or that creates
overrides / default config files in various places.

The config format is pretty simple:

```yaml
somnambulist:
    steps:
        max_files:
            message: 'Setting max open files override (requires sudo)'
            commands:
                - { run: "echo '%s' | sudo tee /Library/LaunchDaemons/limit.maxfiles.plist", file: 'init.d/limit.maxfiles.plist' }

```

Each step should have a unique name. You must provide a message and then one or more commands
that need to be run in this step. Steps can use sudo and can read files in via echo. A command
must have a `run` property, and an optional `file` for the file source. This file must be
located in the project config folder.

The following environment variables can be used and will be replaced with the appropriate value:

 * `${CONFIG_DIR}` - the project configuration folder, usually: `~/.spm_projects.d/<name>`
 * `${HOME}` - the current users home folder, usually: `~/` (/Users/<name>)
 * `${CWD}` - the current working directory from where spm was run (`_SERVER['PWD']`)

If any step fails, the setup halts.

The setup can be run in test mode `--test` or output all the expanded scripts by using `--bash`.
Various steps can be skipped if already applied by using `--skip=X` where X is the number of
the step.

If a particular operation requires a long time or runs as a desktop process (e.g. xcode-select)
a special `exit` command can be added that will automatically halt the setup and output the
instructions on how to continue.

If debugging is used `-vvv` then each command and it's output will be streamed to the console.

When developing a setup script, be sure to do it in a virtual machine to avoid damaging your
own setup.

## Building the phar archive

To build the phar archive, first checkout / clone the project manager project. Run
`composer install` and ensure that `phar.readonly` is set to `0` in your php.ini.

You can then run: `bin/compile` which will create a `somnambulist-project-manager.phar`
file in the project root. The compile will output the SHA384 hash together with the
file location / name.

## Issues / Questions

Make an issue on the github repo: https://github.com/somnambulist-tech/project-manager/issues

Pull requests are welcome!
