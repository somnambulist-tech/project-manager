## Project Configuration

`spm` works with projects. A project is a collection of libraries and services. The project
configuration defines what libraries and services make up the project and the repositories
where they can be located.

A library is a package / bundle that is used by multiple services. Typically libraries are
things like API clients for the services but can include project skeletons or other repos.

A service is an application that will be run within docker. Typically this will be a micro
service, but it could be the data services, a web site etc. Essentially anything that will
run in Docker.

In the case of services; `spm` can provide an overview of what is currently running and what
ports / domains have been exposed. This requires Docker CLI be installed. Provided your Docker
environment is correctly configured, `spm` will work with remote docker hosts.

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

### Standard Project Config

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

### Project

The project section contains the top level information about the project itself. The repository
is the remote repo of the project data and should be set if you wish to share the project
configuration.

Libraries and Services by default are stored together within the project working directory.
If you have many libraries and/or services you may wish to separate them logically into sub-folders.
Specify the folder name you wish to use, and then any service/library will be added to that
folder if installed or created.

### Docker

The docker config contains various settings for docker. Currently this is only the project name
however any other key: value pairs can be provided.

__Note:__ in a future version this configuration may be used when creating services.

### Libraries

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

### Services

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

### Templates

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

See [templates](./templates.md) for more information.

 * [Getting Started](getting-started.md)
 * [Project Configuration](project-configuration.md)
 * [Managing Services](services.md)
 * [Templates](templates.md)
 * [Changing Config Options](changing-config.md)
 * [Setting up a dev env](init-dev-env.md)
