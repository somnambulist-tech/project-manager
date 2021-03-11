## Getting Started

Project Manager (`spm` from here), works through a set of config files that are stored in the
primary `spm` config folder. By default this is in `~/.config/spm_projects.d`. When you first
run `spm` it will prompt to run: `spm init` if this folder does not exist.

Note: `spm` will use `XDG_CONFIG_HOME` if defined, otherwise will default to `~/.config` as per
the XDG Base Directory spec. You can update existing configurations by moving the folder to
`~/.config/spm_projects.d`. The older `~/.spm_projects.d` is still supported.

The base folder can be changed by defining the env var: `SOMNAMBULIST_PROJECT_MANAGER_DIR`.
Please note that `spm` expects all config to still be located within your home folder. Once
running `SOMNAMBULIST_PROJECTS_CONFIG_DIR` is used as the fully qualified path to the spm
configuration folder.

By default projects are expected to be organised in `~/Projects/<project_name>`. The projects
default folder can be changed by editing the `project_manager.yaml` file and changing the
`projects_dir`.

Please note that this folder should exist within your _home_ folder. `spm` does not support
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

`spm` includes two helpers to make navigating a project easier: `open` and `goto`. `open` will
open an IDE (default PhpStorm) with the specified library and `goto` will start a new terminal
session at the library. If no library (library here being a library or a service) is specified,
a list of all options for the project will be presented.

In both cases the script / IDE can be configured by setting the following ENV vars in your
`.bashrc` or `.zshrc` or shell init file:

 * SOMNAMBULIST_EDITOR=XXXX - to override the PHP IDE. This should support a CLI command: e.g. atom
 * SOMNAMBULIST_TERMINAL_SCRIPT - a script that can open a new terminal at a path

By default, `goto` expects to work on macOS and uses osascript to launch a new terminal session.

### Adding libraries / services

First ensure you have created a project, then switch to that project using `spm use <project>`.

Now you can run `spm services:create` or `spm libraries:create` to create one of those types.
Once completed the library will be automatically registered in the project config and it will be
updated.

By default a new git repository is initialised in the library folder and all files committed to
the master branch.

If you have existing libraries, manually configure them in the `project.yaml` file or, you can
try the auto-importer: `spm project:import`. This will attempt to allocate folders to either
a library or service and will try to match an active container name for the app. The rules
for container names are:

 * does _not_ have a `Vagrantfile`
 * name contains `-app`
 * there is a `traefik.enable` label and it is true and there are `traefik.http.routers` labels.

The import does not try to determine dependencies and will use the folder name as the project
and dirname values.

 * [Getting Started](getting-started.md)
 * [Project Configuration](project-configuration.md)
 * [Managing Services](services.md)
 * [Templates](templates.md)
 * [Changing Config Options](changing-config.md)
 * [Setting up a dev env](init-dev-env.md)
