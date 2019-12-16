# Somnambulist Project Manager

![](https://github.com/dave-redfern/somnambulist-project-manager/workflows/.github/workflows/main.yml/badge.svg)

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
Symlink the phar to `spm` or a.n.other name.

Run: `spm init` to create the standard configurtion (see later).

Or:

    $ brew install dave-redfern/somnambulist/spm

### Lazy Install

__Caution:__ you use the following at your own risk! No responsibility is taken
if the script causes bad things to happen. You have been warned.

The following will download the current (at the time of writing) phar archive,
verify the SHA384 hash and copy the phar to `/usr/local/bin`, then symlink it to
`spm` and verify it runs by calling `spm --version`. The script has been
set up with verbose output.

```bash
curl --silent --fail --location --retry 3 --output /tmp/somnambulist-project-manager.phar --url https://github.com/dave-redfern/somnambulist-project-manager/releases/download/0.3.0/somnambulist-project-manager.phar \
  && echo "7a51d920a9ae6bc77eb8229ef4ebee78f5522c2a7344bee876c9ae9626f3f1acd903bd386740ff9ed8bd5cb3113ebe01  /tmp/somnambulist-project-manager.phar" | shasum -a 384 -c \
  && mv -v /tmp/somnambulist-project-manager /usr/local/bin/somnambulist-project-manager.phar \
  && chmod -v 755 /usr/local/bin/somnambulist-project-manager.phar \
  && ln -vf -s /usr/local/bin/somnambulist-project-manager.phar /usr/local/bin/spm \
  && spm --ansi --version --no-interaction
```

If the hash check fails, remove the phar archive.

### Removing Project Manager

If installed via brew: `brew remove dave-redfern/somnambulist/spm`

Remove any symlinks you have and delete the phar file. Delete the configuration folder
from `~/.spm_projects.d/`

```bash
unlink /usr/local/bin/spm && rm -v /usr/local/bin/somnambulist-project-manager.phar
```

## Getting Started

Project Manager (spm from here), works through a set of config files that are stored in the
primary spm config folder. By default this is in `~/.spm_projects.d`. When you first run spm
it will prompt to run: `spm init` if this folder does not exist.

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
            data: 'git:git@github.com:dave-redfern/data-service-skeleton.git'
            service: 'composer:somnambulist/symfony-micro-service'
            web: 'composer:symfony/skeleton'

```

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

A library is a package / bundle that is used by multiple services. Typical libraries are
things like API clients for the services.

A service is an application that will be run within docker. Typically this will be a micro
service, but it could be the data services, a web site etc. Essentially anything that will
run in Docker.

In the case of services; spm can provide an overview of what is currently running and what
ports / domains have been exposed. This requires Docker be installed.

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

 * data - [Data Service](https://github.com/dave-redfern/data-service-skeleton)
 * service - [Symfony Micro Service](https://github.com/dave-redfern/micro-service-skeleton)
 * web - [Symfony Skeletong](https://github.com/symfony/skeleton)

Templates are grouped by type: `library` and `service`. Only library types are displayed when
making new libraries, and the same goes for services.

These can be overridden globally (in the main `~/.spm_projects.d/project_manager.yaml`) or on a
per-project basis in the templates section.

The source can be one of:

 * composer:
 * git:
 * <folder_name> in the project config `templates` folder
 * empty
 
`composer:` will use `composer create-project` and requires that the source be a valid installation
project either registered with packagist.org or with private packagist.com.

`git:` will clone and remove the `.git` folder, essentially using the git repo as a template.

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

### Setting Remote Repositories

By default when using `project:create`, `libraries:create` or `services:create` a git repository
is started, but no remote is set. You can set this after the fact by using:

 * config:<x>:repository
 
where X is project/library/service. This will either add or update the origin URL in the git
repo.

__Note:__ this command will only operate on origin. If you used a different name, then you
must manually set this and manually update the project config file.

### Customising the development environment

spm includes an `init:mac` command that will read a YAML file with various setup instructions
intended to get a new user up and running very quickly with the project. This file can include
any number of shell commands to set various aspects on the machine. The file must be named:
`init_mac.yaml`.

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

Make an issue on the github repo: https://github.com/dave-redfern/somnambulist-project-manager/issues

Pull requests are welcome!
